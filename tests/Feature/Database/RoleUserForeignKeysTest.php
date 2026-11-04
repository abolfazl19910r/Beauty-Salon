<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ح (۲۰۲۶-۱۱-۰۴): کلیدهای خارجی role_user نام پیش‌فرض Laravel دارند (قبلاً «role_user_scoped_*» از جدول موقت migration قدیمی).
 * SQLite نام قید را نگه نمی‌دارد؛ نام‌ها فقط روی MySQL/MariaDB سنجیده می‌شوند.
 */
class RoleUserForeignKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_user_foreign_keys_use_the_default_names_and_cascade(): void
    {
        $keys = collect(Schema::getForeignKeys('role_user'))->keyBy(fn ($fk) => $fk['columns'][0]);

        $this->assertEqualsCanonicalizing(['user_id', 'role_id', 'salon_id'], $keys->keys()->all());
        $this->assertSame('users', $keys['user_id']['foreign_table']);
        $this->assertSame('roles', $keys['role_id']['foreign_table']);
        $this->assertSame('salons', $keys['salon_id']['foreign_table']);
        $this->assertSame(['cascade'], $keys->pluck('on_delete')->map(fn ($v) => strtolower((string) $v))->unique()->values()->all());

        if (DB::getDriverName() !== 'sqlite') {
            $this->assertEqualsCanonicalizing(
                ['role_user_user_id_foreign', 'role_user_role_id_foreign', 'role_user_salon_id_foreign'],
                $keys->pluck('name')->all(),
            );
        }
    }
}
