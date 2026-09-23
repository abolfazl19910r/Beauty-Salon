{{--
    ⭐ اطلاعات تماس و فعالیت سالن (۲۰۲۶-۰۹-۲۳) — مشترک بین ساخت و ویرایش سالن توسط سوپرادمین.
    همون چهار فیلد فرم ثبت‌نام عمومی (salon_address / salon_phone / experience_years / working_hours)،
    ولی اینجا همه اختیاری‌ان. ساعات کاری داخل یک fieldset است که فقط با تیک «ثبت ساعات کاری»
    فعال می‌شه: fieldset غیرفعال هیچ ورودی‌ای نمی‌فرسته، پس ذخیره‌ی فرم ویرایش یک سالن قدیمی هرگز
    بی‌صدا ساعات پیش‌فرض رو روی سالن نمی‌نویسه.
    ورودی: $salon (App\Models\Salon|null)
--}}
@php
    $salon = $salon ?? null;
    $hoursOld = old('working_hours');
    $hoursSaved = $salon?->working_hours;
    $hoursEnabled = is_array($hoursOld) || ! empty($hoursSaved);
    $hoursSource = is_array($hoursOld) ? $hoursOld : null;
    $hoursDefaults = \App\Support\SalonWorkingHours::defaults();
@endphp
<div>
    <h3 class="font-bold mb-3" style="color: var(--sa-accent);">اطلاعات تماس و فعالیت سالن</h3>
    {{-- ⭐ لوگوی اختصاصی سالن (۲۰۲۶-۰۹-۲۴) — اختیاری. --}}
    <div class="mb-4">
        <label class="sa-label">لوگوی سالن <span style="color: var(--sa-text-dim);">(اختیاری — PNG/JPG/WEBP، حداکثر ۲ مگابایت)</span></label>
        <div class="flex items-center gap-3">
            @if ($salon?->logoUrl())
                <img src="{{ $salon->logoUrl() }}" alt="لوگوی {{ $salon->name }}" class="w-14 h-14 object-contain rounded border" style="border-color: var(--sa-border);">
            @endif
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="text-sm">
        </div>
        @if ($salon?->logo_path)
            <label class="flex items-center gap-2 mt-2 text-sm">
                <input type="checkbox" name="remove_logo" value="1">
                حذف لوگوی فعلی
            </label>
        @endif
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="sa-label">آدرس سالن <span style="color: var(--sa-text-dim);">(اختیاری)</span></label>
            <textarea name="salon_address" rows="2" class="sa-input" maxlength="500">{{ old('salon_address', $salon?->address) }}</textarea>
        </div>
        <div>
            <label class="sa-label">شماره تماس سالن <span style="color: var(--sa-text-dim);">(اختیاری)</span></label>
            <input type="tel" name="salon_phone" value="{{ old('salon_phone', $salon?->phone) }}" class="sa-input" dir="ltr" maxlength="15" placeholder="02112345678">
        </div>
        <div>
            <label class="sa-label">سابقه‌ی کاری (سال) <span style="color: var(--sa-text-dim);">(اختیاری)</span></label>
            <input type="number" name="experience_years" value="{{ old('experience_years', $salon?->experienceYears()) }}" class="sa-input" dir="ltr" min="0" max="80">
        </div>
    </div>

    <label class="flex items-center gap-2 mt-4 text-sm" style="color: var(--sa-text);">
        <input type="checkbox" id="working-hours-enabled" @checked($hoursEnabled)>
        ثبت ساعات کاری سالن
    </label>
    <fieldset id="working-hours-fieldset" class="mt-3 grid gap-2" @disabled(! $hoursEnabled)>
        @foreach (\App\Support\SalonWorkingHours::WEEK_ORDER as $day)
            @php
                $value = $hoursSource !== null
                    ? $hoursSource[$day] ?? []
                    : (function () use ($hoursSaved, $hoursDefaults, $day) {
                        $v = is_array($hoursSaved) ? ($hoursSaved[$day] ?? null) : $hoursDefaults[$day];

                        return ['closed' => $v === null, 'open' => $v['open'] ?? '09:00', 'close' => $v['close'] ?? '21:00'];
                    })();
            @endphp
            <div class="grid grid-cols-4 gap-2 items-center text-sm">
                <span>{{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}</span>
                <label class="flex items-center gap-1">
                    <input type="checkbox" name="working_hours[{{ $day }}][closed]" value="1" @checked(! empty($value['closed']))>
                    تعطیل
                </label>
                <input type="time" name="working_hours[{{ $day }}][open]" value="{{ $value['open'] ?? '09:00' }}" class="sa-input" dir="ltr">
                <input type="time" name="working_hours[{{ $day }}][close]" value="{{ $value['close'] ?? '21:00' }}" class="sa-input" dir="ltr">
            </div>
        @endforeach
    </fieldset>
    <script>
        document.getElementById('working-hours-enabled').addEventListener('change', function () {
            document.getElementById('working-hours-fieldset').disabled = !this.checked;
        });
    </script>
</div>
