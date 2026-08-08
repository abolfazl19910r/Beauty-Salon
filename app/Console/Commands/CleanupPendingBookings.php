<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanupPendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:cleanup
                          {--dry-run : نمایش نوبت‌ها بدون لغو واقعی}
                          {--minutes=30 : تعداد دقیقه برای لغو}';

    /**
     * The console command description.
     */
    protected $description = 'لغو نوبت‌های پرداخت نشده بعد از مدت زمان مشخص';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 جستجوی نوبت‌های pending_payment بیشتر از {$minutes} دقیقه...");

        $expiredBookings = Booking::where('status', 'pending_payment')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<=', Carbon::now()->subMinutes($minutes))
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('✅ نوبتی برای لغو یافت نشد.');

            return self::SUCCESS;
        }

        $this->warn("⚠️  {$expiredBookings->count()} نوبت یافت شد:");

        $this->table(
            ['ID', 'کاربر', 'زمان ایجاد', 'دقیقه گذشته'],
            $expiredBookings->map(function ($booking) {
                return [
                    $booking->id,
                    $booking->user->name ?? 'نامشخص',
                    $booking->created_at->format('Y-m-d H:i:s'),
                    $booking->created_at->diffInMinutes(now()),
                ];
            })
        );

        if ($dryRun) {
            $this->comment('🧪 حالت Dry Run: هیچ تغییری اعمال نشد.');

            return self::SUCCESS;
        }

        if (! $this->confirm('آیا مطمئن هستید که می‌خواهید این نوبت‌ها را لغو کنید؟', true)) {
            $this->info('❌ عملیات لغو شد.');

            return self::SUCCESS;
        }

        $cancelled = 0;
        $failed = 0;

        foreach ($expiredBookings as $booking) {
            try {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => 'system',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'عدم تکمیل پرداخت در زمان مقرر',
                ]);
                $cancelled++;
                $this->line("✓ نوبت #{$booking->id} لغو شد");
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ خطا در لغو نوبت #{$booking->id}: {$e->getMessage()}");
            }
        }

        $this->info("
            ✅ عملیات تکمیل شد:
               - لغو شده: {$cancelled}
               - خطا: {$failed}
        ");

        return self::SUCCESS;
    }
}
