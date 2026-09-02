<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreSalonRequest;
use App\Http\Requests\SuperAdmin\UpdateSalonRequest;
use App\Models\Salon;
use App\Models\Specialist;
use App\Services\SuperAdmin\SuperAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 5). Routes already exist
 * (routes/super-admin.php, commit 4a) — this is the controller they were written against ahead
 * of time; every method name here matches what that file already references.
 *
 * Deliberately thin — validation lives in the FormRequests, business rules (quota math,
 * subscription date math, the "at most one owner" rule) live in SuperAdminService. This
 * controller's only real job is translating requests into service calls and picking what to
 * show/redirect to.
 */
class SuperAdminController extends Controller
{
    public function __construct(protected readonly SuperAdminService $superAdminService) {}

    public function dashboard(): View
    {
        $salons = Salon::withCount('specialists')
            ->with('admins')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'active_salons' => $salons->where('is_suspended', false)->count(),
            'total_specialists' => Specialist::count(),
            'expiring_soon' => $salons->filter(fn ($salon) => $salon->subscription_ends_at->isFuture()
                && $salon->subscription_ends_at->diffInDays(now()) <= 7)->count(),
            'expired' => $salons->filter(fn ($salon) => $salon->subscription_ends_at->isPast())->count(),
        ];

        $recentSalons = $salons->take(5);

        return view('superadmin.dashboard', compact('salons', 'stats', 'recentSalons'));
    }

    public function index(): View
    {
        $salons = Salon::withCount('specialists')->with('admins')->orderBy('name')->paginate(20);

        return view('superadmin.salons.index', compact('salons'));
    }

    public function create(): View
    {
        return view('superadmin.salons.create');
    }

    public function store(StoreSalonRequest $request): RedirectResponse
    {
        $salon = $this->superAdminService->createSalonWithAdmin(
            $request->validated(),
            auth()->user(),
        );

        return redirect()->route('superadmin.salons.index')
            ->with('success', "سالن «{$salon->name}» با موفقیت ایجاد شد.");
    }

    public function edit(Salon $salon): View
    {
        return view('superadmin.salons.edit', compact('salon'));
    }

    public function update(UpdateSalonRequest $request, Salon $salon): RedirectResponse
    {
        try {
            $this->superAdminService->updateSalon($salon, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['max_specialists_count' => $e->getMessage()]);
        }

        return redirect()->route('superadmin.salons.index')
            ->with('success', 'اطلاعات سالن به‌روزرسانی شد.');
    }

    public function renewSubscription(Request $request, Salon $salon): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
        ]);

        $this->superAdminService->renewSubscription($salon, $validated['subscription_type']);

        return redirect()->route('superadmin.salons.index')
            ->with('success', "اشتراک سالن «{$salon->name}» تمدید شد.");
    }

    public function toggleSuspend(Salon $salon): RedirectResponse
    {
        try {
            $this->superAdminService->toggleSuspend($salon);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $status = $salon->fresh()->is_suspended ? 'غیرفعال' : 'فعال';

        return redirect()->route('superadmin.salons.index')
            ->with('success', "سالن «{$salon->name}» {$status} شد.");
    }
}
