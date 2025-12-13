<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $admin = \App\Models\User::firstOrCreate(
            ['phone' => '09399717435'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin'),
                'phone_verified_at' => now(),
            ]
        );

        $supportUser = \App\Models\User::firstOrCreate(
            ['phone' => '09121234567'],
            [
                'name' => 'Support User',
                'password' => Hash::make('password'),
                'phone_verified_at' => now(),
            ]
        );

        $users = User::factory(10)->create();
        $allUsers = User::all();

        $tickets = SupportTicket::factory(30)
            ->recycle($users)
            ->state(new Sequence(
                ['status' => 'open'],
                ['status' => 'in_progress', 'assigned_to' => $admin->id],
                ['status' => 'resolved', 'resolved_at' => now()->subDays(5)],
                ['status' => 'closed', 'resolved_at' => now()->subDays(10), 'closed_at' => now()->subDays(2)],
            ))
            ->create();

        $tickets->each(function (SupportTicket $ticket) use ($admin, $allUsers) {
            $messageCount = rand(1, 5);

            for ($i = 0; $i < $messageCount; $i++) {
                if ($ticket->status !== 'closed' && $ticket->status !== 'resolved') {
                    $isStaffReply = (rand(0, 1) === 1) && ($i > 0);

                    SupportTicketMessage::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $isStaffReply ? $admin->id : $ticket->user_id,
                        'is_staff_reply' => $isStaffReply,
                    ]);
                } else {
                    SupportTicketMessage::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $ticket->user_id,
                        'is_staff_reply' => false,
                    ]);
                }
            }
        });

        $urgentTicket = SupportTicket::factory()->urgent()->create([
            'user_id' => $users->random()->id,
            'title' => 'مشکل اضطراری در پرداخت و رزرو',
            'description' => 'پرداخت انجام شده ولی نوبت من ثبت نشده است. نیاز به رسیدگی فوری دارم.',
            'category' => 'payment',
        ]);

        SupportTicketMessage::factory()->create([
            'ticket_id' => $urgentTicket->id,
            'user_id' => $urgentTicket->user_id,
            'message' => 'شماره پیگیری پرداخت: '.rand(10000000, 99999999),
        ]);

        SupportTicketMessage::factory()->staffReply()->create([
            'ticket_id' => $urgentTicket->id,
            'user_id' => $admin->id,
            'message' => 'در حال بررسی با بخش مالی. لطفا چند دقیقه منتظر بمانید.',
        ]);

        $urgentTicket->update(['status' => 'in_progress', 'assigned_to' => $admin->id]);
    }
}
