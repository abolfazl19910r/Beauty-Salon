@extends('layouts.admin')
@section('title', $package->exists ? 'ویرایش بسته‌ی دوره' : 'بسته‌ی دوره‌ی تازه')

@section('content')
    <div class="fade-in" style="max-width: 48rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <h1 class="text-xl font-bold mb-5" style="color:var(--admin-text);">{{ $package->exists ? 'ویرایش «'.$package->name.'»' : 'بسته‌ی دوره‌ی تازه' }}</h1>

        <form method="POST" action="{{ $package->exists ? route('admin.courses.packages.update', $package) : route('admin.courses.packages.store') }}" class="rounded-xl p-5 space-y-4" style="{{ $box }}" novalidate>
            @csrf
            @if ($package->exists) @method('PUT') @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1" for="name">نام بسته</label>
                    <input id="name" name="name" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('name', $package->name) }}" placeholder="مثلاً لیزر موهای زائد صورت — ۶ جلسه">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="service_id">خدمت</label>
                    <select id="service_id" name="service_id" class="{{ $input }}" style="{{ $inputStyle }}">
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((int) old('service_id', $package->service_id) === $service->id)>{{ $service->name }} ({{ number_format($service->price) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    @php $extras = array_map('intval', (array) old('extra_service_ids', $package->extra_service_ids ?? [])); @endphp
                    <div class="block text-sm font-medium mb-1">خدمت‌های همراه در هر جلسه (اختیاری — بسته‌ی چندخدمتی)</div>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($services as $service)
                            <label class="text-sm flex items-center gap-1"><input type="checkbox" name="extra_service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $extras, true))> {{ $service->name }}</label>
                        @endforeach
                    </div>
                    <p class="text-xs mt-1" style="{{ $dim }}">مثلاً «لیزر صورت + زیربغل»: هر جلسه همه‌ی خدمت‌ها با هم در یک نوبت، با مدتِ جمع آن‌ها. متخصص باید همه را انجام دهد.</p>
                    @error('extra_service_ids') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="specialist_id">فقط این متخصص (اختیاری)</label>
                    <select id="specialist_id" name="specialist_id" class="{{ $input }}" style="{{ $inputStyle }}">
                        <option value="">هر متخصصِ این خدمت</option>
                        @foreach ($specialists as $specialist)
                            <option value="{{ $specialist->id }}" @selected((int) old('specialist_id', $package->specialist_id) === $specialist->id)>{{ $specialist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="sessions_count">تعداد جلسه</label>
                    <input id="sessions_count" name="sessions_count" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('sessions_count', $package->sessions_count) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="price">قیمت کل (تومان)</label>
                    <input id="price" name="price" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('price', $package->price !== null ? (int) $package->price : '') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="interval_days">فاصله‌ی پیشنهادی جلسه‌ها (روز)</label>
                    <input id="interval_days" name="interval_days" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('interval_days', $package->interval_days) }}">
                    <p class="text-xs mt-1" style="{{ $dim }}">موعد جلسه‌ی بعد و پیامک یادآوری بر اساس همین.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="min_interval_days">فاصله‌ی حداقل (روز)</label>
                    <input id="min_interval_days" name="min_interval_days" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('min_interval_days', $package->min_interval_days) }}">
                    <p class="text-xs mt-1" style="{{ $dim }}">مشتری زودتر از این نمی‌تواند جلسه‌ی بعد را رزرو کند (مدیر می‌تواند).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="validity_days">مهلت استفاده از خرید (روز، خالی = بی‌مهلت)</label>
                    <input id="validity_days" name="validity_days" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('validity_days', $package->validity_days) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="min_prepayment_percent">حداقل پیش‌پرداخت ٪ (خالی = فقط پرداخت کل)</label>
                    <input id="min_prepayment_percent" name="min_prepayment_percent" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('min_prepayment_percent', $package->min_prepayment_percent) }}">
                    <p class="text-xs mt-1" style="{{ $dim }}">جلسه‌ها به اندازه‌ی مبلغ پرداخت‌شده رزرو می‌شوند.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1" for="description">توضیح (اختیاری)</label>
                    <textarea id="description" name="description" rows="3" class="{{ $input }}" style="{{ $inputStyle }}">{{ old('description', $package->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="sort">ترتیب</label>
                    <input id="sort" name="sort" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('sort', $package->sort ?? 0) }}">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))> فعال (فروخته می‌شود)</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $package->is_public))> فروش آنلاین در صفحه‌ی سالن و اپ (خاموش = فقط فروش حضوری)</label>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">ذخیره</button>
                <a href="{{ route('admin.courses.packages.index') }}" class="text-sm" style="{{ $dim }}">بازگشت</a>
            </div>
        </form>

        @if ($package->exists)
            <form method="POST" action="{{ route('admin.courses.packages.destroy', $package) }}" class="mt-4">
                @csrf @method('DELETE')
                <button type="submit" data-confirm-delete class="text-sm" style="color:#b91c1c;">حذف بسته</button>
            </form>
        @endif
    </div>
@endsection
