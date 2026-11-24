<?php

namespace Tests\Feature\Ui;

use App\Support\Text\ExpertTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * بسته‌ی ۰ (تصمیم ۲۰۲۶-۱۱-۲۴): در همه‌ی متن‌هایی که کاربر می‌بیند «کارشناس / کارشناسان» به‌جای «متخصص». کامنت‌ها، نام
 * کلاس/route/جدول و پیام‌های لاگ عوض نمی‌شوند.
 */
class ExpertTermTest extends TestCase
{
    use RefreshDatabase;

    /** صفت، نه نقش: «بهترین خدمات زیبایی با متخصص‌ترین تیم» */
    private const ALLOWED = ['متخصص‌ترین'];

    public function test_replace_maps_every_form(): void
    {
        $this->assertSame('کارشناس جدید', ExpertTerm::replace('متخصص جدید'));
        $this->assertSame('مدیریت کارشناسان', ExpertTerm::replace('مدیریت متخصصان'));
        $this->assertSame('پنل کارشناسان', ExpertTerm::replace('پنل متخصصین'));
        $this->assertSame('تعداد کارشناسان سالن', ExpertTerm::replace('تعداد متخصص‌های سالن'));
        $this->assertSame('همه‌ی کارشناسان', ExpertTerm::replace('همه‌ی متخصص‌ها'));
        $this->assertSame('با متخصص‌ترین تیم', ExpertTerm::replace('با متخصص‌ترین تیم'));
        $this->assertNull(ExpertTerm::replace(null));
    }

    public function test_no_view_shows_the_old_term(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            $text = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/s', '', $file->getContents());
            foreach (explode("\n", $text) as $no => $line) {
                $trim = ltrim($line);
                if (str_starts_with($trim, '//') || str_starts_with($trim, '*') || str_starts_with($trim, '/*')) {
                    continue;
                }
                if (str_contains(str_replace(self::ALLOWED, '', $line), ExpertTerm::OLD)) {
                    $offenders[] = $file->getRelativePathname().':'.($no + 1);
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_no_user_facing_string_in_app_code_uses_the_old_term(): void
    {
        $offenders = [];
        foreach (File::allFiles(app_path()) as $file) {
            // خود تعریف واژه و دستور تغییر نام
            if (in_array(str_replace('\\', '/', $file->getRelativePathname()), ['Support/Text/ExpertTerm.php', 'Console/Commands/RenameExpertLabels.php'], true)) { // ویندوز: «\\»
                continue;
            }
            $tokens = token_get_all($file->getContents());
            $inLog = false;
            foreach ($tokens as $i => $token) {
                if ($token === ';') {
                    $inLog = false;
                }
                if (! is_array($token)) {
                    continue;
                }
                if ($token[0] === T_STRING && $token[1] === 'Log' && is_array($tokens[$i + 1] ?? null) && $tokens[$i + 1][0] === T_DOUBLE_COLON) {
                    $inLog = true;
                }
                if (! $inLog && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                    && str_contains(str_replace(self::ALLOWED, '', $token[1]), ExpertTerm::OLD)) {
                    $offenders[] = $file->getRelativePathname().':'.$token[2];
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_command_renames_stored_system_labels_only(): void
    {
        DB::table('roles')->updateOrInsert(['name' => 'specialist', 'salon_id' => null], ['label' => 'متخصص/پشتیبان']);
        $salonId = app(\App\Support\CurrentSalon::class)->id();
        DB::table('roles')->insert(['name' => 'custom', 'salon_id' => $salonId, 'label' => 'متخصص ارشد', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('permissions')->updateOrInsert(['name' => 'view-specialists'], ['label' => 'مشاهده متخصصان', 'group' => 'متخصصان', 'description' => 'مشاهده لیست متخصصان']);

        app(\App\Services\Plans\DefaultPlans::class)->sync();
        DB::table('plans')->where('key', 'pro')->update(['description' => 'همه‌ی امکانات اصلی سالن، با ۱۰ متخصص و ۲٬۰۰۰ پیامک در ماه.']);

        $this->artisan('labels:expert-rename')->assertSuccessful();

        $this->assertSame('همه‌ی امکانات اصلی سالن، با ۱۰ کارشناس و ۲٬۰۰۰ پیامک در ماه.', DB::table('plans')->where('key', 'pro')->value('description'));

        $this->assertSame('کارشناس/پشتیبان', DB::table('roles')->where('name', 'specialist')->whereNull('salon_id')->value('label'));
        $this->assertSame('متخصص ارشد', DB::table('roles')->where('name', 'custom')->value('label'), 'نقش ساخت خود سالن');
        $permission = DB::table('permissions')->where('name', 'view-specialists')->first();
        $this->assertSame(['مشاهده کارشناسان', 'کارشناسان', 'مشاهده لیست کارشناسان'], [$permission->label, $permission->group, $permission->description]);
    }
}
