<?php

namespace Tests\Feature\Admin;

use App\Models\QrScan;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): صفحه‌ی «QR و پوستر سالن» مدیر — QR سالن و متخصص، PDF چاپی، شمارش بازدید از QR (?src=qr).
 */
class SalonQrPageTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_page_shows_salon_qr_and_menu_link(): void
    {
        $this->actingAs($this->admin())->get(route('admin.salon-qr.index'))->assertOk()
            ->assertSee('data-salon-qr="'.url('/s/'.$this->salon->slug).'?src=qr"', false)
            ->assertSee('data-qr-poster-format="story"', false)
            ->assertSee('data-qr-pdf="poster"', false)
            ->assertSee('data-qr-pdf="cards"', false)
            ->assertSee(route('admin.salon-qr.index'), false)
            ->assertSee('salon-qr-poster', false);
    }

    public function test_specialist_qr_points_to_the_public_specialist_page(): void
    {
        $specialist = Specialist::factory()->create(['name' => 'نگار']);

        $this->actingAs($this->admin())->get(route('admin.salon-qr.index', ['specialist' => $specialist->id]))->assertOk()
            ->assertSee('data-salon-qr="'.url('/s/'.$this->salon->slug.'/specialists/'.$specialist->id).'?src=qr"', false)
            ->assertSee('رزرو آنلاین نوبت با نگار');

        $this->get(route('admin.specialists.index'))->assertOk()->assertSee('data-specialist-qr-link', false);
        $this->get(route('admin.specialists.show', $specialist))->assertOk()->assertSee('data-specialist-qr-link', false);
    }

    public function test_other_salons_specialist_is_not_found(): void
    {
        $other = Salon::factory()->create(['slug' => 'digar']);
        $foreign = Specialist::factory()->create(['salon_id' => $other->id]);

        $this->actingAs($this->admin());
        $this->get(route('admin.salon-qr.index', ['specialist' => $foreign->id]))->assertNotFound();
        $this->get(route('admin.salon-qr.pdf', ['layout' => 'poster', 'specialist' => $foreign->id]))->assertNotFound();
    }

    public function test_printable_pdfs(): void
    {
        $specialist = Specialist::factory()->create();
        $this->actingAs($this->admin());

        foreach (['poster', 'cards'] as $layout) {
            $response = $this->get(route('admin.salon-qr.pdf', ['layout' => $layout]))->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
            $this->assertStringContainsString($this->salon->slug.'-qr-'.$layout.'.pdf', (string) $response->headers->get('Content-Disposition'));
        }

        $this->assertStringContainsString('-specialist-'.$specialist->id.'-qr-cards.pdf', (string) $this->get(route('admin.salon-qr.pdf',
            ['layout' => 'cards', 'specialist' => $specialist->id]))->assertOk()->headers->get('Content-Disposition'));
        $this->get(route('admin.salon-qr.pdf', ['layout' => 'tablo']))->assertSessionHasErrors('layout');
    }

    public function test_guest_and_customer_cannot_open_the_page(): void
    {
        $this->get(route('admin.salon-qr.index'))->assertRedirect();
        $customer = User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer']);
        $this->actingAs($customer)->get(route('admin.salon-qr.index'))->assertForbidden();
    }

    public function test_qr_visits_are_counted_once_per_session_per_day(): void
    {
        $specialist = Specialist::factory()->create(['name' => 'نگار']);
        $home = route('home', ['salon_slug' => $this->salon->slug, 'src' => 'qr']);

        $this->get($home)->assertOk();
        $this->get($home)->assertOk();
        $this->get(route('home', ['salon_slug' => $this->salon->slug]))->assertOk();
        $this->assertSame(1, (int) QrScan::query()->where('specialist_id', 0)->sum('hits'));

        $this->flushSession();
        $this->get($home)->assertOk();
        $this->get(route('specialists.show', ['salon_slug' => $this->salon->slug, 'specialist' => $specialist->id, 'src' => 'qr']))->assertOk();
        $this->assertSame(2, (int) QrScan::query()->where('specialist_id', 0)->sum('hits'));
        $this->assertSame(1, (int) QrScan::query()->where('specialist_id', $specialist->id)->sum('hits'));

        // سالن دیگر: بازدیدش در صفحه‌ی این سالن نمی‌آید
        $other = Salon::factory()->create(['slug' => 'digar']);
        $this->flushSession();
        $this->get(route('home', ['salon_slug' => 'digar', 'src' => 'qr']))->assertOk();
        app(CurrentSalon::class)->set($this->salon);

        $html = $this->actingAs($this->admin())->get(route('admin.salon-qr.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/QR سالن: <span class="persian-number font-bold">۲<\/span>/u', $html);
        $this->assertStringContainsString('QR نگار: <span class="persian-number">۱</span>', $html);
        $this->assertSame(1, QrScan::query()->withoutGlobalScopes()->where('salon_id', $other->id)->count());
    }
}
