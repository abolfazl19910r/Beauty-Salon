<?php

namespace App\Http\Controllers;

use App\Models\BeautyService;
use App\Models\Specialist;

class HomeController extends Controller
{
    public function index()
    {
        $services = BeautyService::latest()->take(6)->get();
        $specialists = Specialist::latest()->take(4)->get();

        return view('home', compact('services', 'specialists'));
    }
}
