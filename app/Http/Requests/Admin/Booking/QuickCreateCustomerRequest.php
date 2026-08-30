<?php

namespace App\Http\Requests\Admin\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): admin/bookings/create.blade.php previously
 * rendered a full User::all() <select> — every user in the system in one dropdown, with no way
 * to add a walk-in/phone customer who has never used the app. This request backs the "quick
 * create customer" AJAX action the new search widget falls back to when no existing match is
 * found. Deliberately minimal (name + phone only, no roles/permissions) — this is for a
 * customer record, not an admin account; App\Http\Requests\Admin\User\StoreAdminUserRequest
 * stays the request used for actually creating staff/admin users.
 */
class QuickCreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'unique:users,phone'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام مشتری الزامی است.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'phone.unique' => 'مشتری‌ای با این شماره موبایل از قبل ثبت شده — از جستجو استفاده کنید.',
        ];
    }
}
