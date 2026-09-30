<?php

namespace Tests\Feature\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ایندکس‌هایی که کارهای زمان‌بندی‌شده‌ی همه‌ی سالن‌ها (بدون salon_id) به آن‌ها تکیه دارند. اندازه‌گیری و EXPLAIN قبل/بعد
 * در Rasta_unified_prompt.md (۲۰۲۶-۰۹-۳۰، کارایی): بدون آن‌ها هر اجرا کل جدول را می‌خواند.
 */
class SchedulerQueryIndexesTest extends TestCase
{
    use RefreshDatabase;

    private function assertIndex(string $table, array $columns): void
    {
        $found = collect(Schema::getIndexes($table))->contains(fn ($i) => $i['columns'] === $columns);

        $this->assertTrue($found, "{$table}(".implode(', ', $columns).') index is missing');
    }

    public function test_reminders_and_unpaid_cancellation_can_seek_bookings_by_status_and_time(): void
    {
        $this->assertIndex('bookings', ['status', 'booking_time']);
    }

    public function test_payment_reconciliation_can_seek_transactions_by_status_and_time(): void
    {
        $this->assertIndex('payment_transactions', ['status', 'updated_at']);
    }
}
