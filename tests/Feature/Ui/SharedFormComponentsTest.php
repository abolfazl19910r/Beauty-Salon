<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * بسته‌ی ۰ (۲۰۲۶-۱۱-۲۴): مؤلفه‌های فرم مشترک — ساعت ۲۴ساعته‌ی فارسی به‌جای `type="time"` (AM/PM انگلیسی)، دکمه‌ی فارسی
 * انتخاب فایل به‌جای «Choose File»، و جای‌نگه‌دار تاریخ با سال درست (نه `1403/09/15`).
 */
class SharedFormComponentsTest extends TestCase
{
    public function test_time_input_renders_persian_24h_selects_and_a_latin_hidden_value(): void
    {
        $html = Blade::render('<x-time-input name="schedules[1][start_time]" id="t1" value="14:30:00" />');

        $this->assertStringContainsString('data-ui-time', $html);
        $this->assertMatchesRegularExpression('/<input type="hidden"\s+name="schedules\[1\]\[start_time\]"\s+id="t1"\s+value="14:30"/', $html);
        $this->assertStringContainsString('<option value="14" selected>۱۴</option>', $html);
        $this->assertStringContainsString('<option value="30" selected>۳۰</option>', $html);
        $this->assertStringContainsString('<option value="23"', $html);
        $this->assertStringNotContainsString('PM', $html);
        $this->assertStringNotContainsString('type="time"', $html);
    }

    public function test_time_input_keeps_an_off_step_minute_and_supports_empty_values(): void
    {
        $html = Blade::render('<x-time-input name="t" value="10:07" />');
        $this->assertStringContainsString('<option value="07" selected>۰۷</option>', $html);

        $empty = Blade::render('<x-time-input name="b" :value="null" :nullable="true" />');
        $this->assertStringContainsString('value=""', $empty);
        $this->assertStringContainsString('<option value="" selected>—</option>', $empty);

        $disabled = Blade::render('<x-time-input name="c" value="09:00" :disabled="true" />');
        $this->assertSame(3, substr_count($disabled, 'disabled'));
    }

    public function test_file_input_has_a_persian_button_and_keeps_the_real_input(): void
    {
        $html = Blade::render('<x-file-input id="logo" name="logo" accept="image/png" required />');

        $this->assertStringContainsString('انتخاب فایل', $html);
        $this->assertStringContainsString('فایلی انتخاب نشده', $html);
        $this->assertMatchesRegularExpression('/<input type="file"[^>]*id="logo"[^>]*name="logo"[^>]*accept="image\/png"[^>]*required/', $html);
        $this->assertStringContainsString('data-ui-file', $html);
    }

    public function test_views_use_the_shared_components_and_current_year_placeholders(): void
    {
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            $content = $file->getContents();
            $name = $file->getRelativePathname();

            $this->assertDoesNotMatchRegularExpression('/<input[^>]*type="time"/', $content, "$name: <x-time-input> به‌جای type=time");
            // فقط ورودی فایل مخفیِ کادرهای کشیدن‌ورها کردن عکس (class="hidden") بومی می‌ماند
            preg_match_all('/<input[^>]*type="file"[^>]*>/', $content, $m);
            foreach ($m[0] as $input) {
                $this->assertStringContainsString('class="hidden"', $input, "$name: <x-file-input> به‌جای دکمه‌ی بومی");
            }
            $this->assertDoesNotMatchRegularExpression('/placeholder="(مثال: )?140[0-4]\//u', $content, "$name: جای‌نگه‌دار تاریخ با سال قدیمی");
        }
    }

    public function test_ui_handlers_wire_time_and_file_components(): void
    {
        $handlers = File::get(resource_path('views/partials/ui-handlers.blade.php'));

        $this->assertStringContainsString("closest('[data-ui-time]')", $handlers);
        $this->assertStringContainsString("closest('[data-ui-file]')", $handlers);
    }
}
