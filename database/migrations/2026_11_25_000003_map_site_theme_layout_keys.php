<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵الف (۲۰۲۶-۱۱-۲۵): ۴ قالب ترکیبی جای ۴ چیدمان قدیمی. فقط داده عوض می‌شود (ستون‌ها همان):
 * - salons.site_theme: «vitrin:rose» ← «signature:rose» (پالت همان؛ mahru و null دست نمی‌خورند)
 * - platform_settings.theme_layout_order (ترتیب باز شدن چیدمان‌ها در «قالب‌ها»ی ابرمدیر)
 * نگاشت: vitrin→signature، majalle→editorial، aram→showcase، bento→app. down همان را برمی‌گرداند.
 * با DB::table (نه مدل) تا scope سالن و رویدادها دخالت نکنند؛ ثابت‌ها اینجا تا migration به config وابسته نباشد.
 */
return new class extends Migration
{
    private const MAP = ['vitrin' => 'signature', 'majalle' => 'editorial', 'aram' => 'showcase', 'bento' => 'app'];

    public function up(): void
    {
        $this->apply(self::MAP);
    }

    public function down(): void
    {
        $this->apply(array_flip(self::MAP));
    }

    /** @param array<string, string> $map */
    private function apply(array $map): void
    {
        if (Schema::hasTable('salons')) {
            foreach ($map as $from => $to) {
                DB::table('salons')->where('site_theme', 'like', $from.':%')->orderBy('id')->each(function ($salon) use ($from, $to) {
                    DB::table('salons')->where('id', $salon->id)
                        ->update(['site_theme' => $to.substr((string) $salon->site_theme, strlen($from))]);
                });
            }
        }

        if (Schema::hasTable('platform_settings')) {
            $row = DB::table('platform_settings')->where('key', 'theme_layout_order')->first();
            $order = $row ? json_decode((string) $row->value, true) : null;
            if (is_array($order)) {
                $mapped = array_map(fn ($key) => is_string($key) ? ($map[$key] ?? $key) : $key, $order);
                DB::table('platform_settings')->where('key', 'theme_layout_order')->update(['value' => json_encode(array_values($mapped))]);
            }
        }

        // ThemeCatalog ترتیب را برای همیشه کش می‌کند
        Cache::forget('themes:order');
    }
};
