<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Services\SuperAdmin\SalonOwnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** ⭐ «مالک سالن» در ویرایش سالن ابرمدیر (۲۰۲۶-۱۰-۱۵) */
class SalonOwnerController extends Controller
{
    public function update(Request $request, Salon $salon, SalonOwnerService $owners): RedirectResponse
    {
        $owner = $owners->owner($salon);
        $request->merge(['owner_phone' => preg_replace('/\D/', '', strtr((string) $request->input('owner_phone'),
            ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']))]);

        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => [
                'required', 'string', 'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'phone')->where(fn ($q) => $q->where('user_type', 'staff'))->ignore($owner?->id),
            ],
            'owner_password' => [$owner ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'owner_phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'owner_phone.unique' => 'این شماره برای کاربر کادر دیگری ثبت شده است.',
            'owner_password.required' => 'برای مالک تازه رمز لازم است.',
        ], ['owner_name' => 'نام مالک', 'owner_phone' => 'موبایل مالک', 'owner_password' => 'رمز']);

        $owners->save($salon, ['name' => $data['owner_name'], 'phone' => $data['owner_phone'], 'password' => $data['owner_password'] ?? null]);

        return redirect()->route('superadmin.salons.edit', $salon)->with('success', 'مشخصات مالک سالن ذخیره شد.');
    }
}
