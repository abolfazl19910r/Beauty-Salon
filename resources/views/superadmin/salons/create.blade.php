@extends('layouts.superadmin')

@section('title', 'ساخت سالن جدید')

@section('content')
    <div class="sa-card p-6 max-w-3xl">
        <form method="POST" action="{{ route('superadmin.salons.store') }}" class="space-y-6">
            @csrf

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">مشخصات سالن</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">نام سالن (نمایشی)</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">
                            آدرس یکتا (slug) — بعد از ساخت غیرقابل‌تغییر
                        </label>
                        <div class="flex items-center gap-2">
                            <span style="color: var(--sa-text-dim);">/s/</span>
                            <input type="text" name="slug" value="{{ old('slug') }}" class="sa-input" required
                                   pattern="[a-zA-Z0-9_-]+" placeholder="مثلاً: almas">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">اشتراک و سقف</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">مدت اشتراک</label>
                        <select name="subscription_type" class="sa-input" required>
                            <option value="1m" {{ old('subscription_type') == '1m' ? 'selected' : '' }}>۱ ماه</option>
                            <option value="3m" {{ old('subscription_type') == '3m' ? 'selected' : '' }}>۳ ماه</option>
                            <option value="6m" {{ old('subscription_type') == '6m' ? 'selected' : '' }}>۶ ماه</option>
                            <option value="12m" {{ old('subscription_type') == '12m' ? 'selected' : '' }}>۱۲ ماه</option>
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">سقف تعداد متخصص</label>
                        <input type="number" name="max_specialists_count" value="{{ old('max_specialists_count', 5) }}" min="0" class="sa-input" required>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">مشخصات ادمین سالن</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">نام ادمین</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">موبایل ادمین</label>
                        <input type="text" name="admin_phone" value="{{ old('admin_phone') }}" class="sa-input" maxlength="11" required>
                    </div>
                    <div>
                        <label class="sa-label">رمز عبور</label>
                        <input type="password" name="admin_password" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">تکرار رمز عبور</label>
                        <input type="password" name="admin_password_confirmation" class="sa-input" required>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">دسترسی‌های ماژولار</h3>
                {{-- ⭐ فقط ذخیره می‌شن — اعمال واقعی این محدودیت‌ها روی منوی پنل ادمین، طبق مستندات
                     SaaS ("بستن حفره دوم")، هنوز پیاده‌سازی نشده؛ فعلاً فقط اطلاعاتی هستن. --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach (['blog' => 'وبلاگ', 'gallery' => 'گالری', 'announcements' => 'اطلاعیه‌ها', 'loyalty' => 'وفاداری', 'discount_codes' => 'کد تخفیف', 'reports' => 'گزارشات', 'wallet_settings' => 'تنظیمات کیف‌پول'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm" style="color: var(--sa-text-dim);">
                            <input type="checkbox" name="module_permissions[]" value="{{ $key }}"
                                   {{ in_array($key, old('module_permissions', [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="sa-btn">ایجاد سالن</button>
        </form>
    </div>
@endsection
