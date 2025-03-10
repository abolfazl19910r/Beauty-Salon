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
        $specialists = $service->specialists()->get();
        $relatedServices = BeautyService::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'specialists', 'relatedServices'));
    }

    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return BeautyService::all();
    }

    public function specialists(BeautyService $service)
    {
        return $service->specialists;
    }
}
