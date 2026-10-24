<?php

namespace App\Services\Customer;

use App\Models\Booking;
use App\Models\DeletedAccountArchive;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\SecurityLogService;
use App\Support\Retention;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * حذف حساب مشتری — وب و اپ (تصمیم ۲۰۲۶-۱۰-۱۱، گزینه‌ی «الف»).
 *
 * قبلاً user->delete() برای هر مشتری‌ای که حتی یک نوبت داشت با کلید خارجی bookings.user_id شکست می‌خورد (۵۰۰).
 * حالا:
 *  - نوبت فعالِ آینده یا موجودی کیف پول → رد با پیام (پول و نوبت مشتری بی‌صاحب نمی‌ماند)؛
 *  - بدون هیچ سابقه‌ی نوبت/پرداخت → حذف کامل ردیف؛
 *  - با سابقه → ناشناس‌سازی: نام/شماره/رمز/کدها پاک، توکن‌ها و اتصال ربات و اعلان‌ها حذف؛ نوبت‌ها برای
 *    حساب‌وکتاب سالن می‌مانند. همان شماره بعداً می‌تواند دوباره ثبت‌نام کند.
 *
 * ⭐ بسته‌ی مجوز 5د (۲۰۲۶-۱۰-۲۵): 🐞 هویت و IP همان لحظه از بین می‌رفت — تعارض با ماده‌ی ۶۶۸ آیین دادرسی کیفری (اطلاعات کاربر و IP
 * دست‌کم شش ماه پس از خاتمه‌ی اشتراک). حالا پیش از حذف/ناشناس‌سازی، نام، شماره و تاریخچه‌ی IP (security_logs و نشست‌ها، در
 * مهلت نگهداری) در deleted_account_archives رمزنگاری‌شده می‌ماند (فقط ابرمدیر) و retention:prune بعد از مهلت پاکش می‌کند.
 * ماده‌ی ۵۹ هـ قانون تجارت الکترونیکی حق محو را «با رعایت ضوابط مربوطه» می‌دهد: حساب و هر نمایش آن همان لحظه پاک می‌شود.
 */
class CustomerAccountDeletionService
{
    public const ACTIVE_STATUSES = ['pending_payment', 'pending', 'confirmed'];

    /** دلیل رد حذف، یا null اگر حذف مجاز است. */
    public function blocker(User $user): ?string
    {
        $active = Booking::query()->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('booking_time', '>', now())
            ->count();
        if ($active > 0) {
            return "شما {$active} نوبت فعال دارید. پیش از حذف حساب، نوبت‌ها را لغو کنید یا منتظر انجامشان بمانید.";
        }

        $balance = (float) ($user->wallet?->balance ?? 0);
        if ($balance > 0) {
            return 'کیف پول شما '.number_format($balance).' تومان موجودی دارد. پیش از حذف حساب آن را در رزرو بعدی استفاده کنید یا برای تسویه با سالن تماس بگیرید.';
        }

        return null;
    }

    /**
     * @return string deleted (ردیف حذف شد) یا anonymized
     */
    public function delete(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $hasHistory = Booking::query()->withoutGlobalScopes()->where('user_id', $user->id)->exists()
                || PaymentTransaction::where('user_id', $user->id)->exists();

            $archive = $this->archive($user, $hasHistory ? 'anonymized' : 'deleted');
            app(SecurityLogService::class)->logAction('account_deleted', $archive, ['archive_id' => $archive->id], $user->id);

            if (! $hasHistory) {
                $user->delete();

                return 'deleted';
            }

            $user->tokens()->delete();
            DB::table('bot_links')->where('user_id', $user->id)->delete();
            DB::table('user_notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'مشتری حذف‌شده',
                'phone' => 'deleted-'.$user->id,
                'password' => Hash::make(Str::random(40)),
                'phone_verified_at' => null,
                'two_factor_enabled' => false,
                'two_factor_code' => null,
                'two_factor_code_expires_at' => null,
                'verification_code' => null,
                'verification_code_expire_at' => null,
                'login_verification_code' => null,
                'login_verification_code_expire_at' => null,
                'remember_token' => null,
            ])->save();

            return 'anonymized';
        });
    }

    /** هویت و تاریخچه‌ی IP پیش از حذف — جدا و رمزنگاری‌شده، تا پایان مهلت قانونی */
    private function archive(User $user, string $outcome): DeletedAccountArchive
    {
        $since = Retention::cutoff('security_logs_days');
        $seen = [];
        $add = function (?string $ip, $at, ?string $agent) use (&$seen) {
            if (! $ip) {
                return;
            }
            $at = \Illuminate\Support\Carbon::parse($at)->toIso8601String();
            $row = $seen[$ip] ?? ['ip' => $ip, 'first_seen' => $at, 'last_seen' => $at, 'count' => 0, 'user_agent' => null];
            $row['first_seen'] = min($row['first_seen'], $at);
            if ($at >= $row['last_seen'] || $row['user_agent'] === null) {
                $row['last_seen'] = max($row['last_seen'], $at);
                $row['user_agent'] = $agent ? mb_substr($agent, 0, 300) : $row['user_agent'];
            }
            $row['count']++;
            $seen[$ip] = $row;
        };

        DB::table('security_logs')->where('user_id', $user->id)->where('created_at', '>=', $since)
            ->orderBy('id')->select(['ip_address', 'created_at', 'user_agent'])
            ->each(fn ($log) => $add($log->ip_address, $log->created_at, $log->user_agent));
        DB::table('sessions')->where('user_id', $user->id)->select(['ip_address', 'last_activity', 'user_agent'])
            ->get()->each(fn ($s) => $add($s->ip_address, \Illuminate\Support\Carbon::createFromTimestamp((int) $s->last_activity), $s->user_agent));

        $deletionIp = SecurityLogService::hasClientRequest() ? request()->ip() : null;
        $deletionAgent = SecurityLogService::hasClientRequest() ? mb_substr((string) request()->userAgent(), 0, 1000) : null;
        $add($deletionIp, now(), $deletionAgent);

        return DeletedAccountArchive::create([
            'user_id' => $user->id,
            'salon_id' => $user->salon_id,
            'user_type' => $user->user_type,
            'name' => (string) $user->name,
            'phone' => (string) $user->phone,
            'account_created_at' => $user->created_at,
            'ip_history' => array_values($seen),
            'deletion_ip' => $deletionIp,
            'deletion_user_agent' => $deletionAgent,
            'deleted_by' => auth()->id(),
            'outcome' => $outcome,
            'deleted_at' => now(),
            'purge_after' => now()->addDays(Retention::days('deleted_accounts_days')),
        ]);
    }
}
