<?php

namespace App\Console\Commands;

use Database\Seeders\LoadTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * داده‌ی بار برای اندازه‌گیری کارایی (جزو DatabaseSeeder نیست). نمونه:
 *   php artisan migrate:fresh --force && php artisan perf:seed-load --salons=1000
 * در production اجرا نمی‌شود مگر با --force (داده‌ی فیک به جدول‌های واقعی اضافه می‌کند).
 */
class SeedLoadTestData extends Command
{
    protected $signature = 'perf:seed-load
        {--salons=100 : تعداد سالن (مثلاً ۱۰۰ یا ۱۰۰۰)}
        {--months=4 : چند ماه نوبت گذشته}
        {--future-days=30 : چند روز نوبت آینده}
        {--specialists=6 : متخصص هر سالن}
        {--services=10 : خدمت هر سالن}
        {--customers=150 : مشتری هر سالن}
        {--bookings-per-day=6 : میانگین نوبت روزانه‌ی هر سالن}
        {--force : اجرا در production}';

    protected $description = 'ساخت داده‌ی فیک بار (سالن، متخصص، مشتری، نوبت، پرداخت، کیف پول، برداشت، اعلان، نظر) برای اندازه‌گیری کارایی';

    public const REPORTED_TABLES = ['salons', 'users', 'specialists', 'beauty_services', 'specialist_services', 'specialist_schedules', 'bookings', 'payments',
        'payment_transactions', 'specialist_wallets', 'wallet_transactions', 'admin_wallet_transactions', 'withdrawal_requests', 'user_wallets',
        'user_wallet_transactions', 'user_notifications', 'reviews'];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('در production فقط با --force.');

            return self::FAILURE;
        }

        // Telescope (فعال در .env لوکال) هر insert دسته‌ای را با جای‌گذاری هزاران binding ثبت می‌کند — ۸ برابر کندتر
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        $seeder = new LoadTestSeeder;
        $seeder->salons = max(1, (int) $this->option('salons'));
        $seeder->months = max(1, (int) $this->option('months'));
        $seeder->futureDays = max(0, (int) $this->option('future-days'));
        $seeder->specialistsPerSalon = max(2, (int) $this->option('specialists'));
        $seeder->servicesPerSalon = max(1, (int) $this->option('services'));
        $seeder->customersPerSalon = max(1, (int) $this->option('customers'));
        $seeder->bookingsPerDay = max(1, (int) $this->option('bookings-per-day'));
        $seeder->progress = fn (int $done, int $total) => $this->line("  {$done}/{$total} سالن");

        $started = microtime(true);
        $seeder->run();
        $this->info(sprintf('ساخته شد در %.1f ثانیه (حافظه‌ی اوج %.0f MB).', microtime(true) - $started, memory_get_peak_usage(true) / 1048576));

        $this->table(['جدول', 'ردیف (کل جدول)', 'حجم'], collect(self::REPORTED_TABLES)->map(fn ($t) => [$t, DB::table($t)->count(), $this->size($t)])->all());

        return self::SUCCESS;
    }

    private function size(string $table): string
    {
        if (DB::getDriverName() !== 'mysql' && DB::getDriverName() !== 'mariadb') {
            return '-';
        }
        DB::statement('ANALYZE TABLE `'.$table.'`'); // آمار information_schema به‌روز شود
        $row = DB::selectOne('SELECT data_length + index_length AS bytes FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);

        return $row ? sprintf('%.1f MB', $row->bytes / 1048576) : '-';
    }
}
