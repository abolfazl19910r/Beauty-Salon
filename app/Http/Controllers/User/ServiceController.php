<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function index()
    {
        $services = BeautyService::paginate(12);
        $categories = Category::all();

        return view('services.index', compact('services', 'categories'));
    }

    public function show(BeautyService $service)
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

    public function list()
    {
        // کش ۳۰ دقیقه‌ای — لیست خدمات به‌ندرت تغییر می‌کند
        $services = Cache::remember('all_beauty_services', now()->addMinutes(30), fn () => BeautyService::all());
        return response()->json($services);
    }

    public function specialists(BeautyService $beautyService)
    {
        return response()->json($beautyService->specialists);
    }

}
