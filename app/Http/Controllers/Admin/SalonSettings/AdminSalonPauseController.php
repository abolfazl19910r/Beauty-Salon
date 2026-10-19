<?php

namespace App\Http\Controllers\Admin\SalonSettings;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Services\Salon\SalonPauseService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * بسته‌ی «ح»، ۸: کارت «توقف موقت رزرو» در «اطلاعات سالن» (فقط مالک، مثل همان صفحه). مدت به روز (۰ = تا بازکردن دستی) به‌جای
 * تقویم — تاریخ پایان از آن ساخته و در کارت نشان داده می‌شود.
 */
class AdminSalonPauseController extends Controller
{
    public const DURATIONS = [0 => 'تا وقتی خودم باز کنم', 1 => 'یک روز', 3 => 'سه روز', 7 => 'یک هفته', 14 => 'دو هفته', 30 => 'یک ماه'];

    public function __construct(private readonly SalonPauseService $pauses) {}

    public function pause(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'in:'.implode(',', array_keys(self::DURATIONS))],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);
        $days = (int) $data['days'];

        $this->pauses->pause($this->salon(), $request->user(), Salon::PAUSED_BY_OWNER, $data['reason'] ?? null,
            $days > 0 ? now()->addDays($days)->endOfDay() : null);

        return redirect()->route('admin.salon-settings.edit')->with('success', 'رزرو نوبت تازه موقتاً متوقف شد. نوبت‌های ثبت‌شده سر جایشان هستند.');
    }

    public function resume(): RedirectResponse
    {
        $this->pauses->resume($this->salon(), Salon::PAUSED_BY_OWNER);

        return redirect()->route('admin.salon-settings.edit')->with('success', 'رزرو نوبت دوباره باز شد.');
    }

    private function salon(): Salon
    {
        return app(CurrentSalon::class)->get();
    }
}
