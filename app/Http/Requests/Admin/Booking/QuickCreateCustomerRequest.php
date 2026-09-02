<?php

namespace App\Http\Requests\Admin\Booking;

use App\Support\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): admin/bookings/create.blade.php previously
 * rendered a full User::all() <select> — every user in the system in one dropdown, with no way
 * to add a walk-in/phone customer who has never used the app. This request backs the "quick
 * create customer" AJAX action the new search widget falls back to when no existing match is
 * found. Deliberately minimal (name + phone only, no roles/permissions) — this is for a
 * customer record, not an admin account; App\Http\Requests\Admin\User\StoreAdminUserRequest
 * stays the request used for actually creating staff/admin users.
 *
 * ⭐ Customer identity redesign (confirmed 2026-08-30): the uniqueness check is scoped to THIS
 * salon's customers, not global — the same phone number can be a legitimate walk-in customer at
 * a different salon.
 */
class QuickCreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    public function rules(): array
    {
        $salonId = app(CurrentSalon::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'phone')->where(fn ($query) => $query
                    ->where('salon_id', $salonId)
                    ->where('user_type', 'customer')),
            ],
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
