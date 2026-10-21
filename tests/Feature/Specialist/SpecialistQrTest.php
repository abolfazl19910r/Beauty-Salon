<?php

namespace Tests\Feature\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «QR من» پنل متخصص (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): QR صفحه‌ی عمومی خود متخصص، PNG و PDF.
 */
class SpecialistQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_specialist_sees_own_qr_and_gets_pdfs(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id, 'name' => 'نگار']);
        Specialist::factory()->create(['name' => 'دیگری']);

        $this->actingAs($user)->get(route('specialist.qr'))->assertOk()
            ->assertSee('data-salon-qr="'.url('/s/'.$salon->slug.'/specialists/'.$specialist->id).'?src=qr"', false)
            ->assertSee('رزرو آنلاین نوبت با نگار')
            ->assertSee('data-qr-poster-format="story"', false)
            ->assertSee(route('specialist.qr'), false)
            // منوی کشویی موبایل (رفع ۲۰۲۶-۱۰-۲۱): وضعیت صریح، نه toggle کلاس z-30 که منو را زیر محتوا می‌برد
            ->assertSee('function toggleSidebar(force)', false)
            ->assertDontSee("classList.toggle('z-30')", false);

        foreach (['poster', 'cards'] as $layout) {
            $response = $this->get(route('specialist.qr.pdf', ['layout' => $layout]))->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
            $this->assertStringContainsString('-specialist-'.$specialist->id.'-qr-'.$layout.'.pdf', (string) $response->headers->get('Content-Disposition'));
        }
    }

    public function test_user_without_specialist_record_cannot_open_it(): void
    {
        $this->actingAs(User::factory()->create(['phone' => '09129999999']))->get(route('specialist.qr'))->assertStatus(404);
    }
}
