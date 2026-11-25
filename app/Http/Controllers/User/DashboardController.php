<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerDashboardService;
use Illuminate\View\View;

/**
 * داشبورد مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): همه‌ی داده از CustomerDashboardService. «نوبت‌های اخیر» و «پیش رو» (دو فهرست
 * تکراری)، «پیشنهاد برای شما»، «کارشناسان برتر»، اطلاعیه‌ها (بنر سراسری سایت نشانشان می‌دهد) و «دسترسی سریع» حذف شدند.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly CustomerDashboardService $dashboard) {}

    public function index(): View
    {
        return view('dashboard', $this->dashboard->overview(auth()->user()));
    }
}
