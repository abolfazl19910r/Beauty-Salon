<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Category;
use App\Models\WalletSetting;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(protected CurrentSalon $currentSalon) {}

    public function index(): View
    {
        // ⚠️ N+1 fix: Without with('category'), each service on this page
        // (12 rows) would have run a separate category query (11 duplicate queries in Telescope
        // were observed). Now only a single query (where id in (...)) is run.
        //
        // ⚠️ Bug fix (documented in the project prompt as a known gap): clicking a category pill
        // in resources/views/services/index.blade.php changed the URL (?category=ID) and the
        // pill's "active" styling (via request('category')), but this method never actually read
        // that value — every click showed the exact same unfiltered list. Now applies where
        // category_id when present.
        $services = BeautyService::with('category')
            ->when(request('category'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->paginate(12)
            ->withQueryString();
        $categories = Category::all();

        return view('services.index', compact('services', 'categories'));
    }

    public function show(BeautyService $service): View
    {
        $specialists = $service->specialists()
            ->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')])
            ->get();

        $relatedServices = BeautyService::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'specialists', 'relatedServices'));
    }

    public function list(): JsonResponse
    {
        // 30-minute cache — service list changes infrequently. prepayment_amount is computed
        // AFTER the cached collection is retrieved (not baked into the cached payload itself), so
        // it always reflects the current admin-configured wallet_settings.prepayment_percentage/
        // minimum_prepayment_amount on every request, regardless of how stale the underlying
        // service-price cache is — no cache invalidation wiring needed for settings changes.
        //
        // ⭐ Fix (same cross-tenant leak class as HomeController's home_services/home_specialists,
        // found while moving this route under /s/{salon_slug}): the cache key used to be the
        // literal string 'all_beauty_services' with no salon identifier, even though
        // BeautyService is salon-scoped (BelongsToSalon). Once this route is actually reached
        // through 'salon.resolve' (CurrentSalon reliably bound here), an unscoped key would have
        // let whichever salon hit this endpoint first populate a 30-minute cache that every OTHER
        // salon's booking page then silently inherited — the exact same bug, just moved from the
        // query layer into the cache layer instead of being fixed. Suffixing with the salon id
        // gives every salon its own bucket, same as HomeController.
        $salonId = $this->currentSalon->id();

        $services = Cache::remember("beauty_services:{$salonId}", now()->addMinutes(30), fn () => BeautyService::all());

        $settings = WalletSetting::get();
        $services = $services->map(function (BeautyService $service) use ($settings) {
            $service->prepayment_amount = $settings->calculatePrepaymentAmount((float) $service->price);

            return $service;
        });

        return response()->json($services);
    }

    public function specialists(BeautyService $beautyService): JsonResponse
    {
        return response()->json($beautyService->specialists);
    }
}
