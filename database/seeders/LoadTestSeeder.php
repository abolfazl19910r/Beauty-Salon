<?php

namespace Database\Seeders;

use App\Support\Iban;
use App\Support\SalonWorkingHours;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * داده‌ی بار (فیک ولی واقعی‌نما) برای اندازه‌گیری کارایی: ۱۰۰ یا ۱۰۰۰ سالن با متخصص، خدمت، مشتری، نوبت چندماهه، پرداخت،
 * تراکنش درگاه، کیف پول، برداشت، اعلان و نظر.
 *
 * ⚠️ جزو DatabaseSeeder نیست؛ فقط با `php artisan perf:seed-load` اجرا می‌شود. عمداً با insert دسته‌ای مستقیم (بدون مدل،
 * observer، اعلان یا صف) تا ۱۰۰۰ سالن در چند دقیقه ساخته شود؛ پس هر مقدار مشتق (موجودی کیف پول، کمیسیون، وضعیت تسویه)
 * همین‌جا و سازگار با قاعده‌های برنامه حساب می‌شود. شناسه‌ها از max(id)+1 جلو می‌روند تا روی دیتابیس موجود هم اضافه شود.
 */
class LoadTestSeeder extends Seeder
{
    public int $salons = 100;

    public int $months = 4;

    public int $futureDays = 30;

    public int $specialistsPerSalon = 6;

    public int $servicesPerSalon = 10;

    public int $customersPerSalon = 150;

    /** میانگین نوبت در هر روز کاری برای کل سالن */
    public int $bookingsPerDay = 6;

    public ?\Closure $progress = null;

    private const COMMISSION = 10.0;

    private const SETTLEMENT_DELAY_DAYS = 2;

    private array $buffers = [];

    private array $next = [];

    private array $counts = [];

    private string $password;

    private Carbon $now;

    private int $runTag;

    public function run(): void
    {
        mt_srand(20260930);
        DB::disableQueryLog();
        $this->now = Carbon::now()->startOfMinute();
        $this->password = Hash::make('password');
        $this->runTag = (int) DB::table('salons')->max('id');

        foreach (['salons', 'users', 'categories', 'beauty_services', 'specialists', 'specialist_services', 'specialist_schedules',
            'specialist_wallets', 'wallet_settings', 'admin_wallet', 'admin_wallet_transactions', 'bookings', 'payments',
            'payment_transactions', 'wallet_transactions', 'withdrawal_requests', 'user_wallets', 'user_wallet_transactions',
            'reviews', 'salon_admins', 'salon_sms_usages'] as $table) {
            $this->next[$table] = ((int) DB::table($table)->max('id')) + 1;
        }

        $roles = $this->systemRoles();
        $this->superAdmin($roles['super-admin']);

        for ($i = 1; $i <= $this->salons; $i++) {
            DB::transaction(fn () => $this->seedSalon($i, $roles));
            if ($this->progress && ($i % 10 === 0 || $i === $this->salons)) {
                ($this->progress)($i, $this->salons);
            }
        }
        $this->flushAll();
    }

    public function counts(): array
    {
        return $this->counts;
    }

    private function seedSalon(int $n, array $roles): void
    {
        $now = $this->now;
        $tag = $this->runTag + $n;
        $salonId = $this->id('salons');
        $ownerId = $this->id('users');

        $this->push('users', $this->staffUser($ownerId, 'مدیر سالن '.$tag, $this->phone('0912', $tag), true));
        $this->push('salons', [
            'id' => $salonId, 'created_by' => null, 'name' => 'سالن بار '.$tag, 'slug' => 'load-'.$tag,
            'tagline' => null, 'bio' => null, 'logo_path' => null, 'address' => 'تهران', 'phone' => '021'.str_pad((string) $tag, 8, '0', STR_PAD_LEFT),
            'established_year' => 2015, 'working_hours' => json_encode(SalonWorkingHours::defaults()), 'max_specialists_count' => max(7, $this->specialistsPerSalon),
            'module_permissions' => null, 'sms_quota_per_month' => 1000, 'subscription_type' => '12m',
            'subscription_started_at' => $now->copy()->subMonths($this->months + 1), 'subscription_ends_at' => $now->copy()->addMonths(6),
            'trial_ends_at' => null, 'is_suspended' => 0, 'created_at' => $now->copy()->subMonths($this->months + 1), 'updated_at' => $now,
        ]);
        // salons باید پیش از FKهای بقیه‌ی جدول‌ها نوشته شود
        $this->flush('users');
        $this->flush('salons');

        $this->push('salon_admins', ['id' => $this->id('salon_admins'), 'salon_id' => $salonId, 'user_id' => $ownerId, 'role' => 'owner', 'created_at' => $now, 'updated_at' => $now]);
        $this->push('wallet_settings', ['id' => $this->id('wallet_settings'), 'salon_id' => $salonId, 'admin_commission_percentage' => self::COMMISSION, 'settlement_delay_days' => self::SETTLEMENT_DELAY_DAYS, 'created_at' => $now, 'updated_at' => $now]);
        $adminWalletId = $this->id('admin_wallet');
        $this->push('salon_sms_usages', ['id' => $this->id('salon_sms_usages'), 'salon_id' => $salonId, 'period' => $now->format('Y-m'), 'used_count' => mt_rand(50, 600), 'otp_count' => mt_rand(20, 200), 'notified_at' => null, 'created_at' => $now, 'updated_at' => $now]);

        // دسته و خدمت
        $categoryIds = [];
        for ($c = 1; $c <= 3; $c++) {
            $categoryIds[] = $cid = $this->id('categories');
            $this->push('categories', ['id' => $cid, 'salon_id' => $salonId, 'name' => "دسته {$c}", 'slug' => "load-{$tag}-cat-{$c}", 'created_at' => $now, 'updated_at' => $now]);
        }
        $services = [];
        for ($s = 1; $s <= $this->servicesPerSalon; $s++) {
            $sid = $this->id('beauty_services');
            $price = mt_rand(20, 250) * 10000;
            $services[] = ['id' => $sid, 'price' => $price];
            $this->push('beauty_services', ['id' => $sid, 'salon_id' => $salonId, 'name' => "خدمت {$s}", 'slug' => "load-{$tag}-srv-{$s}", 'description' => 'خدمت آزمایشی',
                'price' => $price, 'duration' => [30, 45, 60, 90][mt_rand(0, 3)], 'image' => null, 'category_id' => $categoryIds[$s % 3], 'created_at' => $now, 'updated_at' => $now]);
        }

        // متخصص‌ها (هر کدام کاربر staff با همان تلفن — User::specialist() با تلفن وصل می‌شود)
        $specialists = [];
        for ($p = 1; $p <= $this->specialistsPerSalon; $p++) {
            $uid = $this->id('users');
            $spid = $this->id('specialists');
            $phone = $this->phone('093', $tag * 100 + $p);
            $this->push('users', $this->staffUser($uid, "متخصص {$p} سالن {$tag}", $phone, false));
            $this->push('role_user', ['user_id' => $uid, 'role_id' => $roles['specialist']]);
            $this->push('specialists', ['id' => $spid, 'salon_id' => $salonId, 'name' => "متخصص {$p}", 'phone' => $phone, 'photo_path' => null, 'user_id' => $uid,
                'email' => "sp-{$tag}-{$p}@load.test", 'auto_confirm_bookings' => mt_rand(0, 1), 'commission_rate' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null]);
            $offered = [];
            foreach ($services as $srv) {
                if (mt_rand(1, 100) <= 50 || $offered === []) {
                    $offered[] = $srv;
                    $this->push('specialist_services', ['id' => $this->id('specialist_services'), 'specialist_id' => $spid, 'beauty_service_id' => $srv['id'], 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            for ($d = 0; $d <= 6; $d++) {
                if ($d === 5) {
                    continue;
                }
                $this->push('specialist_schedules', ['id' => $this->id('specialist_schedules'), 'specialist_id' => $spid, 'day_of_week' => $d, 'start_time' => '09:00:00', 'end_time' => '20:00:00',
                    'break_start' => '13:00:00', 'break_end' => '14:00:00', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
            $specialists[] = ['id' => $spid, 'user_id' => $uid, 'wallet_id' => $this->id('specialist_wallets'), 'services' => $offered,
                'balance' => 0.0, 'pending' => 0.0, 'earned' => 0.0, 'withdrawn' => 0.0, 'iban' => Iban::fromBban(str_pad((string) ($tag * 100 + $p), 22, '0', STR_PAD_LEFT))];
        }

        // مشتری‌ها
        $customers = [];
        for ($u = 1; $u <= $this->customersPerSalon; $u++) {
            $uid = $this->id('users');
            $customers[] = $uid;
            $created = $now->copy()->subDays(mt_rand(0, $this->months * 30 + 30));
            $this->push('users', ['id' => $uid, 'salon_id' => $salonId, 'user_type' => 'customer', 'name' => "مشتری {$u}", 'phone' => $this->phone('0901', $u),
                'phone_verified_at' => $created, 'password' => $this->password, 'password_changed_at' => null, 'password_strength_score' => null, 'is_admin' => 0,
                'two_factor_enabled' => 0, 'created_at' => $created, 'updated_at' => $created]);
        }
        $this->flush('users');
        $this->flush('categories');
        $this->flush('beauty_services');
        $this->flush('specialists');

        // نوبت‌ها — هر روز کاری، ساعت‌های یکتا برای هر متخصص (کلید active_slot_key)
        $start = $now->copy()->subMonths($this->months)->startOfDay();
        $end = $now->copy()->addDays($this->futureDays)->startOfDay();
        $adminBalance = 0.0;
        $adminEarned = 0.0;
        $notifyFor = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->dayOfWeek === 5) {
                continue;
            }
            $count = max(0, $this->bookingsPerDay + mt_rand(-2, 2));
            $taken = [];
            for ($b = 0; $b < $count; $b++) {
                $sp = $specialists[mt_rand(0, count($specialists) - 1)];
                $hour = mt_rand(9, 19);
                if ($hour === 13 || isset($taken[$sp['id']][$hour])) {
                    continue;
                }
                $taken[$sp['id']][$hour] = true;
                $time = $day->copy()->setTime($hour, 0);
                $this->booking($salonId, $adminWalletId, $sp, $customers, $time, $specialists, $adminBalance, $adminEarned, $ownerId, $notifyFor);
            }
        }

        // موارد عمدی برای دستورهای scheduler: یک نوبت پرداخت‌نشده‌ی قدیمی (CancelUnpaidBookings) و یک نوبت داخل پنجره‌ی یادآوری
        $sp = $specialists[0];
        $this->booking($salonId, $adminWalletId, $sp, $customers, $now->copy()->addDays(3)->setTime(10, 30), $specialists, $adminBalance, $adminEarned, $ownerId, $notifyFor, 'pending_payment');
        $reminderAt = $now->copy()->addMinutes(60);
        if ($reminderAt->minute === 0) {
            $reminderAt->addMinutes(2); // ساعت رند با نوبت‌های معمولی هم‌اسلات نشود
        }
        $this->booking($salonId, $adminWalletId, $specialists[1 % count($specialists)], $customers, $reminderAt, $specialists, $adminBalance, $adminEarned, $ownerId, $notifyFor, 'reminder');

        // برداشت‌ها: هر متخصص ماهی یک برداشت انجام‌شده (تا ۷۰٪ موجودی)، و برای بعضی یک درخواست در انتظار
        foreach ($specialists as $i => $sp) {
            $sp = $this->withdrawals($sp, $tag);
            $specialists[$i] = $sp;
            $verified = mt_rand(1, 100) <= 70;
            $this->push('specialist_wallets', ['id' => $sp['wallet_id'], 'specialist_id' => $sp['id'], 'balance' => round($sp['balance'], 2), 'total_earned' => round($sp['earned'], 2),
                'total_withdrawn' => round($sp['withdrawn'], 2), 'pending_amount' => round($sp['pending'], 2), 'iban' => $sp['iban'], 'account_holder_name' => 'صاحب حساب',
                'bank_name' => 'ملت', 'iban_verified' => $verified ? 1 : 0, 'iban_verified_by' => $verified ? $ownerId : null, 'iban_verified_at' => $verified ? $now : null,
                'created_at' => $start, 'updated_at' => $now]);
        }
        $this->push('admin_wallet', ['id' => $adminWalletId, 'salon_id' => $salonId, 'balance' => round($adminBalance, 2), 'total_earned' => round($adminEarned, 2), 'total_withdrawn' => 0,
            'created_at' => $start, 'updated_at' => $now]);

        // کیف پول مشتری برای ۲۰٪ مشتری‌ها
        foreach ($customers as $cid) {
            if (mt_rand(1, 100) > 20) {
                continue;
            }
            $wid = $this->id('user_wallets');
            $deposit = mt_rand(5, 50) * 10000;
            $spent = mt_rand(0, (int) ($deposit / 20000)) * 10000;
            $this->push('user_wallets', ['id' => $wid, 'user_id' => $cid, 'balance' => $deposit - $spent, 'total_deposited' => $deposit, 'total_spent' => $spent, 'created_at' => $start, 'updated_at' => $now]);
            $this->push('user_wallet_transactions', ['id' => $this->id('user_wallet_transactions'), 'wallet_id' => $wid, 'booking_id' => null, 'type' => 'deposit', 'amount' => $deposit,
                'balance_after' => $deposit, 'description' => 'شارژ کیف پول', 'metadata' => null, 'created_at' => $start, 'updated_at' => $start]);
            if ($spent > 0) {
                $this->push('user_wallet_transactions', ['id' => $this->id('user_wallet_transactions'), 'wallet_id' => $wid, 'booking_id' => null, 'type' => 'payment', 'amount' => -$spent,
                    'balance_after' => $deposit - $spent, 'description' => 'پرداخت نوبت', 'metadata' => null, 'created_at' => $now->copy()->subDays(10), 'updated_at' => $now]);
            }
        }

        $this->flushAll();
    }

    private function booking(int $salonId, int $adminWalletId, array &$sp, array $customers, Carbon $time, array &$specialists, float &$adminBalance, float &$adminEarned, int $ownerId, array &$notifyFor, ?string $special = null): void
    {
        $now = $this->now;
        $id = $this->id('bookings');
        $srv = $sp['services'][mt_rand(0, count($sp['services']) - 1)];
        $userId = $customers[mt_rand(0, count($customers) - 1)];
        $past = $time->lt($now);
        $created = $past ? $time->copy()->subDays(mt_rand(1, 10))->subMinutes(mt_rand(0, 600)) : $now->copy()->subDays(mt_rand(0, 10))->subMinutes(mt_rand(0, 600));
        if ($created->gt($now)) {
            $created = $now->copy()->subHour();
        }
        $source = mt_rand(1, 100) <= 80 ? 'online' : (mt_rand(0, 1) ? 'phone' : 'walk_in');
        $r = mt_rand(1, 100);
        if ($past) {
            $status = $r <= 75 ? 'completed' : ($r <= 87 ? 'cancelled' : 'confirmed');
        } else {
            $status = $r <= 70 ? 'confirmed' : ($r <= 90 ? 'pending' : 'cancelled');
        }
        if ($special === 'pending_payment') {
            [$status, $source, $created] = ['pending_payment', 'online', $now->copy()->subMinutes(45)];
        } elseif ($special === 'reminder') {
            [$status, $source] = ['confirmed', 'online'];
        }
        $prepay = max(50000, round($srv['price'] * 0.3, -3));
        $paid = $source === 'online' && $status !== 'pending_payment' && ! ($status === 'cancelled' && mt_rand(0, 2) === 0);
        $paidAt = $paid ? $created->copy()->addMinutes(3) : null;
        $cancelled = $status === 'cancelled';
        $refunded = $cancelled && $paid;

        $this->push('bookings', [
            'id' => $id, 'salon_id' => $salonId, 'service_id' => $srv['id'], 'specialist_id' => $sp['id'], 'user_id' => $userId, 'booking_time' => $time,
            'status' => $status, 'source' => $source, 'discount_code' => null, 'discount_amount' => null, 'prepayment_amount' => $source === 'online' ? $prepay : 0,
            'payment_status' => $paid ? 'paid' : 'unpaid', 'payment_reference' => $paid ? 'A'.str_pad((string) $id, 35, '0', STR_PAD_LEFT) : null,
            'payment_details' => null, 'paid_at' => $paidAt, 'rating' => null, 'review' => null,
            'review_sent_at' => $status === 'completed' ? $time->copy()->addHours(2) : null, 'reviewed_at' => null,
            'notes' => null, 'cancelled_by' => $cancelled ? ['customer', 'admin', 'system', 'specialist'][mt_rand(0, 3)] : null,
            'cancellation_reason' => $cancelled ? 'تغییر برنامه' : null, 'cancelled_at' => $cancelled ? $created->copy()->addHours(5) : null,
            'reminder_sent' => $past && $status !== 'cancelled' ? 1 : 0,
            'refund_status' => $refunded ? 'refunded' : null, 'refunded_at' => $refunded ? $created->copy()->addHours(5) : null,
            'refunded_amount' => $refunded ? $prepay : null, 'refund_reference' => null, 'refund_details' => null,
            'created_at' => $created, 'updated_at' => $cancelled ? $created->copy()->addHours(5) : ($paidAt ?? $created),
        ]);

        // تراکنش درگاه: ~۸٪ تلاش ناموفق قبل از موفق؛ نوبت‌های آنلاین پرداخت‌نشده هم یک تراکنش رهاشده/ناموفق دارند
        if ($source === 'online') {
            if (mt_rand(1, 100) <= 8 || ! $paid) {
                $failedAt = $created->copy()->addMinute();
                $status2 = $special === 'pending_payment' ? 'pending' : 'failed';
                $this->push('payment_transactions', $this->tx($salonId, $id, $userId, $prepay, $status2, $created, $status2 === 'failed' ? $failedAt : $created, null));
            }
            if ($paid) {
                $this->push('payment_transactions', $this->tx($salonId, $id, $userId, $prepay, $refunded ? 'refunded' : 'paid', $created->copy()->addMinutes(2), $paidAt, $paidAt));
                $this->push('payments', ['id' => $this->id('payments'), 'booking_id' => $id, 'amount' => $prepay, 'reference_id' => 'LD'.$id,
                    'card_data' => null, 'status' => 'completed', 'gateway_reference' => (string) (100000000 + $id), 'gateway_response' => null,
                    'payment_details' => null, 'paid_at' => $paidAt, 'expired_at' => null, 'created_at' => $created, 'updated_at' => $paidAt]);
            }
        }

        if ($paid && ! $cancelled) {
            $income = round($prepay * (1 - self::COMMISSION / 100), 2);
            $commission = round($prepay - $income, 2);
            $settleDate = $paidAt->copy()->addDays(self::SETTLEMENT_DELAY_DAYS);
            $settled = $settleDate->lt($now) && $time->lt($now);
            $sp['earned'] += $income;
            if ($settled) {
                $sp['balance'] += $income;
            } else {
                $sp['pending'] += $income;
            }
            $this->push('wallet_transactions', ['id' => $this->id('wallet_transactions'), 'wallet_id' => $sp['wallet_id'], 'booking_id' => $id, 'type' => 'income', 'amount' => $income,
                'balance_after' => round($sp['balance'], 2), 'description' => "درآمد از نوبت #{$id}",
                'metadata' => json_encode(['settlement_date' => $settleDate->toDateString(), 'status' => $settled ? 'settled' : 'pending'] + ($settled ? ['settled_at' => $settleDate->toDateTimeString(), 'settled_by' => 'schedule'] : [])),
                'created_at' => $paidAt, 'updated_at' => $settled ? $settleDate : $paidAt]);
            $adminBalance += $commission;
            $adminEarned += $commission;
            $this->push('admin_wallet_transactions', ['id' => $this->id('admin_wallet_transactions'), 'admin_wallet_id' => $adminWalletId, 'booking_id' => $id, 'type' => 'commission',
                'amount' => $commission, 'balance_after' => round($adminBalance, 2), 'description' => "کمیسیون نوبت #{$id}", 'metadata' => null, 'created_at' => $paidAt, 'updated_at' => $paidAt]);
        }

        // نظر برای ~۳۵٪ نوبت‌های انجام‌شده
        if ($status === 'completed' && mt_rand(1, 100) <= 35) {
            $rating = [5, 5, 5, 4, 4, 3, 2, 1][mt_rand(0, 7)];
            $at = $time->copy()->addHours(mt_rand(3, 72));
            $this->push('reviews', ['id' => $this->id('reviews'), 'booking_id' => $id, 'user_id' => $userId, 'specialist_id' => $sp['id'], 'service_id' => $srv['id'],
                'overall_rating' => $rating, 'quality_rating' => $rating, 'behavior_rating' => $rating, 'cleanliness_rating' => $rating, 'speed_rating' => $rating,
                'comment' => $rating >= 4 ? 'عالی بود' : 'می‌توانست بهتر باشد', 'review_token' => 'ld'.$id, 'reviewed_at' => $at, 'is_approved' => 1, 'is_featured' => 0,
                'specialist_response' => null, 'responded_at' => null, 'created_at' => $at, 'updated_at' => $at, 'deleted_at' => null]);
        }

        // اعلان: به مالک برای هر نوبت، به متخصص برای نیمی
        $read = $created->lt($now->copy()->subDays(7)) || mt_rand(0, 1);
        $data = json_encode(['type' => 'new_booking_admin', 'booking_id' => $id, 'message' => "نوبت جدید ثبت شد #{$id}", 'link' => '/admin/bookings/'.$id], JSON_UNESCAPED_UNICODE);
        $this->push('user_notifications', ['id' => (string) Str::uuid(), 'type' => 'App\\Notifications\\Booking\\AdminNewBookingNotification', 'user_id' => $ownerId, 'salon_id' => $salonId,
            'notifiable_type' => 'App\\Models\\User', 'notifiable_id' => $ownerId, 'data' => $data, 'read_at' => $read ? $created->copy()->addHour() : null, 'created_at' => $created, 'updated_at' => $created]);
        if (mt_rand(0, 1)) {
            $this->push('user_notifications', ['id' => (string) Str::uuid(), 'type' => 'App\\Notifications\\Booking\\BookingNotification', 'user_id' => $sp['user_id'], 'salon_id' => $salonId,
                'notifiable_type' => 'App\\Models\\User', 'notifiable_id' => $sp['user_id'], 'data' => $data, 'read_at' => $read ? $created->copy()->addHour() : null, 'created_at' => $created, 'updated_at' => $created]);
        }

        foreach ($specialists as $k => $s) {
            if ($s['id'] === $sp['id']) {
                $specialists[$k] = $sp;
            }
        }
    }

    private function withdrawals(array $sp, int $tag): array
    {
        $now = $this->now;
        for ($m = $this->months - 1; $m >= 1; $m--) {
            $amount = floor($sp['balance'] * 0.7 / 10000) * 10000;
            if ($amount < 100000) {
                continue;
            }
            $at = $now->copy()->subMonths($m)->startOfMonth()->addDays(mt_rand(1, 20));
            $wid = $this->id('withdrawal_requests');
            $sp['balance'] -= $amount;
            $sp['withdrawn'] += $amount;
            $this->push('withdrawal_requests', $this->withdrawalRow($wid, $sp, $amount, 'completed', $at, $tag));
            $this->push('wallet_transactions', ['id' => $this->id('wallet_transactions'), 'wallet_id' => $sp['wallet_id'], 'booking_id' => null, 'type' => 'withdrawal', 'amount' => -$amount,
                'balance_after' => round($sp['balance'], 2), 'description' => "برداشت وجه - کد پیگیری: {$wid}", 'metadata' => json_encode(['withdrawal_request_id' => $wid]),
                'created_at' => $at, 'updated_at' => $at]);
        }
        if (mt_rand(1, 100) <= 30 && $sp['balance'] >= 200000) {
            $amount = floor($sp['balance'] * 0.5 / 10000) * 10000;
            $this->push('withdrawal_requests', $this->withdrawalRow($this->id('withdrawal_requests'), $sp, $amount, 'pending', $now->copy()->subDays(mt_rand(0, 5)), $tag));
        }

        return $sp;
    }

    private function withdrawalRow(int $id, array $sp, float $amount, string $status, Carbon $at, int $tag): array
    {
        $fee = round($amount * 0.025, 2);

        return ['id' => $id, 'wallet_id' => $sp['wallet_id'], 'specialist_id' => $sp['id'], 'reference_code' => 'WDL'.$id, 'amount' => $amount, 'fee' => $fee,
            'net_amount' => $amount - $fee, 'method' => 'iban', 'iban' => $sp['iban'], 'account_holder_name' => 'صاحب حساب', 'status' => $status, 'needs_manual_check' => 0,
            'attention_notified_at' => null, 'admin_note' => null, 'rejection_reason' => null, 'processed_at' => $status === 'completed' ? $at->copy()->addDay() : null,
            'processed_by' => null, 'payment_details' => null, 'created_at' => $at, 'updated_at' => $at];
    }

    private function tx(int $salonId, int $bookingId, int $userId, float $prepay, string $status, Carbon $created, Carbon $updated, ?Carbon $verified): array
    {
        $id = $this->id('payment_transactions');

        return ['id' => $id, 'public_id' => (string) Str::ulid(), 'salon_id' => $salonId, 'gateway_id' => null, 'driver' => 'zarinpal', 'purpose' => 'booking',
            'payable_type' => 'App\\Models\\Booking', 'payable_id' => $bookingId, 'user_id' => $userId, 'amount_rial' => (int) ($prepay * 10), 'fee_rial' => 0,
            'token' => 'A'.str_pad((string) $id, 35, '0', STR_PAD_LEFT), 'ref_id' => $status === 'paid' || $status === 'refunded' ? (string) (500000 + $id) : null,
            'gateway_receipt' => null, 'card_pan' => null, 'status' => $status, 'needs_attention' => 0, 'attention_notified_at' => null, 'callback_url' => '/payment/callback',
            'start_response' => null, 'verify_response' => null, 'verified_at' => $verified, 'created_at' => $created, 'updated_at' => $updated];
    }

    private function staffUser(int $id, string $name, string $phone, bool $admin): array
    {
        return ['id' => $id, 'salon_id' => null, 'user_type' => 'staff', 'name' => $name, 'phone' => $phone, 'phone_verified_at' => $this->now, 'password' => $this->password,
            'password_changed_at' => null, 'password_strength_score' => null, 'is_admin' => $admin ? 1 : 0, 'two_factor_enabled' => 0, 'created_at' => $this->now, 'updated_at' => $this->now];
    }

    private function phone(string $prefix, int $n): string
    {
        return $prefix.str_pad((string) $n, 11 - strlen($prefix), '0', STR_PAD_LEFT);
    }

    private function systemRoles(): array
    {
        $roles = [];
        foreach (['admin' => 'مدیر سیستم', 'specialist' => 'متخصص/پشتیبان', 'super-admin' => 'سوپر ادمین'] as $name => $label) {
            $id = DB::table('roles')->whereNull('salon_id')->where('name', $name)->value('id');
            $roles[$name] = $id ?? DB::table('roles')->insertGetId(['salon_id' => null, 'name' => $name, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $roles;
    }

    private function superAdmin(int $roleId): void
    {
        $phone = '09100000000';
        if (DB::table('users')->where('user_type', 'staff')->where('phone', $phone)->exists()) {
            return;
        }
        $id = $this->id('users');
        DB::table('users')->insert($this->staffUser($id, 'سوپرادمین بار', $phone, true));
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => $roleId]);
        $this->counts['users'] = ($this->counts['users'] ?? 0) + 1;
    }

    private function id(string $table): int
    {
        return $this->next[$table]++;
    }

    private function push(string $table, array $row): void
    {
        // flush فقط در پایان هر سالن (flushAll) تا ترتیب FKها رعایت شود
        $this->buffers[$table][] = $row;
    }

    private function flush(string $table): void
    {
        if (empty($this->buffers[$table])) {
            return;
        }
        foreach (array_chunk($this->buffers[$table], 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
        $this->counts[$table] = ($this->counts[$table] ?? 0) + count($this->buffers[$table]);
        $this->buffers[$table] = [];
    }

    /** ترتیب نوشتن به خاطر FKها */
    private function flushAll(): void
    {
        foreach (['users', 'salons', 'salon_admins', 'role_user', 'wallet_settings', 'salon_sms_usages', 'categories', 'beauty_services', 'specialists',
            'specialist_services', 'specialist_schedules', 'specialist_wallets', 'admin_wallet', 'bookings', 'payments', 'payment_transactions',
            'wallet_transactions', 'admin_wallet_transactions', 'withdrawal_requests', 'reviews', 'user_notifications', 'user_wallets', 'user_wallet_transactions'] as $table) {
            $this->flush($table);
        }
    }
}
