@extends('layouts.superadmin')

@section('title', 'ویرایش سالن')

@section('content')
    <div class="sa-card p-6 max-w-2xl">
        <div class="mb-4 text-sm" style="color: var(--sa-text-dim);">
            آدرس: <span style="color: var(--sa-text);">/s/{{ $salon->slug }}</span>
            (غیرقابل‌تغییر)
        </div>

        <form method="POST" action="{{ route('superadmin.salons.update', $salon) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="sa-label">نام سالن</label>
                <input type="text" name="name" value="{{ old('name', $salon->name) }}" class="sa-input" required>
            </div>

            <div>
                <label class="sa-label">
                    سقف تعداد متخصص
                    <span style="color: var(--sa-text-dim);">(فعلاً {{ $salon->specialists()->count() }} متخصص ثبت‌شده)</span>
                </label>
                <input type="number" name="max_specialists_count" value="{{ old('max_specialists_count', $salon->max_specialists_count) }}" min="0" class="sa-input" required>
            </div>

            <div>
                <label class="sa-label">دسترسی‌های ماژولار</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @php $currentModules = old('module_permissions', $salon->module_permissions ?? []); @endphp
                    @foreach (['blog' => 'وبلاگ', 'gallery' => 'گالری', 'announcements' => 'اطلاعیه‌ها', 'loyalty' => 'وفاداری', 'discount_codes' => 'کد تخفیف', 'reports' => 'گزارشات', 'wallet_settings' => 'تنظیمات کیف‌پول'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm" style="color: var(--sa-text-dim);">
                            <input type="checkbox" name="module_permissions[]" value="{{ $key }}"
                                   {{ in_array($key, $currentModules) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="sa-btn">ذخیره تغییرات</button>
        </form>
    </div>
@endsection
