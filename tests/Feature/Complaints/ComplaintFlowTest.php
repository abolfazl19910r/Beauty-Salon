<?php

namespace Tests\Feature\Complaints;

use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\SuperAdmin\ComplaintDueNotification;
use App\Support\CurrentSalon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * شکایت با مهلت پاسخ ۷۲ ساعت (بسته‌ی مجوز 5د؛ دستورالعمل اینماد ۳-۸: ۵ شکایت بی‌پاسخ = تعلیق اینماد). روی تیکت پشتیبانی؛
 * مشتری واردشده و مدیر سالن ثبت می‌کنند، فقط ابرمدیر پاسخ می‌دهد و سالن شکایت مشتری را نمی‌بیند (تصمیم‌های ۲۰۲۶-۱۰-۲۵).
 */
class ComplaintFlowTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true, 'phone' => '09120009999']);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    public function test_a_customer_files_a_complaint_with_a_72_hour_deadline_and_the_super_admin_is_told(): void
    {
        $super = $this->superAdmin();
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('complaints.create'))->assertOk()->assertSee('۷۲');
        $this->actingAs($customer)->post(route('complaints.store'), [
            'target' => 'complaint_salon', 'title' => 'برخورد نامناسب', 'description' => 'در نوبت دیروز ساعت ۱۰ برخورد خوبی نشد.',
        ])->assertRedirect();

        $ticket = SupportTicket::sole();
        $this->assertTrue($ticket->isComplaint());
        $this->assertSame('complaint_salon', $ticket->category);
        $this->assertSame(app(CurrentSalon::class)->id(), $ticket->salon_id);
        $this->assertTrue($ticket->response_due_at->between(now()->addHours(71), now()->addHours(73)));
        $this->assertSame(1, UserNotification::where('notifiable_id', $super->id)->count(), 'صندوق اعلان ابرمدیر');

        $this->actingAs($customer)->get(route('complaints.show', $ticket))->assertOk()->assertSee('برخورد نامناسب');
        $this->actingAs($customer)->get(route('complaints.index'))->assertSee('در انتظار پاسخ');
    }

    public function test_the_first_staff_reply_stops_the_clock_and_the_customer_sees_it(): void
    {
        $super = $this->superAdmin();
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('complaints.store'), ['target' => 'complaint_platform', 'title' => 'پرداخت', 'description' => 'پول کم شد ولی نوبت ثبت نشد.']);
        $ticket = SupportTicket::sole();

        $this->actingAs($super)->post(route('superadmin.support-tickets.reply', $ticket), ['message' => 'بررسی شد، برگشت داده شد.'])->assertRedirect();

        $this->assertNotNull($ticket->fresh()->first_responded_at);
        $this->actingAs($customer)->get(route('complaints.show', $ticket))->assertSee('بررسی شد، برگشت داده شد.');
        $this->actingAs($customer)->get(route('complaints.index'))->assertSee('پاسخ داده شد');
    }

    public function test_others_cannot_see_a_complaint_and_the_salon_admin_does_not_list_it(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer)->post(route('complaints.store'), ['target' => 'complaint_salon', 'title' => 'محرمانه', 'description' => 'شرح شکایت از سالن.']);
        $ticket = SupportTicket::sole();

        $this->actingAs($this->customer())->get(route('complaints.show', $ticket))->assertNotFound();

        $admin = User::factory()->create(['is_admin' => true, 'salon_id' => app(CurrentSalon::class)->id()]);
        $this->assertFalse($admin->can('view', $ticket), 'مدیر سالن شکایت مشتری را نمی‌بیند');
    }

    public function test_a_salon_admin_can_file_a_complaint_about_the_platform(): void
    {
        $service = app(\App\Services\SupportTicket\SupportTicketService::class);
        $ticket = $service->createTicket(User::factory()->create()->id, [
            'kind' => 'complaint', 'title' => 'تسویه دیر شد', 'description' => 'تسویه‌ی هفته‌ی پیش هنوز نیامده.', 'category' => 'payment',
        ]);

        $this->assertTrue($ticket->isComplaint());
        $this->assertNotNull($ticket->response_due_at);
        $this->assertSame('high', $ticket->priority);
    }

    public function test_reminders_go_out_when_the_deadline_is_near_or_past_and_not_more_than_every_six_hours(): void
    {
        Notification::fake();
        $super = $this->superAdmin();
        $make = fn (array $attrs) => SupportTicket::factory()->create(array_merge(['kind' => 'complaint', 'status' => 'open', 'user_id' => $this->customer()->id], $attrs));

        $overdue = $make(['response_due_at' => now()->subHour()]);
        $soon = $make(['response_due_at' => now()->addHours(10)]);
        $make(['response_due_at' => now()->addHours(60)]);                               // هنوز زود است
        $make(['response_due_at' => now()->subHour(), 'first_responded_at' => now()]);  // پاسخ داده شده
        $make(['response_due_at' => now()->subHour(), 'status' => 'closed']);

        $this->artisan('complaints:remind')->expectsOutput('reminded: 1 overdue, 1 due soon')->assertSuccessful();

        Notification::assertSentTo($super, ComplaintDueNotification::class, fn ($n) => $n->overdue === 1 && $n->dueSoon === 1);
        $this->assertNotNull($overdue->fresh()->reminded_at);
        $this->assertNotNull($soon->fresh()->reminded_at);
        $this->assertStringContainsString('۷۲', ComplaintDueNotification::smsText(1, 1));

        $this->artisan('complaints:remind')->expectsOutput('nothing due');

        $this->travel(11)->hours();   // از یادآوری قبلی بیش از ۶ ساعت؛ دومی هم حالا از مهلت گذشته
        $this->artisan('complaints:remind')->expectsOutput('reminded: 2 overdue, 0 due soon');
    }

    public function test_the_super_admin_list_flags_overdue_complaints_and_the_nav_shows_the_count(): void
    {
        $super = $this->superAdmin();
        SupportTicket::factory()->create(['kind' => 'complaint', 'status' => 'open', 'title' => 'دیرشده', 'response_due_at' => now()->subHour(), 'user_id' => $this->customer()->id]);
        SupportTicket::factory()->create(['kind' => 'support', 'status' => 'open', 'title' => 'پشتیبانی عادی', 'user_id' => $this->customer()->id]);

        $this->actingAs($super)->get(route('superadmin.support-tickets.index', ['kind' => 'awaiting']))
            ->assertOk()->assertSee('دیرشده')->assertDontSee('پشتیبانی عادی')->assertSee('مهلت گذشته')
            ->assertSee('data-complaints-badge', false);
    }

    public function test_it_is_scheduled_hourly(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'complaints:remind'));

        $this->assertSame('25 * * * *', $event?->expression);
    }
}
