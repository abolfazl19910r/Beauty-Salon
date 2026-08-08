<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Specialist;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Cache::remember('home_services', 1800, function () {
            return BeautyService::latest()
                ->select('id', 'name', 'slug', 'description', 'price', 'duration', 'image', 'category_id')
                ->take(6)
                ->get();
        });

        $specialists = Cache::remember('home_specialists', 1800, function () {
            return Specialist::latest()
                ->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')])
                ->select('id', 'name', 'email', 'phone', 'user_id')
                ->take(4)
                ->get();
        });

        return view('home', compact('services', 'specialists'));
    }
}
