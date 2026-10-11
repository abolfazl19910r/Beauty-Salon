<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class RegisteredUserController extends Controller
{
    /**
     * ثبت‌نام مشتری فقط در صفحه‌ی سالن خودش است (/s/{slug}/register؛ تصمیم ۲۰۲۶-۱۰-۱۰). /register مرکزی جایی برای
     * ثبت‌نام ندارد و از ۲۰۲۶-۱۰-۱۱ به صفحه‌ی فروش دامنه‌ی اصلی می‌رود — قبلاً هر کسی را به سالن دمو (rasta)
     * می‌فرستاد. POST/verify/resend مرکزی هم ۲۰۲۶-۱۰-۱۱ حذف شد (مشتری بی‌سالن می‌ساخت).
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('central.home');
    }
}
