@extends('layouts.specialist')
@section('title', 'برنامه کاری (تکی)')

@push('styles')
    <style>
        .ws-day-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 9999px; cursor: pointer;
            border: 1px solid var(--specialist-border); font-size: 0.8125rem;
            background: var(--specialist-surface); color: var(--specialist-text-dim);
            transition: all 0.15s; user-select: none;
        }
        .ws-day-chip input { display: none; }
        .ws-day-chip.checked {
            background: linear-gradient(135deg, #D8AEE0, #A85FB8);
            color: #250D2B; border-color: transparent;
        }
        .ws-time-input {
            border: 1px solid var(--specialist-border); border-radius: 8px;
            padding: 8px 12px; font-size: 0.875rem;
            background: var(--specialist-bg); color: var(--specialist-text);
            outline: none; width: 100%;
        }
        .ws-time-input:focus { border-color: var(--specialist-plum-mid); }
        .ws-toggle { position: relative; width: 44px; height: 24px; display: inline-block; }
        .ws-toggle input { opacity: 0; width: 0; height: 0; }
        .ws-toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--specialist-border); border-radius: 9999px; transition: background .2s;
        }
        .ws-toggle-slider:before {
            content: ''; position: absolute; width: 18px; height: 18px; border-radius: 50%;
            background: #fff; top: 3px; right: 3px; transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .ws-toggle input:checked + .ws-toggle-slider { background: #A85FB8; }
        .ws-toggle input:checked + .ws-toggle-slider:before { transform: translateX(-20px); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--specialist-text);">برنامه کاری (تکی)</h1>
                <p class="text-sm mt-0.5" style="color:var(--specialist-text-dim);">یک بازه‌ی ساعتی مشترک برای روزهای کاری‌ات</p>
            </div>
            <a href="{{ route('specialist.my-dashboard') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium specialist-card">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl p-4 mb-5 text-sm specialist-card" style="color:#86EFAC;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl p-4 mb-5 text-sm specialist-card" style="color:#FCA5A5;">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('specialist.work-schedule.update') }}">
            @csrf @method('PUT')

            <div class="specialist-card rounded-xl p-5 mb-5">

                <div class="flex items-center justify-between mb-5">
                    <span class="text-sm font-medium" style="color:var(--specialist-text);">فعال بودن این برنامه</span>
                    <label class="ws-toggle">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="ws-toggle-slider"></span>
                    </label>
                </div>

                <label class="block text-xs font-medium mb-2" style="color:var(--specialist-text-dim);">روزهای کاری</label>
                @php
                    $days = [0=>'یکشنبه',1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنج‌شنبه',5=>'جمعه',6=>'شنبه'];
                    $selectedDays = old('work_days', $schedule?->work_days ?? []);
                @endphp
                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach($days as $dayNum => $dayName)
                        <label class="ws-day-chip {{ in_array($dayNum, $selectedDays) ? 'checked' : '' }}" onclick="this.classList.toggle('checked')">
                            <input type="checkbox" name="work_days[]" value="{{ $dayNum }}" {{ in_array($dayNum, $selectedDays) ? 'checked' : '' }}>
                            {{ $dayName }}
                        </label>
                    @endforeach
                </div>
                @error('work_days')
                <p class="text-xs mb-3" style="color:#FCA5A5;">{{ $message }}</p>
                @enderror

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color:var(--specialist-text-dim);">ساعت شروع</label>
                        <input type="time" name="start_time" class="ws-time-input" value="{{ old('start_time', $schedule?->start_time?->format('H:i') ?? '09:00') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color:var(--specialist-text-dim);">ساعت پایان</label>
                        <input type="time" name="end_time" class="ws-time-input" value="{{ old('end_time', $schedule?->end_time?->format('H:i') ?? '18:00') }}">
                    </div>
                </div>
            </div>

            <button type="submit" class="specialist-cta px-6 py-2.5 rounded-lg text-sm font-medium">
                ذخیره برنامه کاری
            </button>
        </form>

        @if($schedule)
            <form method="POST" action="{{ route('specialist.work-schedule.destroy') }}" data-confirm-delete class="mt-3">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium" style="background:rgba(220,38,38,.15); color:#FCA5A5;">
                    حذف برنامه کاری
                </button>
            </form>
        @endif
    </div>
@endsection
