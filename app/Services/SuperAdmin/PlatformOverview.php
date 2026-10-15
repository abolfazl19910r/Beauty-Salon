<?php

namespace App\Services\SuperAdmin;

use App\Models\BotLink;
use App\Models\DemoRequest;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Models\SmsCreditPurchase;
use App\Models\SupportTicket;
use App\Services\Bot\BotClient;
use App\Services\Sms\SmsQuotaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ داده‌هایی که پیاده شده بودند ولی در پنل ابرمدیر دیده نمی‌شدند (۲۰۲۶-۱۰-۱۵): درآمد بسته‌ی پیامک، مصرف پیامک همه‌ی سالن‌ها،
 * پرداخت‌های پلتفرمِ نیازمند بررسی، کارهای ناموفق صف، وضعیت ربات و شمارنده‌های هشدار داشبورد.
 *
 * همه‌ی شمارش‌ها در SQL و بدون global scope سالن (پنل ابرمدیر هیچ سالن جاری‌ای ندارد).
 */
class PlatformOverview
{
    /** پرداخت‌هایی که پولشان به درگاه خود پلتفرم می‌رود (نه درگاه سالن) — رسیدگی‌شان با ابرمدیر است، نه مدیر سالن. */
    public const PLATFORM_PURPOSES = ['subscription', 'sms_pack'];

    public function __construct(private readonly SmsQuotaService $quota) {}

    /** @return array<string, int> */
    public function dashboard(?Carbon $now = null): array
    {
        $now ??= now();
        $monthStart = $now->copy()->startOfMonth();

        return [
            'subscription_revenue_month' => (int) Invoice::withoutGlobalScopes()->where('status', 'paid')
                ->where('paid_at', '>=', $monthStart)->sum('amount'),
            'sms_revenue_month' => (int) SmsCreditPurchase::query()->where('status', 'paid')->where('source', 'online')
                ->where('paid_at', '>=', $monthStart)->sum('amount'),
            'new_salons_month' => Salon::withoutGlobalScopes()->where('created_at', '>=', $monthStart)->count(),
            'trial_salons' => Salon::withoutGlobalScopes()->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', $now)
                ->whereDoesntHave('invoices', fn ($q) => $q->withoutGlobalScopes()->where('status', 'paid'))->count(),
            'sms_parts_month' => (int) SalonSmsUsage::query()->where('period', $this->quota->currentPeriod())
                ->sum(DB::raw('used_count + credit_used')),
            'otp_month' => (int) SalonSmsUsage::query()->where('period', $this->quota->currentPeriod())->sum('otp_count'),
            'open_tickets' => SupportTicket::withoutGlobalScopes()->where('status', 'open')->count(),
            'open_demo_requests' => DemoRequest::query()->open()->count(),
            'payments_attention' => $this->attentionQuery()->count(),
            'failed_jobs' => $this->failedJobsCount(),
        ];
    }

    /** تراکنش‌های اشتراک/بسته‌ی پیامک که خودکارسازی کنار کشیده و آدم باید ببیند. */
    public function attentionQuery()
    {
        return PaymentTransaction::query()->whereIn('purpose', self::PLATFORM_PURPOSES)->where('needs_attention', true);
    }

    public function failedJobsCount(): int
    {
        return Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
    }

    /**
     * مصرف پیامک ماه جاری همه‌ی سالن‌ها، پرمصرف‌ترین بالا.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function smsUsage(?string $period = null)
    {
        $period ??= $this->quota->currentPeriod();
        $usage = SalonSmsUsage::query()->where('period', $period)->get()->keyBy('salon_id');

        return Salon::withoutGlobalScopes()->with('plan')->orderBy('name')->get()
            ->map(function (Salon $salon) use ($usage) {
                $row = $usage->get($salon->id);
                $quota = $this->quota->quotaFor($salon);
                $used = (int) ($row->used_count ?? 0);

                return [
                    'salon' => $salon,
                    'quota' => $quota,
                    'used' => $used,
                    'credit_used' => (int) ($row->credit_used ?? 0),
                    'otp' => (int) ($row->otp_count ?? 0),
                    'credit_left' => (int) $salon->sms_credit,
                    'percent' => $quota > 0 ? (int) min(100, round(100 * $used / $quota)) : 0,
                ];
            })
            ->sortByDesc(fn ($r) => $r['used'] + $r['credit_used'])
            ->values();
    }

    /** @return array{enabled: list<string>, links: array<string, int>, usernames: array<string, ?string>} */
    public function botStatus(): array
    {
        $client = app(BotClient::class);

        return [
            'enabled' => $client->enabledMessengers(),
            'links' => collect(BotLink::MESSENGERS)->mapWithKeys(fn ($m) => [$m => BotLink::query()->where('messenger', $m)->count()])->all(),
            'usernames' => collect(BotLink::MESSENGERS)->mapWithKeys(fn ($m) => [$m => $client->username($m)])->all(),
        ];
    }
}
