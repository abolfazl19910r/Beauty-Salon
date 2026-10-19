<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController;
use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Services\Salon\SalonPauseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * بسته‌ی «ح»، ۸: توقف/بازکردن رزرو سالن از پنل ابرمدیر — توقف ابرمدیر جای توقف مدیر را می‌گیرد و فقط ابرمدیر بازش می‌کند؛
 * هر دو کار در «گزارش کارهای ابرمدیر» ثبت می‌شود (SalonPauseService).
 */
class SuperAdminSalonPauseController extends Controller
{
    public function __construct(private readonly SalonPauseService $pauses) {}

    public function pause(Request $request, Salon $salon): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'in:'.implode(',', array_keys(AdminSalonPauseController::DURATIONS))],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $days = (int) $data['days'];

        $this->pauses->pause($salon, $request->user(), Salon::PAUSED_BY_PLATFORM, $data['reason'] ?? null,
            $days > 0 ? now()->addDays($days)->endOfDay() : null);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', 'رزرو سالن موقتاً متوقف شد.');
    }

    public function resume(Salon $salon): RedirectResponse
    {
        $this->pauses->resume($salon, Salon::PAUSED_BY_PLATFORM);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', 'رزرو سالن دوباره باز شد.');
    }
}
