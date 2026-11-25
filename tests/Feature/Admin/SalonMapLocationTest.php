<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\User;
use App\Support\SalonMapLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مکان سالن روی نقشه (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): «نشان» فقط لینک نقطه با مختصات دارد، پس مالک در «اطلاعات سالن» لینک
 * اشتراک‌گذاری یا مختصات را می‌چسباند (اختیاری) و «مسیریابی» مشتری لینک nshn.ir می‌سازد.
 */
class SalonMapLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_share_links_and_plain_coordinates(): void
    {
        $this->assertSame([35.7448, 51.3753], SalonMapLocation::parse('https://nshn.ir/?lat=35.7448&lng=51.3753'));
        $this->assertSame([35.76, 51.406], SalonMapLocation::parse('https://neshan.org/maps/share/35.76,51.406'));
        $this->assertSame([35.6997, 51.338], SalonMapLocation::parse('https://www.google.com/maps/place/x/@35.6997,51.338,17z'));
        $this->assertSame([35.6997, 51.338], SalonMapLocation::parse('https://www.google.com/maps/search/?api=1&query=35.6997%2C51.338'));
        $this->assertSame([35.72, 51.41], SalonMapLocation::parse('https://balad.ir/location?latitude=35.72&longitude=51.41&zoom=17'));
        $this->assertSame([35.7, 51.4], SalonMapLocation::parse('۳۵٫۷، ۵۱٫۴'));
        $this->assertSame([35.7, 51.4], SalonMapLocation::parse('51.4, 35.7'), 'عرض و طول جابه‌جا درست می‌شود');
        $this->assertNull(SalonMapLocation::parse('تهران، خیابان ولیعصر'));
    }

    public function test_errors_explain_what_to_paste(): void
    {
        $this->assertNull(SalonMapLocation::error(''));
        $this->assertStringContainsString('لینک کوتاه', SalonMapLocation::error('https://nshn.ir/AbC12x'));
        $this->assertStringContainsString('مختصات پیدا نشد', SalonMapLocation::error('خیابان ولیعصر'));
        $this->assertStringContainsString('در ایران نیست', SalonMapLocation::error('48.85, 2.35'));
    }

    public function test_owner_saves_and_clears_the_location_and_the_neshan_link_follows(): void
    {
        $salon = Salon::where('slug', 'rasta')->firstOrFail();
        $owner = User::factory()->create(['is_admin' => true]);
        $base = ['name' => $salon->name];

        $this->actingAs($owner)->get(route('admin.salon-settings.edit'))->assertOk()->assertSee('data-map-location', false);

        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => 'https://neshan.org/maps/share/35.76,51.406'])
            ->assertRedirect(route('admin.salon-settings.edit'));
        $salon->refresh();
        $this->assertSame('https://nshn.ir/?lat=35.76&lng=51.406', SalonMapLocation::neshanUrl($salon));
        $this->assertSame('35.76,51.406', SalonMapLocation::forInput($salon));

        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => 'خیابان ولیعصر'])->assertSessionHasErrors('map_location');
        $this->assertNotNull($salon->fresh()->map_lat);

        $this->put(route('admin.salon-settings.update'), $base)->assertRedirect(); // فیلد نیامد ← دست نخورد
        $this->assertNotNull($salon->fresh()->map_lat);

        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => ''])->assertRedirect();
        $this->assertNull(SalonMapLocation::neshanUrl($salon->fresh()));
    }
}
