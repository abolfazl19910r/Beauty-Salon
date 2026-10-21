<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Push\FcmClient;
use App\Services\Push\PushMode;
use App\Services\Push\PushStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** «اعلان اپ (پوش)» ابرمدیر (بسته‌ی ۴): نتیجه‌ی دوره‌ی آزمایشی و بعد از آن، برای تصمیم روشن کردن حالت «فعال». */
class PushStatsController extends Controller
{
    public function index(Request $request, PushStats $stats): View
    {
        $days = in_array((int) $request->query('days'), [1, 7, 30, 90], true) ? (int) $request->query('days') : 7;

        return view('superadmin.push.index', [
            'summary' => $stats->summary($days),
            'devices' => $stats->devices(),
            'readiness' => $stats->readiness(),
            'mode' => PushMode::current(),
            'modeLabel' => PushMode::label(PushMode::current()),
            'fcmConfigured' => app(FcmClient::class)->configured(),
        ]);
    }
}
