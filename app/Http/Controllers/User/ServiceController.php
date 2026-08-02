<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
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
            ->with(['schedules' => fn($q) => $q->where('is_active', true)->orderBy('day_of_week')])
            ->get();

        $relatedServices = BeautyService::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'specialists', 'relatedServices'));
    }

    public function list(): JsonResponse
    {
        // 30-minute cache — service list changes infrequently
        $services = Cache::remember('all_beauty_services', now()->addMinutes(30), fn () => BeautyService::all());
        return response()->json($services);
    }

    public function specialists(BeautyService $beautyService): JsonResponse
    {
        return response()->json($beautyService->specialists);
    }

}
