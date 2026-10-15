<?php

namespace App\Services\Push;

use App\Jobs\PushSmsFallbackJob;
use App\Jobs\SendPushJob;
use App\Models\PushDelivery;
use App\Models\PushDevice;
use App\Models\PushMessage;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notification\NotificationSettingService;
use App\Services\SMSService;
use App\Support\Features\Feature;
use App\Support\SmsParts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * تنها راه فرستادن پیام نوبت/حساب به مشتری یا متخصص (بسته‌ی ۴): پوش اپ، پیامک، یا هر دو.
 *
 * - گیرنده‌ای که دستگاه فعال ندارد (اپ ندارد، حالت پلتفرم «خاموش»، ستون «اپ» رویداد خاموش): همان پیامک قبلی، بی‌تغییر.
 * - حالت «آزمایشی»: پیامک مثل قبل + پوش؛ فقط برای سنجش نرخ رسید.
 * - حالت «فعال» (تصمیم ۲ گزینه‌ی «ج»): پوش؛ اگر رسید در مهلت دسته‌ی پیام نیامد پیامک (PushSmsFallbackJob). پیام فوری به گیرنده‌ای
 *   که همه‌ی دستگاه‌هایش ناسالم‌اند (دو پوش آخر بی‌رسید) مستقیم پیامک هم می‌شود. دسته‌ی «اطلاع‌رسانی» فقط پوش است.
 *   سرور به FCM نرسید (قطعی) ← SendPushJob همان لحظه fallbackNow() را صدا می‌زند.
 *
 * پیامک‌ها از سهمیه‌ی همان سالن و با همان دسته‌ی امکان پلن (SMSService::send). پوش هیچ‌وقت پشت امکان پلن نیست (تصمیم ۲).
 */
class AppMessenger
{
    public function __construct(
        private readonly SMSService $sms,
        private readonly NotificationSettingService $settings,
        private readonly FcmClient $fcm,
        private readonly NtfyClient $ntfy,
    ) {}

    /** true = پوش صف شد یا پیامک رفت */
    public function deliver(Outgoing $o): bool
    {
        $smsAllowed = $o->phone && ($o->settingsEvent === null || $this->settings->isEnabled($o->settingsEvent, 'sms', $o->salonId));

        $devices = $this->devicesFor($o);
        if ($devices->isEmpty()) {
            return $smsAllowed ? $this->sms->send((string) $o->phone, $o->text, $o->salonId, $o->smsFeature) : false;
        }

        $mode = PushMode::current();
        $urgency = $o->urgency();

        $message = DB::transaction(function () use ($o, $devices, $mode, $urgency, $smsAllowed) {
            $message = PushMessage::create([
                'user_id' => $devices->first()->user_id,
                'salon_id' => $o->salonId,
                'app' => $devices->first()->app,
                'event_key' => $o->pushKey,
                'urgency' => $urgency->value,
                'mode' => $mode,
                'title' => mb_substr($o->title(), 0, 120),
                'body' => $o->text,
                'data' => $o->data ?: null,
                'sms_phone' => $smsAllowed ? $o->phone : null,
                'sms_text' => $smsAllowed ? $o->text : null,
                'sms_feature' => $o->smsFeature?->value,
                'sms_parts' => SmsParts::count($o->text),
            ]);

            foreach ($devices as $device) {
                PushDelivery::create(['push_message_id' => $message->id, 'push_device_id' => $device->id, 'status' => PushDelivery::QUEUED]);
            }

            if ($o->inbox) {
                $this->storeInInbox($o, $message, (int) $devices->first()->user_id);
            }

            return $message;
        });

        \App\Support\Queues::dispatchPush(new SendPushJob($message->id));

        if (! $smsAllowed) {
            return true;
        }

        if ($mode === PushMode::SHADOW) {
            $this->fallbackNow($message, 'shadow');

            return true;
        }

        if ($urgency === PushUrgency::Info) {
            return true;
        }

        if ($urgency === PushUrgency::Urgent && $this->allUnhealthy($devices)) {
            $this->fallbackNow($message, 'unhealthy');

            return true;
        }

        // صف sync (لوکال بدون worker) تأخیر ندارد: رسید هرگز پیش از job نمی‌رسد — همان پیامک قبلی
        if (config('queue.default') === 'sync') {
            $this->fallbackNow($message, 'no_receipt');

            return true;
        }

        $due = now()->addSeconds((int) $urgency->fallbackSeconds());
        $message->forceFill(['fallback_due_at' => $due])->save();
        dispatch(new PushSmsFallbackJob($message->id))->delay($due)->afterCommit();

        return true;
    }

    /**
     * پیامک پشتیبان همین حالا، اگر هنوز رسید نیامده و پیامکی نرفته. ادعای ارسال اتمی است (یک UPDATE شرطی)، پس job مهلت، قطعی
     * و حالت آزمایشی هرگز دو پیامک نمی‌فرستند.
     */
    public function fallbackNow(PushMessage $message, string $reason): bool
    {
        if (! $message->sms_phone || ! $message->sms_text) {
            return false;
        }

        $claimed = PushMessage::query()->whereKey($message->id)
            ->whereNull('sms_sent_at')
            ->when($reason !== 'shadow', fn ($q) => $q->whereNull('acked_at'))
            ->update(['sms_sent_at' => now(), 'sms_reason' => $reason]);

        if ($claimed !== 1) {
            return false;
        }

        $sent = $this->sms->send($message->sms_phone, $message->sms_text, $message->salon_id, Feature::tryFrom((string) $message->sms_feature));
        if (! $sent) {
            // پلن یا سهمیه‌ی سالن اجازه نداد: ثبت شود که پیامکی نرفت (آمار «پیامک صرفه‌جویی‌شده» را خراب نکند)
            PushMessage::query()->whereKey($message->id)->update(['sms_sent_at' => null, 'sms_reason' => 'sms_blocked']);
        }

        return $sent;
    }

    /**
     * پوش آزمایشی به یک دستگاه (صفحه‌ی «دریافت اعلان» اپ و دستور push:test) — بدون پیامک، در هر حالت پلتفرم جز «خاموش».
     */
    public function sendTest(PushDevice $device, ?string $text = null, bool $sync = false): PushMessage
    {
        $message = DB::transaction(function () use ($device, $text) {
            $message = PushMessage::create([
                'user_id' => $device->user_id,
                'salon_id' => $device->salon_id,
                'app' => $device->app,
                'event_key' => PushPolicy::TEST,
                'urgency' => PushUrgency::Info->value,
                'mode' => PushMode::current(),
                'title' => PushPolicy::title(PushPolicy::TEST),
                'body' => $text ?? 'اگر این را می‌بینید، اعلان‌های نوبت به این گوشی می‌رسد.',
                'data' => ['test' => 1],
            ]);
            PushDelivery::create(['push_message_id' => $message->id, 'push_device_id' => $device->id, 'status' => PushDelivery::QUEUED]);

            return $message;
        });

        $sync ? dispatch_sync(new SendPushJob($message->id)) : \App\Support\Queues::dispatchPush(new SendPushJob($message->id));

        return $message;
    }

    /** @return Collection<int, PushDevice> */
    public function devicesFor(Outgoing $o): Collection
    {
        if (PushMode::current() === PushMode::OFF) {
            return collect();
        }

        if ($o->settingsEvent !== null && ! $this->settings->isEnabled($o->settingsEvent, 'push', $o->salonId)) {
            return collect();
        }

        $userIds = self::userIdsFor($o->recipient);
        if ($userIds === []) {
            return collect();
        }

        $providers = array_values(array_filter([
            $this->fcm->configured() ? PushDevice::PROVIDER_FCM : null,
            $this->ntfy->configured() ? PushDevice::PROVIDER_NTFY : null,
        ]));
        if ($providers === []) {
            return collect();
        }

        return PushDevice::query()->active()
            ->whereIn('user_id', $userIds)
            ->whereIn('provider', $providers)
            // مشتری مختص یک سالن است؛ کادر در سالن پیام (توکن کادر سالن جاری خودش را دارد)
            ->when($o->salonId, fn ($q) => $q->where(fn ($w) => $w->whereNull('salon_id')->orWhere('salon_id', $o->salonId)))
            ->orderBy('id')
            ->get();
    }

    public function hasDevices(?object $recipient, ?int $salonId, ?string $settingsEvent): bool
    {
        return $this->devicesFor(new Outgoing($recipient, null, '', $salonId, null, $settingsEvent ?? PushPolicy::TEST, $settingsEvent))->isNotEmpty();
    }

    /** @return list<int> */
    public static function userIdsFor(?object $recipient): array
    {
        if ($recipient instanceof User) {
            return [(int) $recipient->id];
        }

        if ($recipient instanceof Specialist) {
            $userId = $recipient->user_id
                ?? User::query()->where('phone', $recipient->phone)->where('user_type', '!=', 'customer')->value('id');

            return $userId ? [(int) $userId] : [];
        }

        return [];
    }

    /** همه‌ی دستگاه‌ها ناسالم‌اند: هر کدام N ارسال آخرش (پذیرفته‌شده و قدیمی‌تر از مهلت فوری) بی‌رسید مانده است */
    private function allUnhealthy(Collection $devices): bool
    {
        $n = max(1, (int) config('push.unhealthy_after_unacked'));
        $before = now()->subSeconds((int) PushUrgency::Urgent->fallbackSeconds());

        foreach ($devices as $device) {
            $last = PushDelivery::query()
                ->where('push_device_id', $device->id)
                ->where('status', PushDelivery::ACCEPTED)
                ->where('accepted_at', '<=', $before)
                ->orderByDesc('id')
                ->limit($n)
                ->get(['acked_at', 'accepted_at']);

            if ($last->count() < $n || $last->contains(fn ($d) => $d->acked_at !== null)) {
                return false;
            }

            // رسیدی تازه‌تر از آن ارسال‌ها (مثلاً پوش آزمایشی) دستگاه را سالم می‌کند
            if ($device->last_receipt_at && $device->last_receipt_at->gt($last->max('accepted_at'))) {
                return false;
            }
        }

        return true;
    }

    private function storeInInbox(Outgoing $o, PushMessage $message, int $userId): void
    {
        try {
            UserNotification::create([
                'type' => 'App\\Notifications\\AppMessage',
                'notifiable_type' => (new User)->getMorphClass(),
                'notifiable_id' => $userId,
                'user_id' => $userId,
                'salon_id' => $o->salonId,
                'data' => array_filter([
                    'title' => $o->title(),
                    'message' => $o->text,
                    'booking_id' => $o->data['booking_id'] ?? null,
                    'course_id' => $o->data['course_id'] ?? null,
                    'push_message_id' => $message->id,
                ], fn ($v) => $v !== null),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Push inbox row failed', ['push_message_id' => $message->id, 'error' => $e->getMessage()]);
        }
    }
}
