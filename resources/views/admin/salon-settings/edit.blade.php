@extends('layouts.admin')
@section('title', 'اطلاعات سالن')

{{--
    ⭐ صفحه‌ی «اطلاعات سالن» مالک سالن (۲۰۲۶-۰۹-۲۴) — AdminSalonSettingsController.
    ساعات کاری مثل فرم سوپرادمین داخل یک fieldset است که فقط با تیک «نمایش ساعات کاری» فعال می‌شه؛
    fieldset غیرفعال هیچ ورودی‌ای نمی‌فرسته، پس ذخیره‌ی بقیه‌ی فیلدها هیچ‌وقت ساعات پیش‌فرض رو بی‌صدا
    روی سالن نمی‌نویسه.
--}}
@php
    $hoursOld = old('working_hours');
    $hoursSaved = $salon->working_hours;
    $hoursEnabled = is_array($hoursOld) || ! empty($hoursSaved);
    $hoursDefaults = \App\Support\SalonWorkingHours::defaults();
    $input = 'w-full rounded-lg px-3 py-2 text-sm';
    $inputStyle = 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);';
@endphp

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-5">
            <h1 class="text-xl font-bold" style="color: var(--admin-text);">اطلاعات سالن</h1>
            <p class="text-sm mt-1" style="color: var(--admin-text-dim);">
                این اطلاعات در سایت رزرو سالن به مشتری‌ها نمایش داده می‌شود. آدرس اختصاصی سالن
                (<span dir="ltr">{{ $salon->slug }}</span>) قابل تغییر نیست.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg p-3 mb-4 text-sm" style="background:#FEE2E2; color:#991B1B;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.salon-settings.update') }}" enctype="multipart/form-data"
              class="rounded-xl p-6 space-y-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf
            @method('PUT')

            <section class="space-y-4">
                <h2 class="font-bold" style="color: var(--admin-accent);">معرفی سالن</h2>
                <div>
                    <label class="block text-sm mb-1" for="name">نام سالن</label>
                    <input id="name" name="name" value="{{ old('name', $salon->name) }}" required maxlength="255" class="{{ $input }}" style="{{ $inputStyle }}">
                </div>
                <div>
                    <label class="block text-sm mb-1" for="tagline">شعار کوتاه <span style="color:var(--admin-text-light);">(اختیاری)</span></label>
                    <input id="tagline" name="tagline" value="{{ old('tagline', $salon->tagline) }}" maxlength="255" class="{{ $input }}" style="{{ $inputStyle }}">
                </div>
                <div>
                    <label class="block text-sm mb-1" for="bio">معرفی سالن <span style="color:var(--admin-text-light);">(اختیاری)</span></label>
                    <textarea id="bio" name="bio" rows="3" maxlength="2000" class="{{ $input }}" style="{{ $inputStyle }}">{{ old('bio', $salon->bio) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm mb-1" for="logo">لوگوی سالن <span style="color:var(--admin-text-light);">(PNG/JPG/WEBP، حداکثر ۲ مگابایت)</span></label>
                    <div class="flex items-center gap-3">
                        <img id="logo-preview" src="{{ $salon->logoUrl() }}" alt="لوگوی {{ $salon->name }}"
                             class="w-16 h-16 object-contain rounded-lg" style="border:1px solid var(--admin-border); {{ $salon->logoUrl() ? '' : 'display:none;' }}">
                        <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="text-sm">
                    </div>
                    @if ($salon->logo_path)
                        <label class="flex items-center gap-2 mt-2 text-sm">
                            <input type="checkbox" name="remove_logo" value="1"> حذف لوگوی فعلی
                        </label>
                    @endif
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="font-bold" style="color: var(--admin-accent);">اطلاعات تماس و فعالیت</h2>
                <div>
                    <label class="block text-sm mb-1" for="salon_address">آدرس سالن</label>
                    <textarea id="salon_address" name="salon_address" rows="2" maxlength="500" class="{{ $input }}" style="{{ $inputStyle }}">{{ old('salon_address', $salon->address) }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1" for="salon_phone">شماره تماس سالن</label>
                        <input id="salon_phone" name="salon_phone" type="tel" dir="ltr" maxlength="15" value="{{ old('salon_phone', $salon->phone) }}" class="{{ $input }}" style="{{ $inputStyle }}" placeholder="02112345678">
                    </div>
                    <div>
                        <label class="block text-sm mb-1" for="experience_years">سابقه‌ی کاری (سال)</label>
                        <input id="experience_years" name="experience_years" type="number" min="0" max="80" dir="ltr" value="{{ old('experience_years', $salon->experienceYears()) }}" class="{{ $input }}" style="{{ $inputStyle }}">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="working-hours-enabled" @checked($hoursEnabled)>
                    نمایش ساعات کاری در سایت سالن
                </label>
                <fieldset id="working-hours-fieldset" class="grid gap-2" @disabled(! $hoursEnabled)>
                    @foreach (\App\Support\SalonWorkingHours::WEEK_ORDER as $day)
                        @php
                            $value = is_array($hoursOld)
                                ? ($hoursOld[$day] ?? [])
                                : (function () use ($hoursSaved, $hoursDefaults, $day) {
                                    $v = is_array($hoursSaved) ? ($hoursSaved[$day] ?? null) : $hoursDefaults[$day];

                                    return ['closed' => $v === null, 'open' => $v['open'] ?? '09:00', 'close' => $v['close'] ?? '21:00'];
                                })();
                        @endphp
                        <div class="grid grid-cols-4 gap-2 items-center text-sm">
                            <span>{{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}</span>
                            <label class="flex items-center gap-1">
                                <input type="checkbox" name="working_hours[{{ $day }}][closed]" value="1" @checked(! empty($value['closed']))> تعطیل
                            </label>
                            <input type="time" name="working_hours[{{ $day }}][open]" value="{{ $value['open'] ?? '09:00' }}" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" aria-label="شروع {{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}">
                            <input type="time" name="working_hours[{{ $day }}][close]" value="{{ $value['close'] ?? '21:00' }}" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" aria-label="پایان {{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}">
                        </div>
                    @endforeach
                    <p class="text-xs" style="color: var(--admin-text-light);">فقط برای نمایش به مشتری‌هاست؛ ساعت‌های قابل رزرو را برنامه‌ی کاری هر متخصص تعیین می‌کند.</p>
                </fieldset>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 rounded-lg text-sm font-bold text-white" style="background: var(--admin-accent);">ذخیره‌ی اطلاعات سالن</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.getElementById('working-hours-enabled').addEventListener('change', function () {
                document.getElementById('working-hours-fieldset').disabled = !this.checked;
            });
            document.getElementById('logo').addEventListener('change', function () {
                var preview = document.getElementById('logo-preview');
                if (this.files && this.files[0]) {
                    preview.src = URL.createObjectURL(this.files[0]);
                    preview.style.display = '';
                }
            });
        </script>
    @endpush
@endsection
