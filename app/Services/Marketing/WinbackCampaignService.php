<?php

namespace App\Services\Marketing;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Models\WinbackCampaign;
use App\Models\WinbackCampaignRecipient;
use App\Models\WinbackSetting;
use App\Services\Links\ShortLinkService;
use App\Services\Sms\SmsCreditService;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Sms\SmsTemplates;
use App\Support\SmsParts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * بسته‌ی ج «ارسال دستی به مشتری‌های قدیمی» (۲۰۲۶-۱۰-۱۴؛ تصمیم‌های ۱۳ و ۱۴).
 *
 * گیرنده‌ها: همان قاعده‌های WinbackService به‌جز پنجره — آخرین نوبت انجام‌شده در همین سالن بین «پایان پنجره‌ی پیگیری خودکار»
 * (lapse_days + 30) و حداکثر MAX_AGE_DAYS (۲ سال، تصمیم ۱۳) پیش؛ بدون نوبت فعال آینده، با شماره، بدون انصراف، و خنک‌شدن ۹۰ روزه
 * (نه پیامک خودکار و نه کمپین در ۹۰ روز گذشته). جدیدترها اول (اگر سهمیه برای همه جا ندارد، «فقط N نفر جدیدتر»).
 *
 * سقف‌ها (تصمیم ۱۴): حداکثر ۵۰۰ گیرنده در هر کمپین، حداکثر ۲ کمپین در هر ۳۰ روز، فقط یک کمپین فعال در هر زمان.
 *
 * ارسال: دستور winback:campaigns هر دقیقه، حداکثر BATCH_PER_MINUTE پیامک برای هر کمپین، فقط ۹ تا ۲۰ تهران. پیش از هر پیامک انصراف،
 * نوبت آینده، شماره و خنک‌شدن دوباره سنجیده می‌شود؛ قاعده‌ی ۲۰٪ سهمیه مثل خودکار (نرسید ← توقف با دلیل quota). هر گیرنده با claim
 * اتمی (pending → sending) فقط یک بار؛ کد تخفیف شخصی فقط بعد از ارسال موفق. توقف/ادامه/لغو در میانه.
 */
class WinbackCampaignService
{
    public const MAX_AGE_DAYS = 730;

    public const MAX_RECIPIENTS = 500;

    public const MAX_PER_30_DAYS = 2;

    public const COOLDOWN_DAYS = 90;

    public const BATCH_PER_MINUTE = 20;

    public const SEND_FROM_HOUR = 9;

    public const SEND_UNTIL_HOUR = 20;

    public const RESULT_DAYS = 30;

    public const TIMEZONE = 'Asia/Tehran';

    /** جای‌خالی‌های مجاز متن کمپین */
    public const PLACEHOLDERS = [
        'سالن' => ['نام کوتاه پیامکی سالن', 'سالن رزگلد'],
        'مشتری' => ['نام مشتری', 'مریم'],
        'تخفیف' => ['«کد X: مقدار تخفیف تا تاریخ.» اگر کد تخفیف روشن است', 'کد BKQ7M2X: 15٪ تخفیف تا 1405/09/30. '],
        'لینک_رزرو' => ['لینک کوتاه رزرو سالن', 'mahru.ir/b/r7p2'],
        'لینک_انصراف' => ['لینک کوتاه انصراف از این پیامک‌ها', 'mahru.ir/b/u9z1'],
    ];

    public function __construct(
        private readonly SMSService $sms,
        private readonly ShortLinkService $links,
        private readonly SmsQuotaService $quota,
        private readonly WinbackService $winback,
    ) {}

    /** کمترین «روز از آخرین مراجعه»: جایی که پنجره‌ی پیگیری خودکار تمام می‌شود */
    public function minFromDays(Salon $salon): int
    {
        return WinbackSetting::forSalon($salon->id)->lapse_days + WinbackService::WINDOW_DAYS;
    }

    // ─── گیرنده‌ها ──────────────────────────────────────────────────

    /**
     * @param  array{last_visit_from_days: int, last_visit_to_days: int, service_ids?: ?array, min_visits?: ?int}  $filters
     */
    public function candidatesQuery(Salon $salon, array $filters): Builder
    {
        $newest = now()->subDays((int) $filters['last_visit_from_days']);
        $oldest = now()->subDays((int) $filters['last_visit_to_days']);

        $lastVisits = DB::table('bookings')
            ->select('user_id', DB::raw('MAX(booking_time) AS last_visit_at'), DB::raw('COUNT(*) AS visits'))
            ->where('salon_id', $salon->id)
            ->where('status', 'completed')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('MAX(booking_time) <= ? AND MAX(booking_time) >= ?', [$newest->format('Y-m-d H:i:s'), $oldest->format('Y-m-d H:i:s')]);

        if (! empty($filters['min_visits'])) {
            $lastVisits->havingRaw('COUNT(*) >= ?', [(int) $filters['min_visits']]);
        }

        $serviceIds = array_values(array_filter(array_map('intval', (array) ($filters['service_ids'] ?? []))));
        $cooldown = now()->subDays(self::COOLDOWN_DAYS);

        return User::query()
            ->joinSub($lastVisits, 'lv', 'lv.user_id', '=', 'users.id')
            ->select('users.*', 'lv.last_visit_at', 'lv.visits')
            ->where('users.user_type', 'customer')
            ->where('users.salon_id', $salon->id)
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->whereNull('users.marketing_sms_opted_out_at')
            ->when($serviceIds !== [], fn ($q) => $q->whereExists(fn ($e) => $e->from('bookings')
                ->whereColumn('bookings.user_id', 'users.id')
                ->where('bookings.salon_id', $salon->id)
                ->where('bookings.status', 'completed')
                ->whereIn('bookings.service_id', $serviceIds)))
            ->whereNotExists(fn ($q) => $q->from('bookings')
                ->whereColumn('bookings.user_id', 'users.id')
                ->where('bookings.salon_id', $salon->id)
                ->whereIn('bookings.status', WinbackService::ACTIVE_STATUSES)
                ->where('bookings.booking_time', '>=', now()))
            ->whereNotExists(fn ($q) => $q->from('winback_messages')
                ->whereColumn('winback_messages.user_id', 'users.id')
                ->where('winback_messages.sent_at', '>=', $cooldown))
            ->whereNotExists(fn ($q) => $q->from('winback_campaign_recipients')
                ->whereColumn('winback_campaign_recipients.user_id', 'users.id')
                ->where('winback_campaign_recipients.status', 'sent')
                ->where('winback_campaign_recipients.sent_at', '>=', $cooldown))
            ->orderByDesc('lv.last_visit_at')
            ->orderBy('users.id');
    }

    /** @return Collection<int, User> */
    public function candidates(Salon $salon, array $filters, ?int $limit = null): Collection
    {
        return $this->candidatesQuery($salon, $filters)->when($limit, fn ($q) => $q->limit($limit))->get();
    }

    // ─── پیش‌نمایش و قاعده‌ی ۲۰٪ ────────────────────────────────────

    /**
     * تعداد، نمونه‌ی متن با یک مشتری واقعی، قطعه‌ها و جای سهمیه.
     *
     * @return array{count: int, capped: int, sample: string, parts: int, total_parts: int, available: int, reserve: int, allowed: int, after: int, buy_parts: int, buy_price: int, packs: int[]}
     */
    public function preview(Salon $salon, array $filters, string $message, ?WinbackCampaign $discount = null): array
    {
        $count = $this->candidatesQuery($salon, $filters)->count();
        $capped = min($count, self::MAX_RECIPIENTS, (int) ($filters['max_recipients'] ?? self::MAX_RECIPIENTS));
        $first = $this->candidates($salon, $filters, 1)->first();

        $sample = $this->render($salon, $message, $first, $discount, preview: true);
        $parts = SmsParts::count($sample);
        $available = $this->quota->remaining($salon) + $this->quota->credit($salon);
        $reserve = (int) ceil($this->quota->quotaFor($salon) * WinbackService::RESERVE_RATIO);
        $room = max(0, $available - $reserve);
        $allowed = $parts > 0 ? intdiv($room, $parts) : 0;
        $needed = $capped * $parts;
        $credit = app(SmsCreditService::class);
        $missing = max(0, $needed - $room);
        $pack = collect($credit->packs())->sort()->first(fn ($p) => $p >= $missing) ?? (collect($credit->packs())->max() ?? 0);

        return [
            'count' => $count,
            'capped' => $capped,
            'sample' => $sample,
            'parts' => $parts,
            'total_parts' => $needed,
            'available' => $available,
            'reserve' => $reserve,
            'allowed' => min($allowed, $capped),
            'after' => $available - min($needed, $room),
            'buy_parts' => $missing > 0 ? (int) $pack : 0,
            'buy_price' => $missing > 0 ? $credit->price((int) $pack) : 0,
            'packs' => $credit->packs(),
        ];
    }

    /** چرا الان کمپین تازه ساخته نمی‌شود؛ null = می‌شود */
    public function limitsError(Salon $salon): ?string
    {
        $base = WinbackCampaign::withoutGlobalScope('salon')->where('salon_id', $salon->id);

        if ((clone $base)->whereIn('status', WinbackCampaign::ACTIVE)->exists()) {
            return 'یک کمپین فعال دارید؛ اول آن تمام، یا لغو شود.';
        }
        // کمپین لغوشده‌ای که هیچ پیامکی نفرستاد شمرده نمی‌شود
        $recent = (clone $base)->where('created_at', '>=', now()->subDays(30))
            ->where(fn ($q) => $q->where('status', '!=', 'cancelled')->orWhere('sent', '>', 0))->count();
        if ($recent >= self::MAX_PER_30_DAYS) {
            return sprintf('در هر ۳۰ روز حداکثر %s کمپین؛ برای محافظت از خط پیامک سالن در برابر گزارش اسپم.', to_persian_num((string) self::MAX_PER_30_DAYS));
        }

        return null;
    }

    /** پیام خطای متن کمپین؛ null = درست */
    public function messageError(string $message): ?string
    {
        $used = SmsTemplates::placeholdersIn($message);

        return match (true) {
            trim($message) === '' => 'متن پیامک خالی است.',
            mb_strlen($message) > SmsTemplates::MAX_LENGTH => sprintf('متن حداکثر %s نویسه.', to_persian_num((string) SmsTemplates::MAX_LENGTH)),
            ! in_array('لینک_انصراف', $used, true) => 'جای‌خالی {لینک_انصراف} در متن لازم است (پیامک تبلیغاتی بدون راه انصراف نمی‌رود).',
            ($unknown = array_diff($used, array_keys(self::PLACEHOLDERS))) !== [] => 'جای‌خالی ناشناخته: {'.implode('}، {', $unknown).'}',
            default => null,
        };
    }

    // ─── ساخت ───────────────────────────────────────────────────────

    /**
     * کمپین و فهرست ثابت گیرنده‌ها (جدیدترها اول، تا سقف). زیر قفل ردیف سالن تا دو کلیک هم‌زمان دو کمپین نسازند.
     *
     * @throws \DomainException پیام فارسی
     */
    public function create(Salon $salon, User $by, array $data): WinbackCampaign
    {
        return DB::transaction(function () use ($salon, $by, $data) {
            Salon::withoutGlobalScopes()->lockForUpdate()->find($salon->id);

            if ($error = $this->limitsError($salon)) {
                throw new \DomainException($error);
            }
            if ($error = $this->messageError((string) $data['message'])) {
                throw new \DomainException($error);
            }

            $max = min(self::MAX_RECIPIENTS, (int) ($data['max_recipients'] ?? self::MAX_RECIPIENTS));
            $recipients = $this->candidates($salon, $data, $max);
            if ($recipients->isEmpty()) {
                throw new \DomainException('با این فیلترها مشتری‌ای برای پیامک نیست.');
            }

            $campaign = new WinbackCampaign([
                'name' => $data['name'],
                'created_by' => $by->id,
                'last_visit_from_days' => (int) $data['last_visit_from_days'],
                'last_visit_to_days' => (int) $data['last_visit_to_days'],
                'service_ids' => array_values(array_filter(array_map('intval', (array) ($data['service_ids'] ?? [])))) ?: null,
                'min_visits' => $data['min_visits'] ?? null,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_amount' => ($data['discount_type'] ?? null) ? $data['discount_amount'] : null,
                'discount_max_amount' => ($data['discount_type'] ?? null) === 'percentage' ? ($data['discount_max_amount'] ?? null) : null,
                'discount_valid_days' => (int) ($data['discount_valid_days'] ?? 14),
                'message' => trim((string) $data['message']),
                'status' => 'scheduled',
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'max_recipients' => $max,
                'targeted' => $recipients->count(),
            ]);
            $campaign->salon_id = $salon->id;
            $campaign->save();

            $now = now();
            WinbackCampaignRecipient::withoutGlobalScope('salon')->insert($recipients->map(fn (User $u) => [
                'salon_id' => $salon->id,
                'winback_campaign_id' => $campaign->id,
                'user_id' => $u->id,
                'last_visit_at' => Carbon::parse($u->getAttribute('last_visit_at'))->format('Y-m-d H:i:s'),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            activity()->performedOn($campaign)->causedBy($by)->withProperties(['targeted' => $recipients->count()])->log('winback_campaign_created');

            return $campaign;
        });
    }

    // ─── ارسال ──────────────────────────────────────────────────────

    public function insideSendingHours(?Carbon $at = null): bool
    {
        $hour = ($at ?? now())->copy()->setTimezone(self::TIMEZONE)->hour;

        return $hour >= self::SEND_FROM_HOUR && $hour < self::SEND_UNTIL_HOUR;
    }

    /** همه‌ی کمپین‌های آماده (دستور هر دقیقه)؛ خروجی: پیامک فرستاده‌شده برای هر کمپین */
    public function runDue(): array
    {
        if (! $this->insideSendingHours()) {
            return [];
        }

        $campaigns = WinbackCampaign::withoutGlobalScope('salon')
            ->where(fn ($q) => $q->where('status', 'sending')->orWhere(fn ($s) => $s->where('status', 'scheduled')->where('scheduled_at', '<=', now())))
            ->orderBy('id')->get();

        $sent = [];
        foreach ($campaigns as $campaign) {
            $salon = Salon::withoutGlobalScopes()->find($campaign->salon_id);
            if (! $salon) {
                continue;
            }
            $sent[$campaign->id] = app(CurrentSalon::class)->withSalon($salon, fn () => $this->process($salon, $campaign));
        }

        return $sent;
    }

    /** یک دسته از گیرنده‌های یک کمپین */
    public function process(Salon $salon, WinbackCampaign $campaign, int $batch = self::BATCH_PER_MINUTE): int
    {
        if (! $this->insideSendingHours()) {
            return 0;
        }
        if (! $salon->hasActiveSubscription()) {
            $this->pause($campaign, 'subscription');

            return 0;
        }
        if (! $salon->hasFeature(Feature::WinbackManual)) {
            $this->pause($campaign, 'feature');

            return 0;
        }

        if ($campaign->status === 'scheduled') {
            $campaign->forceFill(['status' => 'sending', 'started_at' => $campaign->started_at ?? now(), 'pause_reason' => null])->save();
        }

        $sent = 0;
        $ids = WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->where('status', 'pending')
            ->orderBy('id')->limit($batch)->pluck('id');

        foreach ($ids as $id) {
            // claim اتمی: اجرای هم‌زمان یا تکراری این گیرنده را دوباره نمی‌فرستد
            if (WinbackCampaignRecipient::query()->whereKey($id)->where('status', 'pending')->update(['status' => 'sending', 'updated_at' => now()]) !== 1) {
                continue;
            }
            $recipient = WinbackCampaignRecipient::query()->with('user')->find($id);
            $result = $this->sendOne($salon, $campaign, $recipient);

            if ($result === 'sent') {
                $sent++;
            } elseif ($result !== 'skipped') {
                $this->pause($campaign, $result);
                break;
            }
            if ($campaign->fresh()->status !== 'sending') {
                break; // مدیر وسط دسته متوقف/لغو کرد
            }
        }

        $this->finishIfDone($campaign);

        return $sent;
    }

    /** @return string sent | skipped | quota | send_failed */
    private function sendOne(Salon $salon, WinbackCampaign $campaign, WinbackCampaignRecipient $recipient): string
    {
        $user = $recipient->user;
        $reason = match (true) {
            ! $user || ! $user->phone => 'no_phone',
            $user->marketing_sms_opted_out_at !== null => 'opted_out',
            $this->hasFutureBooking($salon, $user) => 'booked',
            $this->recentlyTexted($user, $campaign) => 'recently_texted',
            default => null,
        };
        if ($reason) {
            $recipient->forceFill(['status' => 'skipped', 'skip_reason' => $reason])->save();
            WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->increment('skipped');

            return 'skipped';
        }

        $codeValue = $campaign->hasDiscount() ? $this->winback->newCodeValue($salon) : null;
        $expires = now()->addDays($campaign->discount_valid_days)->endOfDay();
        $text = $this->render($salon, $campaign->message, $user, $campaign, codeValue: $codeValue, expires: $expires);
        $parts = SmsParts::count($text);

        if (! $this->hasRoomFor($salon, $parts)) {
            $recipient->forceFill(['status' => 'pending'])->save();

            return 'quota';
        }
        if (! $this->sms->send($user->phone, $text, $salon->id, Feature::WinbackManual)) {
            $recipient->forceFill(['status' => 'pending'])->save();

            return 'send_failed';
        }

        DB::transaction(function () use ($salon, $campaign, $recipient, $user, $codeValue, $expires, $parts) {
            $code = $codeValue ? $this->winback->createCode($salon, $campaign, $user, $codeValue, $expires) : null;
            $recipient->forceFill(['status' => 'sent', 'sent_at' => now(), 'sms_parts' => $parts, 'discount_code_id' => $code?->id])->save();
            WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->update([
                'sent' => DB::raw('sent + 1'),
                'parts_used' => DB::raw('parts_used + '.(int) $parts),
                'updated_at' => now(),
            ]);
        });

        return 'sent';
    }

    private function render(Salon $salon, string $message, ?User $user, ?WinbackCampaign $discount, bool $preview = false, ?string $codeValue = null, ?Carbon $expires = null): string
    {
        $expires ??= now()->addDays($discount?->discount_valid_days ?? 14)->endOfDay();
        $hasDiscount = $discount?->hasDiscount() ?? false;
        $code = $codeValue ?? ($hasDiscount ? 'BKQ7M2X' : null);

        return SmsTemplates::fill($message, [
            'سالن' => (string) $salon->smsName(),
            'مشتری' => (string) ($user ? mb_substr(trim((string) $user->name), 0, 25) : 'مریم'),
            'تخفیف' => $hasDiscount && $code ? sprintf('کد %s: %s تخفیف تا %s. ', $code, $discount->discountLabel(), verta($expires)->format('Y/m/d')) : '',
            'لینک_رزرو' => $preview ? 'mahru.ir/b/r7p2xk' : fn () => $this->links->shorten($salon->publicUrl(), $expires->copy()->addDay()),
            'لینک_انصراف' => $preview || ! $user ? 'mahru.ir/b/u9z1ab' : fn () => $this->links->shorten(WinbackService::unsubscribeUrl($salon, $user), now()->addDays(180)),
        ]);
    }

    private function hasRoomFor(Salon $salon, int $parts): bool
    {
        $available = $this->quota->remaining($salon) + $this->quota->credit($salon);

        return $available - $parts >= (int) ceil($this->quota->quotaFor($salon) * WinbackService::RESERVE_RATIO);
    }

    private function hasFutureBooking(Salon $salon, User $user): bool
    {
        return Booking::withoutGlobalScope('salon')->where('salon_id', $salon->id)->where('user_id', $user->id)
            ->whereIn('status', WinbackService::ACTIVE_STATUSES)->where('booking_time', '>=', now())->exists();
    }

    private function recentlyTexted(User $user, WinbackCampaign $campaign): bool
    {
        $since = now()->subDays(self::COOLDOWN_DAYS);

        return DB::table('winback_messages')->where('user_id', $user->id)->where('sent_at', '>=', $since)->exists()
            || DB::table('winback_campaign_recipients')->where('user_id', $user->id)->where('winback_campaign_id', '!=', $campaign->id)
                ->where('status', 'sent')->where('sent_at', '>=', $since)->exists();
    }

    private function finishIfDone(WinbackCampaign $campaign): void
    {
        $open = WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->whereIn('status', ['pending', 'sending'])->exists();
        if (! $open) {
            WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->whereIn('status', ['sending', 'scheduled'])
                ->update(['status' => 'done', 'finished_at' => now(), 'updated_at' => now()]);
        }
    }

    // ─── توقف / ادامه / لغو ────────────────────────────────────────

    public function pause(WinbackCampaign $campaign, string $reason = 'manual'): void
    {
        WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->whereIn('status', ['scheduled', 'sending'])
            ->update(['status' => 'paused', 'pause_reason' => $reason, 'updated_at' => now()]);
    }

    public function resume(WinbackCampaign $campaign): bool
    {
        return WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->where('status', 'paused')
            ->update(['status' => 'sending', 'pause_reason' => null, 'started_at' => $campaign->started_at ?? now(), 'updated_at' => now()]) === 1;
    }

    public function cancel(WinbackCampaign $campaign, User $by): bool
    {
        return DB::transaction(function () use ($campaign, $by) {
            $changed = WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->whereIn('status', WinbackCampaign::ACTIVE)
                ->update(['status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now()]) === 1;
            if ($changed) {
                $skipped = WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->where('status', 'pending')
                    ->update(['status' => 'skipped', 'skip_reason' => 'cancelled', 'updated_at' => now()]);
                WinbackCampaign::withoutGlobalScope('salon')->whereKey($campaign->id)->increment('skipped', $skipped);
                activity()->performedOn($campaign)->causedBy($by)->log('winback_campaign_cancelled');
            }

            return $changed;
        });
    }

    // ─── نتیجه ──────────────────────────────────────────────────────

    /** @return array{booked: int, revenue: float, opted_out: int, codes_used: int} */
    public function results(WinbackCampaign $campaign): array
    {
        $sent = WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->where('status', 'sent')
            ->with(['user:id,marketing_sms_opted_out_at', 'discountCode:id,used_count'])->get();

        $booked = 0;
        $revenue = 0.0;
        foreach ($sent as $r) {
            $bookings = Booking::query()->with('service:id,price')->where('user_id', $r->user_id)->where('status', '!=', 'cancelled')
                ->where('created_at', '>', $r->sent_at)->where('created_at', '<=', $r->sent_at->copy()->addDays(self::RESULT_DAYS))->get();
            if ($bookings->isNotEmpty()) {
                $booked++;
                $revenue += (float) $bookings->sum(fn (Booking $b) => (float) ($b->service?->price ?? 0));
            }
        }

        return [
            'booked' => $booked,
            'revenue' => $revenue,
            'opted_out' => $sent->filter(fn ($r) => $r->user?->marketing_sms_opted_out_at && $r->user->marketing_sms_opted_out_at->gte($r->sent_at))->count(),
            'codes_used' => $sent->filter(fn ($r) => ($r->discountCode?->used_count ?? 0) > 0)->count(),
        ];
    }
}
