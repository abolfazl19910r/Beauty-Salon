@extends('layouts.admin')
@section('title', 'برنامه کاری (تکی) ' . $specialist->name)

@push('styles')
    <style>
        .wd-day-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 9999px; cursor: pointer;
            border: 1px solid var(--admin-border); font-size: 0.8125rem;
            background: var(--admin-surface); color: var(--admin-text-dim);
            transition: all 0.15s;
            user-select: none;
        }
        .wd-day-chip input { display: none; }
        .wd-day-chip.checked {
            background: var(--admin-accent); color: #fff; border-color: var(--admin-accent);
        }
        .wd-time-input {
            border: 1px solid var(--admin-border); border-radius: 8px;
            padding: 8px 12px; font-size: 0.875rem;
            background: var(--admin-bg); color: var(--admin-text);
            outline: none; width: 100%;
        }
        .wd-time-input:focus { border-color: var(--admin-accent); }
        .wd-toggle { position: relative; width: 44px; height: 24px; display: inline-block; }
        .wd-toggle input { opacity: 0; width: 0; height: 0; }
        .wd-toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--admin-border); border-radius: 9999px; transition: background .2s;
        }
        .wd-toggle-slider:before {
            content: ''; position: absolute; width: 18px; height: 18px; border-radius: 50%;
            background: #fff; top: 3px; right: 3px; transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .wd-toggle input:checked + .wd-toggle-slider { background: #16A34A; }
        .wd-toggle input:checked + .wd-toggle-slider:before { transform: translateX(-20px); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">برنامه کاری (تکی)</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $specialist->name }} — یک بلوک ساعتی مشترک برای چند روز</p>
            </div>
            <a href="{{ route('admin.specialists.show', $specialist) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl p-4 mb-5 flex items-start gap-3 text-sm"
             style="background:#EFF6FF; border:1px solid #93C5FD; color:#1D4ED8;">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p>این یک برنامه‌ی کاری جداگانه است (فقط یک بازه‌ی ساعتی مشترک برای روزهای انتخاب‌شده). اگر برنامه‌ی روزانه‌ی جداگانه برای هر روز نیاز دارید، از «برنامه کاری هفتگی» در صفحه‌ی متخصص استفاده کنید.</p>
        </div>

        <form method="POST" action="{{ route('admin.specialists.work-schedule.update', $specialist) }}">
            @csrf @method('PUT')

            <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">

                <div class="flex items-center justify-between mb-5">
                    <span class="text-sm font-medium" style="color:var(--admin-text);">فعال بودن این برنامه</span>
                    <label class="wd-toggle">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="wd-toggle-slider"></span>
                    </label>
                </div>

                <label class="block text-xs font-medium mb-2" style="color:var(--admin-text-dim);">روزهای کاری</label>
                @php
                    $days = [0=>'یکشنبه',1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنج‌شنبه',5=>'جمعه',6=>'شنبه'];
                    $selectedDays = old('work_days', $schedule?->work_days ?? []);
                @endphp
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach($days as $dayNum => $dayName)
                        <label class="wd-day-chip {{ in_array($dayNum, $selectedDays) ? 'checked' : '' }}" onclick="this.classList.toggle('checked')">
                            <input type="checkbox" name="work_days[]" value="{{ $dayNum }}" {{ in_array($dayNum, $selectedDays) ? 'checked' : '' }}>
                            {{ $dayName }}
                        </label>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">ساعت شروع</label>
                        <input type="time" name="start_time" class="wd-time-input" value="{{ old('start_time', $schedule?->start_time?->format('H:i') ?? '09:00') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">ساعت پایان</label>
                        <input type="time" name="end_time" class="wd-time-input" value="{{ old('end_time', $schedule?->end_time?->format('H:i') ?? '18:00') }}">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 rounded-xl" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors"
                        style="background:var(--admin-accent);">
                    ذخیره برنامه کاری
                </button>
            </div>
        </form>

        @if($schedule)
            <form method="POST" action="{{ route('admin.specialists.work-schedule.destroy', $specialist) }}" data-confirm-delete class="mt-3">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium" style="background:#FEF2F2; color:#DC2626;">
                    حذف برنامه کاری
                </button>
            </form>
        @endif
    </div>
@endsection
