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

    /** @return array<string, mixed> */
    public function dashboard(?Carbon $now = null): array
    {
        $now ??= now();
        // بسته‌ی ۱ ظاهری (تصمیم ابوالفضل ۱۱-۲۴): «این ماه» = ماه شمسی (درآمد آذر). پیامک روی دوره‌ی سهمیه می‌ماند (Y-m میلادی).
        $month = verta($now);
        $monthStart = Carbon::instance($month->startMonth()->datetime());
        $awaiting = SupportTicket::withoutGlobalScopes()->awaitingResponse();

        return [
            'month_name' => $month->format('F'),
            'month_start' => $monthStart,
            'subscription_revenue_month' => (int) Invoice::withoutGlobalScopes()->where('status', 'paid')
                ->where('paid_at', '>=', $monthStart)->sum('amount'),
            'sms_revenue_month' => (int) SmsCreditPurchase::query()->where('status', 'paid')->where('source', 'online')
                ->where('paid_at', '>=', $monthStart)->sum('amount'),
            'new_salons_month' => Salon::withoutGlobalScopes()->where('created_at', '>=', $monthStart)->count(),
            'trial_salons' => Salon::withoutGlobalScopes()->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', $now)
                ->whereDoesntHave('invoices', fn ($q) => $q->withoutGlobalScopes()->where('status', 'paid'))->count(),
            'sms_period' => $this->quota->currentPeriod(),
            'sms_parts_month' => (int) SalonSmsUsage::query()->where('period', $this->quota->currentPeriod())
                ->sum(DB::raw('used_count + credit_used')),
            'otp_month' => (int) SalonSmsUsage::query()->where('period', $this->quota->currentPeriod())->sum('otp_count'),
            'open_tickets' => SupportTicket::withoutGlobalScopes()->where('status', 'open')->count(),
            'oldest_open_ticket_at' => SupportTicket::withoutGlobalScopes()->where('status', 'open')->min('created_at'),
            'overdue_complaints' => (clone $awaiting)->where('response_due_at', '<', $now)->count(),
            'pending_verifications' => \App\Models\SalonVerification::query()->where('status', 'pending')->count(),
            'open_demo_requests' => DemoRequest::query()->open()->count(),
            'payments_attention' => $this->attentionQuery()->count(),
            'failed_jobs' => $this->failedJobsCount(),
        ];
    }

    /**
     * فهرست «نیاز به رسیدگی» داشبورد: هر مورد با شمار، متن و لینک؛ موارد صفر جدا تا در یک خط «موردی نیست» بیایند.
     *
     * @param  array<string, mixed>  $overview  خروجی dashboard()
     * @param  array<string, mixed>  $backup  خروجی backup()
     * @return array{items: list<array{key: string, n: int|string, text: string, action: string, url: string, tone: string}>, clear: list<string>}
     */
    public function attention(array $overview, array $backup): array
    {
        $items = [];
        $clear = [];
        $add = function (string $key, int $n, string $text, string $action, string $url, string $tone, string $zeroLabel) use (&$items, &$clear) {
            if ($n > 0) {
                $items[] = ['key' => $key, 'n' => $n, 'text' => $text, 'action' => $action, 'url' => $url, 'tone' => $tone];
            } else {
                $clear[] = $zeroLabel;
            }
        };

        $oldest = $overview['oldest_open_ticket_at'] ? Carbon::parse($overview['oldest_open_ticket_at']) : null;
        $ticketText = 'تیکت باز'.($oldest ? '؛ قدیمی‌ترین '.$this->ago($oldest) : '');
        if ($overview['overdue_complaints'] > 0) {
            $ticketText .= '؛ '.fa_num($overview['overdue_complaints']).' شکایت از مهلت ۷۲ ساعته گذشته';
        }
        $add('tickets', (int) $overview['open_tickets'], $ticketText, 'دیدن تیکت‌ها', route('superadmin.support-tickets.index'),
            $overview['overdue_complaints'] > 0 ? 'danger' : 'warn', 'تیکت');
        $add('verifications', (int) $overview['pending_verifications'], 'سالن منتظر احراز هویت', 'بررسی مدارک', route('superadmin.verifications.index'), 'warn', 'احراز');
        $add('demos', (int) $overview['open_demo_requests'], 'درخواست دموی پیگیری‌نشده', 'دیدن درخواست‌ها', route('superadmin.demo-requests.index'), 'warn', 'درخواست دمو');
        $add('payments', (int) $overview['payments_attention'], 'پرداخت پلتفرم نیازمند بررسی', 'بررسی پرداخت‌ها', route('superadmin.payment-attention.index'), 'danger', 'پرداخت نیازمند بررسی');
        $add('failed_jobs', (int) $overview['failed_jobs'], 'کار ناموفق در صف', 'دیدن کارها', route('superadmin.failed-jobs.index'), 'danger', 'کار ناموفق در صف');

        if (($backup['state'] ?? null) !== 'ok') {
            $items[] = ['key' => 'backup', 'n' => '!', 'text' => match ($backup['state'] ?? null) {
                'stale' => 'آخرین پشتیبان قدیمی است',
                'failed' => 'آخرین پشتیبان‌گیری ناموفق بود'.(! empty($backup['message']) ? '؛ '.$backup['message'] : ''),
                default => 'پشتیبان‌گیری خودکار سرور هنوز تنظیم نشده است',
            }, 'action' => 'راهنمای تنظیم', 'url' => '#backup-status', 'tone' => 'danger'];
        }

        // خطرها اول (قرمز)، بعد کارهای عادی
        usort($items, fn ($a, $b) => ($a['tone'] === 'danger' ? 0 : 1) <=> ($b['tone'] === 'danger' ? 0 : 1));

        return ['items' => $items, 'clear' => $clear];
    }

    private function ago(Carbon $at): string
    {
        $days = (int) floor($at->diffInSeconds(now()) / 86400);

        return match (true) {
            $at->isToday() => 'از امروز',
            $at->isYesterday() => 'از دیروز',
            default => 'از '.fa_num(max(2, $days)).' روز پیش',
        };
    }

    /** @return array{state: string, at: ?\Illuminate\Support\Carbon, size: ?string, dest: ?string, message: ?string} */
    public function backup(): array
    {
        return \App\Support\BackupStatus::read();
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
            'sources' => collect(BotLink::MESSENGERS)->mapWithKeys(fn ($m) => [$m => \App\Services\Bot\BotSettings::tokenSource($m)])->all(),
            'masked' => collect(BotLink::MESSENGERS)->mapWithKeys(fn ($m) => [$m => \App\Services\Bot\BotSettings::masked($m)])->all(),
            'api_bases' => collect(BotLink::MESSENGERS)->mapWithKeys(fn ($m) => [$m => \App\Support\PlatformSettings::raw("bot_{$m}_api_base")])->all(),
        ];
    }
}
