<?php

namespace App\Services\Marketing;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Salon;
use App\Models\User;
use App\Models\WinbackMessage;
use App\Models\WinbackSetting;
use App\Services\Links\ShortLinkService;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Sms\SmsTemplateStore;
use App\Support\SmsParts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * «پیگیری خودکار مشتریانی که مدتی نیامده‌اند» (تصمیم ۲۰۲۶-۱۰-۱۴).
 *
 * مشتری غایب: آخرین نوبت «انجام‌شده»اش در همین سالن بیش از lapse_days روز پیش است، نوبت فعال آینده ندارد، شماره دارد و از
 * پیامک‌های تبلیغاتی انصراف نداده است. برای هر «آخرین مراجعه» فقط یک پیامک (winback_messages یکتا روی user_id + last_visit_at)؛
 * اگر برگشت و دوباره مدتی نیامد، دوره‌ی تازه و پیامک تازه.
 *
 * پنجره‌ی WINDOW_DAYS: فقط کسانی که در همین بازه از مرز غیبت گذشته‌اند پیامک می‌گیرند — روشن کردن قابلیت، همه‌ی مشتری‌های
 * چندساله‌ی قدیمی را یک‌جا پیامک نمی‌کند (سهمیه‌ی سالن یک‌شبه تمام نشود) و اگر دستور یک روز اجرا نشد، فردا جبران می‌شود.
 *
 * پیامک از سهمیه‌ی همان سالن (SMSService با salon_id) و فقط تا وقتی که ۲۰٪ سهمیه‌ی ماه برای پیامک‌های نوبت بماند؛ اولین پیامکی
 * که جا نداشت یا ارسال نشد، کار آن سالن را تا فردا متوقف می‌کند. کد تخفیف فقط بعد از ارسال موفق ذخیره می‌شود.
 */
class WinbackService
{
    public const WINDOW_DAYS = 30;

    public const ACTIVE_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    public const RESERVE_RATIO = 0.2;

    public function __construct(private readonly SMSService $sms, private readonly ShortLinkService $links) {}

    /** همه‌ی سالن‌های فعال با قابلیت روشن؛ خروجی: تعداد پیامک فرستاده‌شده برای هر سالن */
    public function runAll(): array
    {
        // فقط سالن‌هایی که «پیگیری مشتریان غایب» در پلنشان هست — در خود کوئری (بسته‌ی پلن‌ها)
        $settings = WinbackSetting::withoutGlobalScope('salon')->where('enabled', true)
            ->whereIn('salon_id', Salon::withoutGlobalScopes()->withFeature(\App\Support\Features\Feature::Winback)->select('id'))
            ->get();
        $sent = [];

        foreach ($settings as $setting) {
            $salon = Salon::withoutGlobalScopes()->find($setting->salon_id);
            if (! $salon || ! $salon->subscription_ends_at || ! $salon->hasActiveSubscription()) {
                continue;
            }

            $sent[$salon->id] = app(CurrentSalon::class)->withSalon($salon, fn () => $this->runForSalon($salon, $setting));
        }

        return $sent;
    }

    public function runForSalon(Salon $salon, WinbackSetting $setting): int
    {
        $sent = 0;

        foreach ($this->candidates($salon, $setting) as $customer) {
            if ($this->send($salon, $setting, $customer, Carbon::parse($customer->getAttribute('last_visit_at'))) === null) {
                break; // سهمیه‌ی پیامک سالن جا ندارد
            }
            $sent++;
        }

        return $sent;
    }

    /**
     * مشتری‌هایی که امروز باید پیامک بگیرند (ستون اضافه‌ی last_visit_at = آخرین نوبت انجام‌شده).
     *
     * @return Collection<int, User>
     */
    public function candidates(Salon $salon, WinbackSetting $setting, ?int $limit = null): Collection
    {
        $cutoff = now()->subDays($setting->lapse_days);
        $windowStart = $cutoff->copy()->subDays(self::WINDOW_DAYS);

        $lastVisits = DB::table('bookings')
            ->select('user_id', DB::raw('MAX(booking_time) AS last_visit_at'))
            ->where('salon_id', $salon->id)
            ->where('status', 'completed')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('MAX(booking_time) <= ? AND MAX(booking_time) > ?', [$cutoff->format('Y-m-d H:i:s'), $windowStart->format('Y-m-d H:i:s')]);

        return User::query()
            ->joinSub($lastVisits, 'lv', 'lv.user_id', '=', 'users.id')
            ->select('users.*', 'lv.last_visit_at')
            ->where('users.user_type', 'customer')
            ->where('users.salon_id', $salon->id)
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->whereNull('users.marketing_sms_opted_out_at')
            ->whereNotExists(fn ($q) => $q->from('bookings')
                ->whereColumn('bookings.user_id', 'users.id')
                ->where('bookings.salon_id', $salon->id)
                ->whereIn('bookings.status', self::ACTIVE_STATUSES)
                ->where('bookings.booking_time', '>=', now()))
            ->whereNotExists(fn ($q) => $q->from('winback_messages')
                ->whereColumn('winback_messages.user_id', 'users.id')
                ->whereColumn('winback_messages.last_visit_at', 'lv.last_visit_at'))
            ->orderBy('lv.last_visit_at')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /** true = فرستاده شد؛ null = سهمیه‌ی سالن (با ذخیره‌ی پیامک‌های نوبت) کافی نیست یا ارسال نشد — تا فردا ادامه نده */
    private function send(Salon $salon, WinbackSetting $setting, User $customer, Carbon $lastVisit): ?bool
    {
        $codeValue = $setting->hasDiscount() ? $this->newCodeValue($salon) : null;
        $expires = now()->addDays($setting->discount_valid_days)->endOfDay();
        $linksExpire = $codeValue ? $expires->copy()->addDay() : now()->addDays(30);

        $text = SmsTemplateStore::render($salon->id, 'winback', [
            'سالن' => (string) $salon->smsName(),
            'تخفیف' => $codeValue ? sprintf('کد %s: %s تخفیف تا %s. ', $codeValue, $setting->discountLabel(), verta($expires)->format('Y/m/d')) : '',
            'لینک_رزرو' => fn () => $this->links->shorten($salon->publicUrl(), $linksExpire),
            'لینک_انصراف' => fn () => $this->links->shorten(self::unsubscribeUrl($salon, $customer), now()->addDays(180)),
        ]);

        if (! $this->hasRoomFor($salon, SmsParts::count($text)) || ! $this->sms->send($customer->phone, $text, $salon->id, \App\Support\Features\Feature::Winback)) {
            return null;
        }

        DB::transaction(function () use ($salon, $setting, $customer, $lastVisit, $codeValue, $expires) {
            $code = $codeValue ? $this->createCode($salon, $setting, $customer, $codeValue, $expires) : null;

            WinbackMessage::create([
                'salon_id' => $salon->id,
                'user_id' => $customer->id,
                'last_visit_at' => $lastVisit,
                'discount_code_id' => $code?->id,
                'sent_at' => now(),
            ]);
        });

        return true;
    }

    /**
     * پیامک تبلیغاتی نباید جای پیامک‌های نوبت را بگیرد: بعد از این پیامک دست‌کم RESERVE_RATIO از سهمیه‌ی ماه (به‌علاوه‌ی اعتبار
     * خریده‌شده) باید بماند.
     */
    private function hasRoomFor(Salon $salon, int $parts): bool
    {
        $quota = app(SmsQuotaService::class);
        $available = $quota->remaining($salon) + $quota->credit($salon);

        return $available - $parts >= (int) ceil($quota->quotaFor($salon) * self::RESERVE_RATIO);
    }

    public function newCodeValue(Salon $salon): string
    {
        do {
            $value = 'BK'.substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 5);
        } while (DiscountCode::withoutGlobalScope('salon')->where('salon_id', $salon->id)->where('code', $value)->exists());

        return $value;
    }

    /**
     * کد یک‌بارمصرف مخصوص همین مشتری. بی‌صدا ذخیره می‌شود: پیامک «کد تخفیف صادر شد» DiscountCodeObserver نباید جدا برود.
     * مشترک با کمپین دستی (بسته‌ی ج) — هر دو فیلدهای discount_* هم‌نام دارند.
     */
    public function createCode(Salon $salon, WinbackSetting|\App\Models\WinbackCampaign $setting, User $customer, string $value, Carbon $expires): DiscountCode
    {
        $code = new DiscountCode([
            'code' => $value,
            'type' => $setting->discount_type,
            'amount' => $setting->discount_amount,
            'max_amount' => $setting->discount_type === 'percentage' ? $setting->discount_max_amount : null,
            'max_uses' => 1,
            'user_id' => $customer->id,
            'expires_at' => $expires,
            'is_active' => true,
        ]);
        $code->salon_id = $salon->id;
        $code->saveQuietly();

        return $code;
    }

    /** لینک امضاشده‌ی انصراف (بی‌تاریخ؛ بدون ورود) */
    public static function unsubscribeUrl(Salon $salon, User $customer): string
    {
        return URL::signedRoute('sms.unsubscribe', ['salon_slug' => $salon->slug, 'customer' => $customer->id]);
    }

    /** آمار صفحه‌ی مدیر: پیامک‌های N روز اخیر و چند نفرشان بعد از پیامک نوبت گرفتند */
    public function stats(int $salonId, int $days = 30): array
    {
        $since = now()->subDays($days);
        $sent = WinbackMessage::withoutGlobalScope('salon')->where('salon_id', $salonId)->where('sent_at', '>=', $since);

        $returned = (clone $sent)->whereExists(fn ($q) => $q->from('bookings')
            ->whereColumn('bookings.user_id', 'winback_messages.user_id')
            ->where('bookings.salon_id', $salonId)
            ->whereColumn('bookings.created_at', '>=', 'winback_messages.sent_at')
            ->where('bookings.status', '!=', 'cancelled'))->count();

        return ['sent' => $sent->count(), 'returned' => $returned];
    }

    public static function returnedAfter(WinbackMessage $message): bool
    {
        return Booking::withoutGlobalScope('salon')
            ->where('salon_id', $message->salon_id)
            ->where('user_id', $message->user_id)
            ->where('created_at', '>=', $message->sent_at)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}
