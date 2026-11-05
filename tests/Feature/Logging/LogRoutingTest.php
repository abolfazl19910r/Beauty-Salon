<?php

namespace Tests\Feature\Logging;

use Tests\TestCase;

/**
 * بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵: لاگ پرداخت و امنیت به کانال نگهداری‌شده‌ی خودش می‌رود (payments ‏۳۶۵ روز، security ‏۱۹۰ روز؛
 * هشدارها در laravel.log هم تکرار می‌شوند)، نه laravel.log که ۱۴ روزه پاک می‌شود. فهرست کامل: docs/deployment/LOGGING.md.
 */
class LogRoutingTest extends TestCase
{
    /** فایل‌هایی که همه‌ی لاگشان رویداد پرداخت، تسویه یا کیف پول است */
    private const PAYMENT_FILES = [
        'app/Payments/Drivers',
        'app/Services/Payment',
        'app/Services/PaymentService.php',
        'app/Services/SecurePaymentService.php',
        'app/Services/Admin/Wallet/WalletAdminService.php',
        'app/Console/Commands/ReconcilePaymentTransactions.php',
        'app/Jobs/ProcessWithdrawalJob.php',
        'app/Http/Controllers/Payment/AppPaymentController.php',
        'app/Http/Controllers/User/PaymentController.php',
        'app/Http/Controllers/User/SecurePaymentController.php',
        'app/Http/Controllers/User/UserWalletController.php',
        'app/Http/Controllers/Admin/Wallet',
        'app/Http/Controllers/Admin/PaymentGateway/AdminPaymentAttentionController.php',
        'app/Http/Controllers/SuperAdmin/PlatformOperationsController.php',
        'app/Http/Controllers/Specialist/Wallet',
    ];

    /** @return array<string, string> مسیر ← متن */
    private function sources(array $paths): array
    {
        $out = [];
        foreach ($paths as $path) {
            $full = base_path($path);
            $files = is_dir($full)
                ? iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS)))
                : [new \SplFileInfo($full)];
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $out[$path.':'.$file->getFilename()] = (string) file_get_contents($file->getPathname());
                }
            }
        }

        return $out;
    }

    /** لاگ مستقیم روی کانال پیش‌فرض: Log::info(…)، \Illuminate\Support\Facades\Log::error(…)، logger(…) */
    private function defaultChannelCalls(string $source): array
    {
        preg_match_all('/(?:\\\\?Illuminate\\\\Support\\\\Facades\\\\)?\bLog::(?!channel\b)(\w+)\s*\(|\blogger\s*\(/', $source, $m);

        return $m[0];
    }

    public function test_payment_events_go_to_the_payments_channel(): void
    {
        $sources = $this->sources(self::PAYMENT_FILES);
        $this->assertGreaterThan(20, count($sources));

        $wrong = [];
        $routed = 0;
        foreach ($sources as $file => $source) {
            if ($calls = $this->defaultChannelCalls($source)) {
                $wrong[$file] = $calls;
            }
            $routed += substr_count($source, "Log::channel('payments')");
        }

        $this->assertSame([], $wrong, 'لاگ پرداخت در laravel.log (۱۴ روز) به‌جای payments.log');
        $this->assertGreaterThan(80, $routed);
    }

    public function test_payment_lines_of_the_booking_observer_go_to_the_payments_channel(): void
    {
        $source = (string) file_get_contents(app_path('Observers/Booking/BookingObserver.php'));

        foreach ([
            'Failed to dispatch PaymentSucceeded event',
            'Failed to add income and commission',
            'Refund already exists',
            'Failed to handle cancellation',
            'سهم متخصص از موجودی واقعی',
        ] as $message) {
            $this->assertMatchesRegularExpression(
                "/Log::channel\('payments'\)->\w+\(\s*'[^']*".preg_quote($message, '/').'/u',
                $source,
                $message
            );
        }
    }
}
