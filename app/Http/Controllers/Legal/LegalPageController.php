<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use Illuminate\View\View;

/**
 * صفحه‌های حقوقی پلتفرم روی دامنه‌ی مرکزی (بسته‌ی مجوز 5د): حریم خصوصی، قوانین و شرایط، تماس با ما. متن‌ها پیش‌نویس‌اند
 * (config/legal.php ‏draft) و اطلاعات هویت و تماس از «تنظیمات پلتفرم» ابرمدیر می‌آید، نه از کد.
 */
class LegalPageController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy', $this->shared());
    }

    public function terms(): View
    {
        return view('legal.terms', $this->shared());
    }

    public function contact(): View
    {
        return view('legal.contact', $this->shared());
    }

    /** @return array<string, mixed> */
    private function shared(): array
    {
        $keys = ['legal_name', 'national_id', 'landline_phone', 'contact_email', 'postal_address', 'postal_code', 'support_phone'];

        return [
            'platform' => collect($keys)->mapWithKeys(fn (string $key) => [$key => PlatformSettings::get($key)])->all()
                + ['brand' => (string) config('brand.name'), 'domain' => (string) (config('app.central_domain') ?: config('brand.domain'))],
            'versions' => (array) config('legal.versions'),
            'draft' => (bool) config('legal.draft'),
            'retentionDays' => \App\Support\Retention::days('security_logs_days'),
            'complaintHours' => (int) config('legal.complaint_response_hours', 72),
        ];
    }
}
