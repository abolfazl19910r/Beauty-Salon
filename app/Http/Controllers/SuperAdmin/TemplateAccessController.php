<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Salon;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\Themes\ThemeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ⭐ «قالب‌ها» در پنل ابرمدیر (بسته‌ی «و»، ۲۰۲۶-۱۰-۱۵): برای هر پلن و هر بازه «چند چیدمان × چند پالت» و چند قالب پنل مدیر؛
 * و ترتیب باز شدن چیدمان‌ها و پالت‌ها (مشترک همه‌ی پلن‌ها). خود قالب‌ها در config/themes.php هستند.
 */
class TemplateAccessController extends Controller
{
    private const PAIRS = [
        'base' => ['theme_layouts', 'theme_palettes'],
        '6m' => ['theme_layouts_6m', 'theme_palettes_6m'],
        '12m' => ['theme_layouts_12m', 'theme_palettes_12m'],
    ];

    public function index(): View
    {
        $usage = Salon::withoutGlobalScopes()->whereNotNull('site_theme')
            ->selectRaw('site_theme, COUNT(*) as cnt')->groupBy('site_theme')->pluck('cnt', 'site_theme');

        return view('superadmin.templates.index', [
            'plans' => Plan::query()->orderByDesc('is_trial')->orderBy('sort')->orderBy('id')->get(),
            'layouts' => ThemeCatalog::layouts(),
            'palettes' => ThemeCatalog::palettes(),
            'adminThemes' => ThemeCatalog::adminThemes(),
            'layoutTotal' => ThemeCatalog::layoutCount(),
            'paletteTotal' => ThemeCatalog::paletteCount(),
            'adminTotal' => ThemeCatalog::adminTotal(),
            'usage' => $usage,
        ]);
    }

    public function update(Request $request, SuperAdminAudit $audit): RedirectResponse
    {
        $layoutTotal = ThemeCatalog::layoutCount();
        $paletteTotal = ThemeCatalog::paletteCount();
        $adminTotal = ThemeCatalog::adminTotal();
        $plans = Plan::query()->get()->keyBy('id');

        $rules = [];
        foreach ($plans->keys() as $id) {
            foreach (self::PAIRS as [$layoutField, $paletteField]) {
                $rules["plans.$id.$layoutField"] = ['required', 'integer', 'min:0', "max:$layoutTotal"];
                $rules["plans.$id.$paletteField"] = ['required', 'integer', 'min:0', "max:$paletteTotal"];
            }
            $rules["plans.$id.theme_admin_count"] = ['required', 'integer', 'min:0', "max:$adminTotal"];
        }
        $rules['layout_order'] = ['sometimes', 'array', 'size:'.$layoutTotal];
        $rules['layout_order.*'] = ['string', Rule::in(array_keys((array) config('themes.layouts')))];
        $rules['palette_order'] = ['sometimes', 'array', 'size:'.$paletteTotal];
        $rules['palette_order.*'] = ['string', Rule::in(array_keys((array) config('themes.palettes')))];
        $data = $request->validate($rules, [
            '*.max' => 'بیشتر از تعداد کل نمی‌شود.',
        ]);

        $errors = [];
        foreach ($data['plans'] ?? [] as $id => $row) {
            $base = [(int) $row['theme_layouts'], (int) $row['theme_palettes']];
            $six = [(int) $row['theme_layouts_6m'], (int) $row['theme_palettes_6m']];
            $twelve = [(int) $row['theme_layouts_12m'], (int) $row['theme_palettes_12m']];
            if ($six[0] < $base[0] || $six[1] < $base[1] || $twelve[0] < $six[0] || $twelve[1] < $six[1]) {
                $errors["plans.$id.theme_layouts_12m"] = "پلن «{$plans[$id]->name}»: اشتراک بلندتر نباید چیدمان یا پالت کمتری بدهد.";
            }
        }
        foreach (['layout_order' => 'چیدمان', 'palette_order' => 'پالت'] as $field => $label) {
            if (isset($data[$field]) && count(array_unique($data[$field])) !== count($data[$field])) {
                $errors[$field] = "هر {$label} باید فقط یک بار در ترتیب بیاید.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($data['plans'] ?? [] as $id => $row) {
            $plan = $plans[$id];
            $before = $plan->only(Plan::THEME_FIELDS);
            $after = array_map('intval', array_intersect_key($row, array_flip(Plan::THEME_FIELDS)));
            $changes = SuperAdminAudit::diff($before, $after);
            if ($changes) {
                $plan->update($after);
                $audit->record('template_access.updated', "دسترسی قالب‌های پلن «{$plan->name}»", null, $changes);
            }
        }

        if (isset($data['layout_order']) || isset($data['palette_order'])) {
            $beforeOrder = ['layouts' => ThemeCatalog::layoutOrder(), 'palettes' => ThemeCatalog::paletteOrder()];
            $layouts = array_values($data['layout_order'] ?? $beforeOrder['layouts']);
            $palettes = array_values($data['palette_order'] ?? $beforeOrder['palettes']);
            if ($layouts !== $beforeOrder['layouts'] || $palettes !== $beforeOrder['palettes']) {
                ThemeCatalog::saveOrder($layouts, $palettes);
                $audit->record('template_access.updated', 'ترتیب باز شدن چیدمان‌ها و پالت‌ها', null, SuperAdminAudit::diff(
                    ['layouts' => implode('، ', $beforeOrder['layouts']), 'palettes' => implode('، ', $beforeOrder['palettes'])],
                    ['layouts' => implode('، ', $layouts), 'palettes' => implode('، ', $palettes)],
                ));
            }
        }

        return back()->with('success', 'دسترسی قالب‌ها ذخیره شد.');
    }
}
