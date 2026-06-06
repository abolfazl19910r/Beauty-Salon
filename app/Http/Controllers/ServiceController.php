<?php

namespace App\Http\Controllers;

use App\Models\BeautyService;
use App\Models\Category;

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
        $services = BeautyService::all();
        return response()->json($services);
    }

    public function specialists(BeautyService $beautyService)
    {
        return response()->json($beautyService->specialists);
    }

}
