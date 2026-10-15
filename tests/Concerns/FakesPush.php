<?php

namespace Tests\Concerns;

use App\Models\PushDevice;
use App\Models\User;
use App\Services\SMSService;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\Http;

/**
 * پوش در تست‌ها (بسته‌ی ۴): حساب سرویس ساختگی با کلید RSA واقعی (امضای JWT واقعاً ساخته می‌شود)، FCM با Http::fake،
 * ضبط پیامک‌ها به‌جای کاوه‌نگار، و دستگاه ثبت‌شده برای یک کاربر.
 */
trait FakesPush
{
    private static ?string $pushKeyPem = null;

    /** @var list<array{mobile: string, message: string, salon_id: ?int, feature: ?Feature}> */
    protected array $smsSent = [];

    protected function fakeFcm(array $sendResponses = []): void
    {
        if (self::$pushKeyPem === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $pem);
            self::$pushKeyPem = $pem;
        }

        $path = sys_get_temp_dir().'/mahru-fcm-test-'.getmypid().'.json';
        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'project_id' => 'mahru-test',
            'client_email' => 'push@mahru-test.iam.gserviceaccount.com',
            'private_key' => self::$pushKeyPem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));

        config(['push.fcm.credentials' => $path]);
        cache()->forget('push:fcm:access_token');

        // هر بار از نو (Http::fake قبلی پاک می‌شود؛ وگرنه اولین الگوی ثبت‌شده برنده است)
        Http::swap(new \Illuminate\Http\Client\Factory($this->app['events']));
        Http::fake($sendResponses + [
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/mahru-test/messages/1']),
        ]);
    }

    protected function recordSms(): void
    {
        $test = $this;
        $this->app->instance(SMSService::class, new class($test) extends SMSService
        {
            public function __construct(private readonly object $test) {}

            public function send(string $mobile, string $message, ?int $salonId = null, ?Feature $feature = null): bool
            {
                $this->test->pushSmsLog(['mobile' => $mobile, 'message' => $message, 'salon_id' => $salonId, 'feature' => $feature]);

                return true;
            }
        });
    }

    public function pushSmsLog(array $sms): void
    {
        $this->smsSent[] = $sms;
    }

    protected function deviceFor(User $user, string $app = PushDevice::APP_CUSTOMER, array $attributes = []): PushDevice
    {
        $token = $user->createToken('RN8', [$app], now()->addDays(90));

        return PushDevice::query()->create(array_merge([
            'user_id' => $user->id,
            'personal_access_token_id' => $token->accessToken->id,
            'salon_id' => $user->salon_id,
            'app' => $app,
            'provider' => PushDevice::PROVIDER_FCM,
            'token' => 'fcm-token-'.$user->id.'-'.$token->accessToken->id,
            'token_hash' => PushDevice::hashToken('fcm', 'fcm-token-'.$user->id.'-'.$token->accessToken->id),
            'manufacturer' => 'Xiaomi',
            'model' => 'Redmi Note 8',
        ], $attributes));
    }

    /** @return list<array> ارسال‌های واقعی به FCM (نه گرفتن توکن) */
    protected function fcmSends(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'fcm.googleapis.com'))
            ->map(fn ($pair) => $pair[0]->data())
            ->values()->all();
    }
}
