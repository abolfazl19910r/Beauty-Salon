<?php

namespace App\Http\Requests\Admin\User;

use App\Support\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    /**
     * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن»: وقتی یک owner (نه سوپر ادمین مستقیم از
     * /admin) این فرم را پر می‌کند، CurrentSalon همیشه ست است (EnsureAdminSalonActive) و باید
     * صریحاً انتخاب کند نفر جدید owner است یا staff — این انتخاب همان چیزی است که is_admin و
     * نقش‌های سیستمی را تعیین می‌کند (AdminUserService::create() می‌بیند)، نه یک چک‌باکس آزاد.
     * وقتی CurrentSalon ست نیست (سوپر ادمین مستقیم از /admin/users)، این فیلد بی‌معناست چون به
     * هیچ سالنی وصل نمی‌شود — مسیر واقعی ساخت اولین ادمین سالن همچنان /superadmin است.
     */
    public function rules(): array
    {
        $hasCurrentSalon = app(CurrentSalon::class)->id() !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:11', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'salon_role' => [$hasCurrentSalon ? 'required' : 'nullable', 'in:owner,staff'],
            'finance_access' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_admin' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام کاربر الزامی است.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'salon_role.required' => 'نقش نفر جدید در سالن (مالک یا منشی) باید مشخص شود.',
            'salon_role.in' => 'نقش نفر جدید در سالن نامعتبر است.',
        ];
    }
}
