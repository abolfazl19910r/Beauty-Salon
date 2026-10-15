<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SavePlanRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\SalonFeatureOverride;
use App\Services\Plans\PlanAdminService;
use App\Support\Features\Feature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * پلن‌ها و امکانات (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴): فهرست، ساخت/ویرایش (قیمت هر بازه، متخصص، سهمیه، امکانات)، حذف پلن بی‌سالن؛
 * و روی هر سالن: تخصیص دستی پلن و استثنای امکانات.
 */
class SuperAdminPlanController extends Controller
{
    public function __construct(private readonly PlanAdminService $plans) {}

    public function index(): View
    {
        $plans = Plan::query()->with('features')->orderByDesc('is_trial')->orderBy('sort')->orderBy('id')->get();
        $salonCounts = Salon::withoutGlobalScopes()->whereNotNull('plan_id')->selectRaw('plan_id, count(*) as c')->groupBy('plan_id')->pluck('c', 'plan_id');
        $revenue = Invoice::withoutGlobalScopes()->where('status', 'paid')->whereNotNull('plan_id')
            ->selectRaw('plan_id, sum(amount) as total')->groupBy('plan_id')->pluck('total', 'plan_id');
        $noPlan = Salon::withoutGlobalScopes()->whereNull('plan_id')->count();

        return view('superadmin.plans.index', compact('plans', 'salonCounts', 'revenue', 'noPlan'));
    }

    public function create(): View
    {
        return view('superadmin.plans.form', ['plan' => new Plan(['is_active' => true, 'included_specialists' => 10, 'sms_quota_per_month' => 2000]), 'salonCount' => 0]);
    }

    public function store(SavePlanRequest $request): RedirectResponse
    {
        $this->plans->save(null, $request->planAttributes(), $request->validated('features', []), $request->user());
        $this->audit()->record('plan.saved', 'ساخت پلن «'.$request->planAttributes()['name'].'»', null, ['features' => $request->validated('features', [])]);

        return redirect()->route('superadmin.plans.index')->with('success', 'پلن ساخته شد.');
    }

    public function edit(Plan $plan): View
    {
        return view('superadmin.plans.form', ['plan' => $plan->load('features'), 'salonCount' => $this->plans->salonCount($plan)]);
    }

    public function update(SavePlanRequest $request, Plan $plan): RedirectResponse
    {
        $beforeAttrs = $plan->only(array_keys($request->planAttributes()));
        $beforeFeatures = $plan->featureKeys();
        $removed = $this->plans->save($plan, $request->planAttributes(), $request->validated('features', []), $request->user());
        $changes = \App\Services\SuperAdmin\SuperAdminAudit::diff($beforeAttrs, $plan->fresh()->only(array_keys($beforeAttrs)));
        $afterFeatures = $plan->fresh()->featureKeys();
        if ($beforeFeatures !== $afterFeatures) {
            $changes['features'] = [array_values(array_diff($beforeFeatures, $afterFeatures)), array_values(array_diff($afterFeatures, $beforeFeatures))];
        }
        $this->audit()->record('plan.saved', "ویرایش پلن «{$plan->name}»", null, $changes ?: null);
        $count = $this->plans->salonCount($plan);

        $message = 'پلن ذخیره شد.';
        if ($removed !== [] && $count > 0) {
            $labels = collect($removed)->map(fn ($k) => Feature::from($k)->label())->implode('، ');
            $message .= sprintf(' %s سالن این امکانات را از دست دادند: %s (جز سالن‌هایی که استثنای دستی دارند).', number_format($count), $labels);
        }

        return redirect()->route('superadmin.plans.edit', $plan)->with('success', $message);
    }

    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        try {
            $this->plans->delete($plan, $request->user());
            $this->audit()->record('plan.deleted', "حذف پلن «{$plan->name}»");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('superadmin.plans.index')->with('success', 'پلن حذف شد.');
    }

    // ─── روی یک سالن ─────────────────────────────────────────────────

    public function assign(Request $request, Salon $salon): RedirectResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);
        $plan = Plan::findOrFail($validated['plan_id']);

        $oldPlan = $salon->plan?->name;
        $this->plans->assignPlan($salon, $plan, $request->user());
        $this->audit()->record('salon.plan', "پلن «{$salon->name}»: ".($oldPlan ?? '—')." ← {$plan->name}", $salon);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', "پلن سالن به «{$plan->name}» تغییر کرد.");
    }

    public function storeOverride(Request $request, Salon $salon): RedirectResponse
    {
        $validated = $request->validate([
            'feature' => ['required', Rule::in(Feature::keys())],
            'enabled' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['feature' => 'امکان', 'expires_at' => 'تاریخ انقضا', 'note' => 'یادداشت']);

        // تاریخ شمسی تا پایان همان روز
        $expiresAt = \App\Support\JalaliDateInput::toCarbon($validated['expires_at'] ?? null, endOfDay: true);
        if (! empty($validated['expires_at']) && (! $expiresAt || $expiresAt->isPast())) {
            return back()->withInput()->withErrors(['expires_at' => 'تاریخ انقضا باید یک تاریخ شمسی آینده باشد (مثلاً ۱۴۰۵/۰۸/۳۰).']);
        }

        $this->plans->setOverride(
            $salon,
            Feature::from($validated['feature']),
            (bool) $validated['enabled'],
            $expiresAt,
            $validated['note'] ?? null,
            $request->user(),
        );
        $this->audit()->record('salon.feature_override', sprintf('استثنای «%s» برای «%s»: %s%s', Feature::from($validated['feature'])->label(), $salon->name,
            $validated['enabled'] ? 'روشن' : 'خاموش', $expiresAt ? ' تا '.jalali_date($expiresAt) : ''), $salon);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', 'استثنای امکان ذخیره شد.');
    }

    public function destroyOverride(Request $request, Salon $salon, SalonFeatureOverride $override): RedirectResponse
    {
        abort_unless((int) $override->salon_id === (int) $salon->id, 404);

        $this->plans->removeOverride($override, $request->user());
        $this->audit()->record('salon.feature_override', sprintf('برداشتن استثنای «%s» از «%s»', Feature::tryFrom($override->feature)?->label() ?? $override->feature, $salon->name), $salon);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', 'استثنا برداشته شد؛ سالن دوباره از پلنش پیروی می‌کند.');
    }

    private function audit(): \App\Services\SuperAdmin\SuperAdminAudit
    {
        return app(\App\Services\SuperAdmin\SuperAdminAudit::class);
    }
}
