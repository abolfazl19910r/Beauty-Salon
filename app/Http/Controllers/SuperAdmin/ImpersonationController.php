<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Services\SuperAdmin\SuperAdminImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ⭐ «ورود اضطراری» ابرمدیر به پنل مدیر یک سالن برای پشتیبانی (۲۰۲۶-۱۰-۱۵؛ در طرح اولیه‌ی SaaS بود).
 * با حساب مالک سالن وارد می‌شود؛ شناسه‌ی ابرمدیر در session می‌ماند، بنر «بازگشت» بالای پنل نشان داده می‌شود، و شروع/پایان در
 * «گزارش کارهای ابرمدیر» ثبت می‌شود. تغییر رمز و شماره‌ی مالک در این حالت مسدود است (SuperAdminImpersonation::BLOCKED_ROUTES).
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Salon $salon, SuperAdminAudit $audit): RedirectResponse
    {
        $owner = $salon->admins()->wherePivot('role', 'owner')->orderBy('users.id')->first();

        if (! $owner) {
            return back()->with('error', 'این سالن مالک ندارد؛ اول از «مالک سالن» یکی تعیین کنید.');
        }
        if ($owner->hasRole('super-admin')) {
            return back()->with('error', 'ورود با حساب یک ابرمدیر دیگر مجاز نیست.');
        }

        $superAdmin = $request->user();
        $audit->record('impersonation.started', "ورود اضطراری به پنل «{$salon->name}» با حساب {$owner->name} ({$owner->phone})", $salon);

        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put([
            SuperAdminImpersonation::SESSION_KEY => $superAdmin->id,
            SuperAdminImpersonation::SALON_KEY => $salon->id,
        ]);

        return redirect()->route('admin.dashboard')->with('success', "حالت پشتیبانی: شما در پنل «{$salon->name}» هستید.");
    }

    /** در routes/web.php فقط با auth (کاربر فعلی مالک سالن است، نه ابرمدیر) */
    public function stop(Request $request, SuperAdminAudit $audit): RedirectResponse
    {
        $superAdminId = $request->session()->get(SuperAdminImpersonation::SESSION_KEY);
        $salonId = $request->session()->get(SuperAdminImpersonation::SALON_KEY);
        $superAdmin = $superAdminId ? User::find($superAdminId) : null;

        if (! $superAdmin || ! $superAdmin->hasRole('super-admin')) {
            $request->session()->forget([SuperAdminImpersonation::SESSION_KEY, SuperAdminImpersonation::SALON_KEY]);

            return redirect()->route('admin.dashboard');
        }

        $salonName = Salon::withoutGlobalScopes()->whereKey($salonId)->value('name');
        $audit->record('impersonation.ended', "پایان ورود اضطراری به «{$salonName}»", $salonId);

        Auth::login($superAdmin);
        $request->session()->regenerate();
        $request->session()->forget([SuperAdminImpersonation::SESSION_KEY, SuperAdminImpersonation::SALON_KEY]);

        return $salonId
            ? redirect()->route('superadmin.salons.edit', $salonId)->with('success', 'به پنل ابرمدیر برگشتید.')
            : redirect()->route('superadmin.dashboard');
    }
}
