<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;

/**
 * 🐞 ۲۰۲۶-۱۰-۲۷ (XAMPP): `migrate:fresh` روی MariaDB با explicit_defaults_for_timestamp=OFF (پیش‌فرض MariaDB قبل از 10.10، مثل XAMPP)
 * با «1067 Invalid default value for 'purge_after'» شکست. در آن حالت ستون TIMESTAMP بی‌NULL و بی‌DEFAULT:
 * - اگر اولین TIMESTAMP جدول باشد، بی‌صدا DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP می‌گیرد (با هر UPDATE ردیف عوض می‌شود)؛
 * - وگرنه پیش‌فرض '0000-00-00' می‌گیرد و با NO_ZERO_DATE (حالت strict لاراول) خطای 1067 می‌دهد.
 * SQLite، MariaDB 10.11 و MySQL 8 (explicit_defaults روشن) هیچ‌کدام این را نشان نمی‌دهند؛ پس این تست خود migrationها را می‌خواند:
 * هر ستون timestamp باید nullable، useCurrent یا default صریح داشته باشد. dateTime این رفتار ضمنی را ندارد.
 */
class TimestampColumnDefaultsTest extends TestCase
{
    public function test_every_timestamp_column_has_an_explicit_default_or_is_nullable(): void
    {
        $offenders = [];
        foreach (glob(__DIR__.'/../../../database/migrations/*.php') as $file) {
            $source = file_get_contents($file);
            // هر زنجیره‌ی ستون تا «;» — چندخطی هم
            preg_match_all('/\$table->(timestamp|timestampTz)\(\s*[\'"]([a-z0-9_]+)[\'"][^;]*;/', $source, $matches, PREG_SET_ORDER);
            foreach ($matches as [$statement, , $column]) {
                if (! preg_match('/->(nullable|useCurrent|useCurrentOnUpdate|default)\(/', $statement)) {
                    $offenders[] = basename($file).': '.$column;
                }
            }
        }

        $this->assertSame([], $offenders, 'ستون timestamp بی nullable/useCurrent/default روی MariaDB با explicit_defaults_for_timestamp=OFF خطای 1067 یا ON UPDATE ضمنی می‌گیرد');
    }

    public function test_the_scan_sees_timestamp_columns_at_all(): void
    {
        $count = 0;
        foreach (glob(__DIR__.'/../../../database/migrations/*.php') as $file) {
            $count += preg_match_all('/\$table->timestamp\(\s*[\'"]/', file_get_contents($file));
        }

        $this->assertGreaterThan(20, $count, 'الگوی جست‌وجو هنوز با migrationها جور است');
    }
}
