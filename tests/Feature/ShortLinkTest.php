<?php

namespace Tests\Feature;

use App\Models\ShortLink;
use App\Services\Links\ShortLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * لینک کوتاه پیامک‌ها (۲۰۲۶-۰۹-۳۰).
 */
class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_link_is_host_slash_b_slash_a_six_character_code_and_redirects(): void
    {
        config(['services.sms_links.host' => 'mahru.ir']);

        $short = app(ShortLinkService::class)->shorten('http://salon.mahru.ir/review/create?token='.str_repeat('x', 64), now()->addDays(7));

        $this->assertMatchesRegularExpression('#^mahru\.ir/b/[a-z0-9]{6}$#', $short);
        $code = substr($short, -6);
        $this->get('/b/'.$code)->assertRedirect('http://salon.mahru.ir/review/create?token='.str_repeat('x', 64));
    }

    public function test_the_host_falls_back_to_the_central_domain_then_app_url(): void
    {
        config(['services.sms_links.host' => null, 'app.central_domain' => 'rasta.ir']);
        $this->assertSame('rasta.ir', ShortLinkService::host());

        config(['app.central_domain' => null, 'app.url' => 'https://app.example.ir']);
        $this->assertSame('app.example.ir', ShortLinkService::host());
    }

    public function test_expired_and_unknown_links_are_not_found(): void
    {
        ShortLink::create(['code' => 'old123', 'target_url' => 'http://x.test/a', 'expires_at' => now()->subMinute()]);

        $this->get('/b/old123')->assertNotFound();
        $this->get('/b/nope99')->assertNotFound();
    }

    public function test_links_are_pruned_thirty_days_after_they_expire(): void
    {
        ShortLink::create(['code' => 'gone12', 'target_url' => 'http://x.test/a', 'expires_at' => now()->subDays(31)]);
        ShortLink::create(['code' => 'keep12', 'target_url' => 'http://x.test/b', 'expires_at' => now()->subDays(5)]);
        ShortLink::create(['code' => 'live12', 'target_url' => 'http://x.test/c', 'expires_at' => null]);

        $this->artisan('model:prune', ['--model' => [ShortLink::class]])->assertSuccessful();

        $this->assertSame(['keep12', 'live12'], ShortLink::orderBy('code')->pluck('code')->all());
    }

    public function test_the_review_request_sms_carries_a_short_link_to_the_review_form(): void
    {
        config(['services.sms_links.host' => 'mahru.ir']);
        $sent = [];
        $this->mock(\App\Services\SMSService::class, function ($mock) use (&$sent) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $message) use (&$sent) {
                $sent[] = $message;

                return true;
            });
        });
        $booking = \App\Models\Booking::factory()->create(['status' => 'completed']);

        $this->assertTrue(app(\App\Services\Review\ReviewService::class)->sendReviewRequest($booking));

        $this->assertCount(1, $sent);
        $this->assertMatchesRegularExpression('#mahru\.ir/b/([a-z0-9]{6})#', $sent[0]);
        $this->assertStringNotContainsString('token=', $sent[0]);
        preg_match('#/b/([a-z0-9]{6})#', $sent[0], $m);
        $token = \App\Models\ReviewToken::where('booking_id', $booking->id)->value('token');
        $this->assertStringEndsWith('/review/create?token='.$token, ShortLink::where('code', $m[1])->value('target_url'));
    }
}
