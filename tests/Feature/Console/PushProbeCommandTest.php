<?php

namespace Tests\Feature\Console;

use App\Experiments\PushProbe\FcmProbeSender;
use App\Experiments\PushProbe\ProbeMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** بسته‌ی ۰ اپلیکیشن — دستور push:probe (برنچ experiment/push-probe). */
class PushProbeCommandTest extends TestCase
{
    private string $dir;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/push-probe-'.uniqid();
        mkdir($this->dir);

        // config صریح: PHP ویندوز (XAMPP) openssl.cnf پیش‌فرض ندارد و ساختن کلید بدون آن شکست می‌خورد.
        // خود دستور به این فایل نیاز ندارد (فقط امضا می‌کند، کلید نمی‌سازد).
        $openssl = ['config' => base_path('tests/Fixtures/openssl-test.cnf')];
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA] + $openssl);
        $this->assertNotFalse($key, 'openssl_pkey_new: '.openssl_error_string());
        openssl_pkey_export($key, $privatePem, null, $openssl);
        $this->publicKey = openssl_pkey_get_details($key)['key'];

        file_put_contents($this->dir.'/sa.json', json_encode([
            'type' => 'service_account',
            'project_id' => 'mahru-push-probe',
            'client_email' => 'probe@mahru-push-probe.iam.gserviceaccount.com',
            'private_key' => $privatePem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));

        config([
            'push_probe.fcm.credentials' => $this->dir.'/sa.json',
            'push_probe.fcm.proxy' => null,
            'push_probe.ntfy.server' => null,
            'push_probe.ntfy.token' => null,
            'push_probe.log' => $this->dir.'/probe.csv',
        ]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    private function fakeGoogle(string $host = 'googleapis.com'): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
            'fcm.googleapis.com/v1/projects/mahru-push-probe/messages:send' => Http::response(['name' => 'projects/mahru-push-probe/messages/0:1']),
            'proxy.example.ir/s3cr3tPathSegment01/token' => Http::response(['access_token' => 'ya29.proxy']),
            'proxy.example.ir/s3cr3tPathSegment01/v1/projects/mahru-push-probe/messages:send' => Http::response(['name' => 'projects/mahru-push-probe/messages/0:2']),
        ]);
    }

    /** @return array<string, mixed> */
    private function jwtClaims(Request $request): array
    {
        [$header, $claims, $signature] = explode('.', $request->data()['assertion']);
        $decode = fn (string $part) => base64_decode(strtr($part, '-_', '+/'));

        $this->assertSame(1, openssl_verify("{$header}.{$claims}", $decode($signature), $this->publicKey, OPENSSL_ALGO_SHA256), 'JWT signature');
        $this->assertSame(['alg' => 'RS256', 'typ' => 'JWT'], json_decode($decode($header), true));

        return json_decode($decode($claims), true);
    }

    public function test_fcm_gets_an_access_token_with_a_signed_jwt_and_sends_a_high_priority_message(): void
    {
        $this->fakeGoogle();

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token-abcdefghijklmnop', '--label' => 'A17-باز'])
            ->expectsOutputToContain('پذیرفته شد')
            ->assertSuccessful();

        Http::assertSent(function (Request $request) {
            if ($request->url() !== 'https://oauth2.googleapis.com/token') {
                return false;
            }
            $claims = $this->jwtClaims($request);

            return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && $claims['iss'] === 'probe@mahru-push-probe.iam.gserviceaccount.com'
                && $claims['scope'] === 'https://www.googleapis.com/auth/firebase.messaging'
                && $claims['aud'] === 'https://oauth2.googleapis.com/token'
                && $claims['exp'] - $claims['iat'] === 3600;
        });

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/messages:send')) {
                return false;
            }
            $message = $request['message'];

            return $request->url() === 'https://fcm.googleapis.com/v1/projects/mahru-push-probe/messages:send'
                && $request->header('Authorization') === ['Bearer ya29.test']
                && $message['token'] === 'device-token-abcdefghijklmnop'
                && $message['android']['priority'] === 'HIGH'
                && $message['android']['notification']['channel_id'] === 'probe'
                && $message['data']['via'] === 'fcm'
                && $message['data']['label'] === 'A17-باز'
                && ctype_digit($message['data']['sent_at_ms'])
                // برچسب داخل متن همان شناسه و زمان data را دارد (اپ وقتی بسته بوده فقط متن را می‌بیند)
                && str_ends_with($message['notification']['body'], "[P:{$message['data']['probe_id']}:{$message['data']['sent_at_ms']}]")
                && str_contains($message['notification']['body'], 'A17-باز');
        });
    }

    public function test_fcm_proxy_sends_both_requests_through_the_worker_but_keeps_the_real_audience(): void
    {
        config(['push_probe.fcm.proxy' => 'https://proxy.example.ir/s3cr3tPathSegment01/']);
        $this->fakeGoogle();

        $this->artisan('push:probe', ['via' => 'fcm-proxy', 'target' => 'device-token'])->assertSuccessful();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://proxy.example.ir/s3cr3tPathSegment01/token'
            && $this->jwtClaims($r)['aud'] === 'https://oauth2.googleapis.com/token');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://proxy.example.ir/s3cr3tPathSegment01/v1/projects/mahru-push-probe/messages:send'
            && $r->header('Authorization') === ['Bearer ya29.proxy']);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'googleapis.com'));
    }

    public function test_fcm_proxy_without_a_configured_worker_fails_clearly(): void
    {
        Http::fake();

        $this->artisan('push:probe', ['via' => 'fcm-proxy', 'target' => 'device-token'])
            ->expectsOutputToContain('PUSH_PROBE_FCM_PROXY')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_count_sends_numbered_messages_with_one_access_token_and_logs_each_to_csv(): void
    {
        $this->fakeGoogle();

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token-abcdefghijklmnop', '--count' => 3, '--interval' => 0])
            ->assertSuccessful();

        $sends = Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/messages:send'));
        $this->assertSame(['1', '2', '3'], $sends->map(fn ($pair) => $pair[0]['message']['data']['probe_seq'])->values()->all());
        $this->assertCount(1, Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/token')));

        $rows = array_map('str_getcsv', file($this->dir.'/probe.csv', FILE_IGNORE_NEW_LINES));
        $this->assertSame('sent_at', $rows[0][0]);
        $this->assertCount(4, $rows);
        $this->assertSame('device-t…mnop', $rows[1][4]);
        $this->assertSame('1', $rows[1][5]);
    }

    public function test_long_runs_refresh_the_access_token_before_it_expires(): void
    {
        // S7 (۲۰۲۶-۱۰-۰۴): با --interval=3600 توکن یک‌ساعته‌ی پیام اول برای پیام دوم منقضی بود و همه 401 گرفتند
        $issued = 0;
        Http::fake([
            'oauth2.googleapis.com/token' => function () use (&$issued) {
                $issued++;

                return Http::response(['access_token' => "ya29.t{$issued}", 'expires_in' => 3599]);
            },
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/mahru-push-probe/messages/0:1']),
        ]);

        $sender = app(FcmProbeSender::class);
        $first = ProbeMessage::make('abcd', 1, 'fcm', 'RN8-شب');
        $this->assertTrue($sender->send('device-token', $first, false)->ok);

        $this->travel(10)->minutes();
        $this->assertTrue($sender->send('device-token', $first, false)->ok);
        $this->assertSame(1, $issued, 'توکن تازه هنوز تمدید نمی‌شود');

        $this->travel(56)->minutes();
        $this->assertTrue($sender->send('device-token', $first, false)->ok);
        $this->assertSame(2, $issued, 'نزدیک انقضا توکن تازه گرفته می‌شود');

        $tokens = Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/messages:send'))
            ->map(fn ($pair) => $pair[0]->header('Authorization')[0])->values()->all();
        $this->assertSame(['Bearer ya29.t1', 'Bearer ya29.t1', 'Bearer ya29.t2'], $tokens);
    }

    public function test_a_401_from_fcm_is_retried_once_with_a_new_access_token(): void
    {
        $issued = 0;
        $sends = 0;
        Http::fake([
            'oauth2.googleapis.com/token' => function () use (&$issued) {
                $issued++;

                return Http::response(['access_token' => "ya29.t{$issued}", 'expires_in' => 3599]);
            },
            'fcm.googleapis.com/*' => function () use (&$sends) {
                $sends++;

                return $sends === 1
                    ? Http::response(['error' => ['code' => 401, 'status' => 'UNAUTHENTICATED', 'message' => 'invalid credentials']], 401)
                    : Http::response(['name' => 'projects/mahru-push-probe/messages/0:9']);
            },
        ]);

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token'])
            ->expectsOutputToContain('0:9')
            ->assertSuccessful();

        $this->assertSame(2, $issued);
        $this->assertSame(2, $sends);
    }

    public function test_a_401_that_persists_after_a_new_token_is_reported_without_looping(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
            'fcm.googleapis.com/*' => Http::response(['error' => ['code' => 401, 'status' => 'UNAUTHENTICATED', 'message' => 'invalid credentials']], 401),
        ]);

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token'])
            ->expectsOutputToContain('UNAUTHENTICATED')
            ->assertFailed();

        $this->assertCount(2, Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/messages:send')));
    }

    public function test_an_unregistered_token_is_reported_with_the_fcm_error_code(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test']),
            'fcm.googleapis.com/*' => Http::response(['error' => [
                'code' => 404, 'status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.',
                'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']],
            ]], 404),
        ]);

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'old-token'])
            ->expectsOutputToContain('UNREGISTERED')
            ->assertFailed();
    }

    public function test_a_refused_access_token_stops_before_sending(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Invalid JWT Signature.'], 400),
            'fcm.googleapis.com/*' => Http::response([], 200),
        ]);

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token'])
            ->expectsOutputToContain('invalid_grant')
            ->assertFailed();

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'fcm.googleapis.com'));
    }

    public function test_an_unreachable_google_is_reported_as_a_connection_failure(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Connection timed out'));

        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'device-token'])
            ->expectsOutputToContain('اتصال برقرار نشد')
            ->assertFailed();
    }

    public function test_missing_target_unknown_route_and_missing_credentials_fail_without_sending(): void
    {
        Http::fake();

        $this->artisan('push:probe', ['via' => 'fcm'])->expectsOutputToContain('توکن FCM')->assertFailed();
        $this->artisan('push:probe', ['via' => 'apns', 'target' => 'x'])->expectsOutputToContain('شناخته نشد')->assertFailed();
        $this->artisan('push:probe', ['via' => 'ntfy'])->expectsOutputToContain('تاپیک ntfy')->assertFailed();

        config(['push_probe.fcm.credentials' => null]);
        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'x'])->expectsOutputToContain('PUSH_PROBE_FCM_CREDENTIALS در .env تنظیم نشده')->assertFailed();

        config(['push_probe.fcm.credentials' => $this->dir.'/missing.json']);
        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'x'])->expectsOutputToContain($this->dir.'/missing.json')->assertFailed();

        // تغییر نام در ویندوز با پسوند پنهان: sa.json در عمل sa.json.json است
        copy($this->dir.'/sa.json', $this->dir.'/renamed.json.json');
        config(['push_probe.fcm.credentials' => $this->dir.'/renamed.json']);
        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'x'])->expectsOutputToContain('پسوند دوبار آمده')->assertFailed();

        file_put_contents($this->dir.'/google-services.json', json_encode(['project_info' => ['project_id' => 'x']]));
        config(['push_probe.fcm.credentials' => $this->dir.'/google-services.json']);
        $this->artisan('push:probe', ['via' => 'fcm', 'target' => 'x'])->expectsOutputToContain('google-services.json')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_check_reports_reachability_and_both_access_token_routes_without_sending_a_message(): void
    {
        config(['push_probe.fcm.proxy' => 'https://proxy.example.ir/s3cr3tPathSegment01']);
        Http::fake([
            'oauth2.googleapis.com/token' => function (Request $r) {
                return $r->method() === 'GET' ? Http::response('<html>405</html>', 405) : Http::response(['access_token' => 'ya29.test']);
            },
            'fcm.googleapis.com/' => Http::response('', 404),
            'proxy.example.ir/s3cr3tPathSegment01/token' => Http::response(['error' => 'x'], 502),
        ]);

        $this->artisan('push:probe', ['via' => 'check'])
            ->expectsOutputToContain('توکن دسترسی FCM — مستقیم')
            ->expectsOutputToContain('توکن دسترسی FCM — از Worker')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/messages:send'));
        Http::assertSentCount(4);
    }

    public function test_ntfy_publishes_json_to_the_self_hosted_server_without_touching_google(): void
    {
        config(['push_probe.ntfy.server' => 'http://192.168.1.10:8090/', 'push_probe.ntfy.token' => 'tk_probe']);
        Http::fake(['192.168.1.10:8090' => Http::response(['id' => 'abc123', 'event' => 'message'])]);

        $this->artisan('push:probe', ['via' => 'ntfy', 'target' => 'mahru-probe-x7k2', '--label' => 'RN8-قطعی', '--count' => 2, '--interval' => 0])
            ->expectsOutputToContain('abc123')
            ->assertSuccessful();

        $sent = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        $this->assertCount(2, $sent);
        $request = $sent[0];
        $this->assertSame('http://192.168.1.10:8090', $request->url());
        $this->assertSame(['Bearer tk_probe'], $request->header('Authorization'));
        $this->assertSame('mahru-probe-x7k2', $request['topic']);
        $this->assertSame(5, $request['priority']);
        $this->assertSame('آزمایش پوش ماهرو #1', $request['title']);
        // همان برچسب متن FCM — اپ آزمایشی و فرم نتیجه برای هر دو راه یکسان کار می‌کنند
        $this->assertMatchesRegularExpression('/^مسیر NTFY · ارسال \d{2}:\d{2}:\d{2} · RN8-قطعی \[P:[a-z0-9]{4}-1:\d{13}\]$/u', $request['message']);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'googleapis.com'));

        $rows = array_map('str_getcsv', file($this->dir.'/probe.csv', FILE_IGNORE_NEW_LINES));
        $this->assertSame(['ntfy', 'mahru-probe-x7k2'], [$rows[1][2], $rows[1][4]]);
    }

    public function test_ntfy_without_a_server_or_with_an_unreachable_one_fails_clearly(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect'));

        $this->artisan('push:probe', ['via' => 'ntfy', 'target' => 'topic'])
            ->expectsOutputToContain('PUSH_PROBE_NTFY_SERVER')
            ->assertFailed();
        Http::assertNothingSent();

        config(['push_probe.ntfy.server' => 'http://127.0.0.1:8090']);
        $this->artisan('push:probe', ['via' => 'ntfy', 'target' => 'topic'])
            ->expectsOutputToContain('اتصال برقرار نشد')
            ->assertFailed();
    }

    public function test_check_keeps_going_when_the_key_file_is_missing_and_shows_its_path(): void
    {
        config(['push_probe.fcm.credentials' => $this->dir.'/missing.json']);
        Http::fake(['*' => Http::response('', 404)]);

        $this->artisan('push:probe', ['via' => 'check'])
            ->expectsOutputToContain('fcm.googleapis.com')
            ->expectsOutputToContain('پیدا نشد یا خواندنی نیست: '.$this->dir.'/missing.json')
            ->assertSuccessful();

        // فقط GET دسترس‌پذیری؛ هیچ درخواست توکن (POST) فرستاده نشد
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
    }

    public function test_check_reports_ntfy_health(): void
    {
        config(['push_probe.fcm.credentials' => null, 'push_probe.ntfy.server' => 'http://127.0.0.1:8090']);
        Http::fake([
            '127.0.0.1:8090/v1/health' => Http::response(['healthy' => true]),
            '*' => Http::response('', 404),
        ]);

        $this->artisan('push:probe', ['via' => 'check'])
            // هر خط خروجی فقط با یک expectation جفت می‌شود؛ نتیجه و نام سرور در یک ردیف‌اند
            ->expectsOutputToContain('سرور ntfy: http://127.0.0.1:8090 | موفق     | 200')
            ->assertSuccessful();
    }
}
