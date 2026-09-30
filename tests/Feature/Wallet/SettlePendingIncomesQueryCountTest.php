<?php

namespace Tests\Feature\Wallet;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Services\Admin\Wallet\WalletAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * wallet:settle-pending برای هر درآمد pending نوبتش را جدا می‌خواند (N+1). درآمد نوبت‌های آینده تا گذشتن ساعت نوبت pending می‌ماند،
 * پس این مجموعه بزرگ است: در داده‌ی بار ۱۰۰۰ سالن ۷۹٬۰۲۷ کوئری bookings در یک اجرای شبانه (۳۴٫۷ ثانیه).
 */
class SettlePendingIncomesQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function pendingIncomeForFutureBooking(Specialist $specialist, BeautyService $service, int $hour): void
    {
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(5)->setTime($hour, 0),
        ]);
        $tx = $specialist->getOrCreateWallet()->addIncome(50000, $booking->id);
        $tx->update(['metadata' => ['settlement_date' => now()->subDay()->toDateString(), 'status' => 'pending']]);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_pending_incomes(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $specialist = Specialist::factory()->create();
        foreach (range(8, 19) as $hour) {
            $this->pendingIncomeForFutureBooking($specialist, $service, $hour);
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $result = app(WalletAdminService::class)->settlePendingIncomes();

        $this->assertSame(0, $result['settledCount']); // هیچ‌کدام: نوبت‌ها هنوز نرسیده‌اند
        $this->assertLessThanOrEqual(3, $queries, "settle-pending ran {$queries} queries for 12 pending incomes");
    }
}
