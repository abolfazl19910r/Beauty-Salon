<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Services\Salon\SalonMembershipService;
use App\Services\SuperAdmin\SuperAdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ابرمدیر: افزودن حساب کادرِ موجود به پنل سالن دیگر (تصمیم ۵-۱ الف، ۲۰۲۶-۱۰-۲۲) — مثلاً مالکی که سالن دومش را از پشتیبانی خواسته.
 */
class SalonMemberController extends Controller
{
    public function store(Request $request, Salon $salon, SalonMembershipService $memberships, SuperAdminAudit $audit): RedirectResponse
    {
        $request->merge(['member_phone' => preg_replace('/\D/', '', strtr((string) $request->input('member_phone'),
            ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']))]);
        $data = $request->validate([
            'member_phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'member_role' => ['required', 'in:owner,staff'],
        ], [
            'member_phone.required' => 'شماره را وارد کنید.',
            'member_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
        ]);

        $user = $memberships->existingStaff($data['member_phone']);
        if (! $user) {
            return back()->withErrors(['member_phone' => 'حساب کادری با این شماره نیست. برای حساب تازه از «مالک سالن» یا پنل مدیر همان سالن استفاده کنید.'])->withInput();
        }

        $memberships->addExisting($user, $salon, $data['member_role'], [], 'member_phone');
        $label = $data['member_role'] === 'owner' ? 'مالک' : 'منشی';
        $audit->record('salon.member_added', "افزودن حساب موجود {$user->name} ({$user->phone}) به «{$salon->name}» به‌عنوان {$label}", $salon);

        return back()->with('success', "{$user->name} به‌عنوان {$label} به این سالن اضافه شد؛ با همان رمز خودش وارد می‌شود.");
    }
}
