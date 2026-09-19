<?php

namespace App\Http\Requests\Admin\User;

use App\Support\CurrentSalon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    /**
     * ⭐ فاز ۲ SaaS، محور «۲» — همون منطق StoreAdminUserRequest؛ به داکبلاک اون فایل نگاه کن.
     * محافظت «آخرین owner سالن نمی‌تواند تنزل/حذف شود» در AdminUserService انجام می‌شود، نه اینجا
     * (به یک کوئری روی salon_admins نیاز دارد، جای صحیحش لایه‌ی سرویس/تراکنش است).
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;
        $hasCurrentSalon = app(CurrentSalon::class)->id() !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:11', 'unique:users,phone,'.$userId],
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
            'salon_role.required' => 'نقش این ادمین در سالن (مالک یا منشی) باید مشخص شود.',
            'salon_role.in' => 'نقش این ادمین در سالن نامعتبر است.',
        ];
    }
}
