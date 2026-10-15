<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SuperAdmin\SuperAdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ⭐ «قالب‌ها» در پنل ابرمدیر (۲۰۲۶-۱۰-۱۵): چند قالب برای هر پلن و هر بازه‌ی اشتراک باز است (plans.theme_*).
 * خود قالب‌ها (۱۲ سایت + ۳ پنل مدیر، جدا از پیش‌فرض) با بسته‌ی «و» ساخته می‌شوند؛ این صفحه از همین حالا قاعده‌ی دسترسی را نگه می‌دارد.
 */
class TemplateAccessController extends Controller
{
    private const FIELDS = ['theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m', 'theme_admin_count'];

    public function index(): View
    {
        return view('superadmin.templates.index', [
            'plans' => Plan::query()->orderByDesc('is_trial')->orderBy('sort')->orderBy('id')->get(),
            'siteTotal' => (int) config('themes.site_total'),
            'adminTotal' => (int) config('themes.admin_total'),
        ]);
    }

    public function update(Request $request, SuperAdminAudit $audit): RedirectResponse
    {
        $siteTotal = (int) config('themes.site_total');
        $adminTotal = (int) config('themes.admin_total');
        $plans = Plan::query()->get()->keyBy('id');

        $rules = [];
        foreach ($plans->keys() as $id) {
            foreach (['theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m'] as $field) {
                $rules["plans.$id.$field"] = ['required', 'integer', 'min:0', "max:$siteTotal"];
            }
            $rules["plans.$id.theme_admin_count"] = ['required', 'integer', 'min:0', "max:$adminTotal"];
        }
        $data = $request->validate($rules, [
            '*.max' => 'بیشتر از تعداد کل قالب‌ها نمی‌شود.',
        ]);

        $errors = [];
        foreach ($data['plans'] ?? [] as $id => $row) {
            if ((int) $row['theme_site_count_6m'] < (int) $row['theme_site_count'] || (int) $row['theme_site_count_12m'] < (int) $row['theme_site_count_6m']) {
                $errors["plans.$id.theme_site_count_12m"] = "پلن «{$plans[$id]->name}»: اشتراک بلندتر نباید قالب کمتری بدهد.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($data['plans'] ?? [] as $id => $row) {
            $plan = $plans[$id];
            $before = $plan->only(self::FIELDS);
            $after = array_map('intval', array_intersect_key($row, array_flip(self::FIELDS)));
            $changes = SuperAdminAudit::diff($before, $after);
            if ($changes) {
                $plan->update($after);
                $audit->record('template_access.updated', "دسترسی قالب‌های پلن «{$plan->name}»", null, $changes);
            }
        }

        return back()->with('success', 'دسترسی قالب‌ها ذخیره شد.');
    }
}
