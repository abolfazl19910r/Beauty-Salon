<?php

namespace App\Http\Controllers\Api\V1\Push;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Services\Push\AppMessenger;
use App\Services\Push\FcmClient;
use App\Services\Push\NtfyClient;
use App\Services\Push\PushMode;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * پوش اپ‌ها (بسته‌ی ۴) — مشترک هر دو اپ، پشت auth:sanctum + api.audience.
 *
 * PUT    /api/v1/push/device     ثبت/به‌روزرسانی گیرنده‌ی پوش همین نشست (یک دستگاه برای هر توکن Sanctum)
 * GET    /api/v1/push/device     وضعیت (برای صفحه‌ی «دریافت اعلان»)
 * DELETE /api/v1/push/device     قطع پوش همین نشست (خروج هم با حذف توکن خودکار قطع می‌کند)
 * POST   /api/v1/push/receipts   رسید پیام‌ها از همین دستگاه {ids: [...], opened?: bool}
 * POST   /api/v1/push/test       پوش آزمایشی به همین دستگاه (۳ در دقیقه)
 */
class PushDeviceController extends Controller
{
    public function upsert(Request $request, CurrentSalon $currentSalon): JsonResponse
    {
        $input = $request->validate([
            'provider' => ['nullable', 'in:fcm,ntfy'],
            'token' => ['required', 'string', 'min:8', 'max:512'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'manufacturer' => ['nullable', 'string', 'max:64'],
            'model' => ['nullable', 'string', 'max:64'],
            'os_version' => ['nullable', 'string', 'max:32'],
        ]);

        $provider = $input['provider'] ?? PushDevice::PROVIDER_FCM;
        if ($provider === PushDevice::PROVIDER_NTFY && ! app(NtfyClient::class)->configured()) {
            throw new ApiException('push_provider_unavailable', 'سرور ntfy روی این سرور تنظیم نشده است.', 422);
        }

        $accessToken = $request->user()->currentAccessToken();
        $hash = PushDevice::hashToken($provider, $input['token']);

        $device = DB::transaction(function () use ($request, $accessToken, $hash, $provider, $input, $currentSalon) {
            // همان توکن پوش روی نشست/حساب دیگر (ورود دوباره، حساب دیگر روی همان گوشی) دیگر نباید پیام بگیرد
            PushDevice::query()->where('token_hash', $hash)->where('personal_access_token_id', '!=', $accessToken->id)->delete();

            return PushDevice::query()->updateOrCreate(
                ['personal_access_token_id' => $accessToken->id],
                [
                    'user_id' => $request->user()->id,
                    'salon_id' => $currentSalon->id(),
                    'app' => (string) $request->attributes->get('api_audience'),
                    'provider' => $provider,
                    'token' => $input['token'],
                    'token_hash' => $hash,
                    'app_version' => $input['app_version'] ?? null,
                    'manufacturer' => $input['manufacturer'] ?? null,
                    'model' => $input['model'] ?? null,
                    'os_version' => $input['os_version'] ?? null,
                    'disabled_at' => null,
                    'disabled_reason' => null,
                ],
            );
        });

        return ApiResponse::success($this->present($device));
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->present($this->device($request)));
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->device($request)?->delete();

        return ApiResponse::success(null);
    }

    public function receipts(Request $request): JsonResponse
    {
        $input = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['string', 'uuid'],
            'opened' => ['nullable', 'boolean'],
        ]);

        $device = $this->device($request);
        if (! $device) {
            return ApiResponse::success(['acknowledged' => 0]);
        }

        $now = now();
        // فقط پیام‌های خود همین کاربر؛ شناسه‌ی پیام دیگران بی‌اثر است
        $ids = PushMessage::query()->whereIn('id', $input['ids'])->where('user_id', $request->user()->id)->pluck('id');

        $acknowledged = PushDelivery::query()->whereIn('push_message_id', $ids)->where('push_device_id', $device->id)
            ->whereNull('acked_at')->update(['acked_at' => $now]);
        if (! empty($input['opened'])) {
            PushDelivery::query()->whereIn('push_message_id', $ids)->where('push_device_id', $device->id)
                ->whereNull('opened_at')->update(['opened_at' => $now]);
        }
        PushMessage::query()->whereIn('id', $ids)->whereNull('acked_at')->update(['acked_at' => $now]);
        $device->forceFill(['last_receipt_at' => $now])->save();

        return ApiResponse::success(['acknowledged' => $acknowledged]);
    }

    public function test(Request $request, AppMessenger $messenger): JsonResponse
    {
        $device = $this->device($request);
        if (! $device || $device->disabled_at) {
            throw new ApiException('push_not_registered', 'این گوشی هنوز برای اعلان ثبت نشده است.', 409);
        }
        if (PushMode::current() === PushMode::OFF || ! app(FcmClient::class)->configured() && $device->provider === PushDevice::PROVIDER_FCM) {
            throw new ApiException('push_unavailable', 'ارسال اعلان اپ فعلاً روی سرور خاموش است.', 409);
        }

        $message = $messenger->sendTest($device);

        return ApiResponse::success(['message_id' => $message->id]);
    }

    private function device(Request $request): ?PushDevice
    {
        return PushDevice::query()->where('personal_access_token_id', $request->user()->currentAccessToken()->id)->first();
    }

    private function present(?PushDevice $device): array
    {
        return [
            'registered' => $device !== null && $device->disabled_at === null,
            'provider' => $device?->provider,
            'disabled_reason' => $device?->disabled_reason,
            'last_receipt_at' => $device?->last_receipt_at?->toIso8601String(),
            'server_enabled' => PushMode::current() !== PushMode::OFF && app(FcmClient::class)->configured(),
        ];
    }
}
