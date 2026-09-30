<?php

namespace Tests\Feature\Performance;

use App\Models\Booking;
use App\Models\SpecialistWallet;
use App\Support\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * perf:seed-load (داده‌ی بار، جزو DatabaseSeeder نیست) — داده‌ی ساخته‌شده باید با قاعده‌های برنامه سازگار باشد تا اندازه‌گیری روی آن معنی داشته باشد.
 */
class LoadTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_consistent_salons(): void
    {
        $before = DB::table('salons')->count();

        $this->artisan('perf:seed-load', ['--salons' => 2, '--months' => 1, '--future-days' => 7, '--customers' => 20])->assertSuccessful();

        $salons = DB::table('salons')->where('slug', 'like', 'load-%')->pluck('id');
        $this->assertCount(2, $salons);
        $this->assertSame($before + 2, DB::table('salons')->count());

        foreach ($salons as $salonId) {
            $this->assertSame(1, DB::table('salon_admins')->where('salon_id', $salonId)->where('role', 'owner')->count());
            $this->assertGreaterThan(0, DB::table('bookings')->where('salon_id', $salonId)->count());
            // هر نوبت متخصص و مشتری همان سالن را دارد
            $this->assertSame(0, DB::table('bookings')->join('specialists', 'specialists.id', '=', 'bookings.specialist_id')
                ->where('bookings.salon_id', $salonId)->where('specialists.salon_id', '!=', $salonId)->count());
            $this->assertSame(0, DB::table('bookings')->join('users', 'users.id', '=', 'bookings.user_id')
                ->where('bookings.salon_id', $salonId)->where('users.salon_id', '!=', $salonId)->count());
        }

        // موجودی کیف پول = درآمد تسویه‌شده − برداشت؛ pending = درآمد تسویه‌نشده؛ شبا معتبر
        foreach (SpecialistWallet::withoutGlobalScopes()->whereIn('specialist_id', DB::table('specialists')->whereIn('salon_id', $salons)->pluck('id'))->get() as $wallet) {
            $tx = DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->get();
            $settled = $tx->where('type', 'income')->filter(fn ($t) => json_decode($t->metadata, true)['status'] === 'settled')->sum('amount');
            $pending = $tx->where('type', 'income')->filter(fn ($t) => json_decode($t->metadata, true)['status'] === 'pending')->sum('amount');
            $withdrawn = -$tx->where('type', 'withdrawal')->sum('amount');
            $this->assertEqualsWithDelta($settled - $withdrawn, (float) $wallet->balance, 0.05);
            $this->assertEqualsWithDelta($pending, (float) $wallet->pending_amount, 0.05);
            $this->assertTrue(Iban::isValid($wallet->iban));
        }

        // موردهای عمدی برای دستورهای scheduler
        $this->assertSame(2, Booking::withoutGlobalScopes()->whereIn('salon_id', $salons)->where('status', 'pending_payment')->count());
    }

    public function test_it_refuses_to_run_in_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('perf:seed-load', ['--salons' => 1])->assertFailed();
        $this->assertSame(0, DB::table('salons')->where('slug', 'like', 'load-%')->count());
    }
}
