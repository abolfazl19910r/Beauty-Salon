<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\User;
use App\Support\SalonMapLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * مکان سالن روی نقشه (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): «نشان» فقط لینک نقطه با مختصات دارد، پس مالک در «اطلاعات سالن» لینک
 * اشتراک‌گذاری یا مختصات را می‌چسباند (اختیاری). آزمون ویندوز: لینک کوتاه گوگل‌مپ هم پذیرفته و خودش «مسیریابی» می‌شود.
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
        // مقصد لینک کوتاه گوگل‌مپ: خود نقطه (!3d!4d) بر مرکز نما (@) مقدم است
        $this->assertSame([35.7012, 51.3911], SalonMapLocation::parse('https://www.google.com/maps/place/Salon/@35.70,51.39,17z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d35.7012!4d51.3911'));
        $this->assertSame([35.7, 51.4], SalonMapLocation::parse('https://maps.google.com/?q=35.7,51.4'));
        $this->assertSame([35.705, 51.439], SalonMapLocation::parse('https://neshan.org/maps/places/_bvPXeQxucEv#c35.705-51.439-15z-0p'));
    }

    public function test_accepts_short_and_full_map_links_only_from_map_hosts(): void
    {
        $this->assertSame('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7', SalonMapLocation::mapLink('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7'));
        $this->assertNotNull(SalonMapLocation::mapLink('https://goo.gl/maps/AbC123'));
        $this->assertNotNull(SalonMapLocation::mapLink('https://nshn.ir/AbC12x'));
        $this->assertNotNull(SalonMapLocation::mapLink('https://balad.ir/p/xyz'));
        $this->assertNotNull(SalonMapLocation::mapLink('https://www.google.com/maps/place/x'));
        $this->assertNull(SalonMapLocation::mapLink('https://www.google.com/search?q=salon'), 'جستجوی گوگل نقشه نیست');
        $this->assertNull(SalonMapLocation::mapLink('https://goo.gl/AbC123'));
        $this->assertNull(SalonMapLocation::mapLink('https://evil.example/maps.app.goo.gl'));
        $this->assertNull(SalonMapLocation::mapLink('https://maps.app.goo.gl.evil.example/x'));
        $this->assertNull(SalonMapLocation::mapLink('javascript:alert(1)'));
    }

    public function test_short_link_is_resolved_through_map_hosts_only(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/S/data=!3d35.7012!4d51.3911']),
        ]);
        $this->assertSame([35.7012, 51.3911], SalonMapLocation::resolve('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7'));
    }

    public function test_short_link_redirect_to_a_non_map_host_is_not_followed(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin'])]);
        $this->assertNull(SalonMapLocation::resolve('https://maps.app.goo.gl/X'), 'ریدایرکت به میزبان غیرنقشه دنبال نمی‌شود');
        Http::assertSentCount(1);
    }

    public function test_unreachable_short_link_is_kept_without_coordinates(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $this->assertSame(
            ['map_url' => 'https://maps.app.goo.gl/MQ6n73uU3bURTPFx7', 'map_lat' => null, 'map_lng' => null],
            SalonMapLocation::attributesFor('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7')
        );
        $this->assertSame(['map_url' => null, 'map_lat' => 35.7, 'map_lng' => 51.4], SalonMapLocation::attributesFor('35.7, 51.4'));
    }

    public function test_errors_explain_what_to_paste(): void
    {
        $this->assertNull(SalonMapLocation::error(''));
        $this->assertNull(SalonMapLocation::error('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7'), 'لینک کوتاه پذیرفته می‌شود');
        $this->assertStringContainsString('لینک نقشه یا مختصات نیست', SalonMapLocation::error('خیابان ولیعصر'));
        $this->assertStringContainsString('لینک نقشه یا مختصات نیست', SalonMapLocation::error('https://example.com/x'));
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
        $this->assertSame('https://neshan.org/maps/share/35.76,51.406', SalonMapLocation::forInput($salon), 'لینک نقشه خودش نگه داشته می‌شود');

        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => 'خیابان ولیعصر'])->assertSessionHasErrors('map_location');
        $this->assertNotNull($salon->fresh()->map_lat);

        $this->put(route('admin.salon-settings.update'), $base)->assertRedirect(); // فیلد نیامد ← دست نخورد
        $this->assertNotNull($salon->fresh()->map_lat);

        // لینک کوتاه گوگل‌مپ: خودش ذخیره و «مسیریابی» همان را باز می‌کند؛ مختصات اگر سرور توانست باز کند
        Http::fake(['maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/S/@35.7012,51.3911,17z'])]);
        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => 'https://maps.app.goo.gl/MQ6n73uU3bURTPFx7'])->assertRedirect();
        $salon->refresh();
        $this->assertSame('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7', SalonMapLocation::directionsUrl($salon));
        $this->assertSame('https://maps.app.goo.gl/MQ6n73uU3bURTPFx7', SalonMapLocation::forInput($salon));
        $this->assertSame('35.7012, 51.3911', SalonMapLocation::coordinates($salon));
        $this->get(route('admin.salon-settings.edit'))->assertSee('مختصات خوانده‌شده');

        $this->put(route('admin.salon-settings.update'), $base + ['map_location' => ''])->assertRedirect();
        $salon->refresh();
        $this->assertNull(SalonMapLocation::directionsUrl($salon));
        $this->assertNull($salon->map_url);
    }

    public function test_home_page_shows_the_address_and_a_click_to_load_map(): void
    {
        $salon = Salon::where('slug', 'rasta')->firstOrFail();
        $salon->update(['address' => 'تهران، خیابان ولیعصر', 'map_url' => 'https://maps.app.goo.gl/X', 'map_lat' => null, 'map_lng' => null]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('data-salon-location', $html);
        $this->assertStringContainsString('href="https://maps.app.goo.gl/X"', $html);
        $this->assertStringNotContainsString('data-location-map ', $html, 'بی مختصات نقشه‌ای نیست');

        $salon->update(['map_lat' => 35.705, 'map_lng' => 51.439]);
        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('data-location-map-load', false);
        $response->assertSee(e('https://www.openstreetmap.org/export/embed.html?bbox=51.433,35.701,51.445,35.709&layer=mapnik&marker=35.705,51.439'), false);
        $this->assertStringNotContainsString('<iframe', $response->getContent(), 'نقشه فقط با کلیک بار می‌شود');
        $this->assertStringContainsString('https://www.openstreetmap.org', (string) $response->headers->get('Content-Security-Policy').(string) $response->headers->get('Content-Security-Policy-Report-Only'));

        $salon->update(['address' => null, 'map_url' => null, 'map_lat' => null, 'map_lng' => null]);
        $this->get(route('home'))->assertOk()->assertDontSee('data-salon-location', false);
    }
}
