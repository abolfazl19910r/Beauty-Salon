@extends('layouts.admin')
@section('title', 'برنامه کاری ' . $specialist->name)

@push('styles')
    <style>
        .day-card {
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .day-card.active { border-color: #86EFAC; }
        .day-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--admin-border);
        }
        .day-card.active .day-header { background: #F0FDF4; }
        .day-card:not(.active) .day-header { background: var(--admin-accent-light); }
        .toggle-switch {
            position: relative; width: 44px; height: 24px; display: inline-block;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--admin-border); border-radius: 9999px;
            transition: background 0.2s;
        }
        .toggle-slider:before {
            content: ''; position: absolute;
            width: 18px; height: 18px; border-radius: 50%;
            background: #fff; top: 3px; right: 3px;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #16A34A; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(-20px); }
        .time-input {
            border: 1px solid var(--admin-border); border-radius: 8px;
            padding: 7px 12px; font-size: 0.875rem;
            background: var(--admin-bg); color: var(--admin-text);
            outline: none; transition: border-color 0.15s;
            width: 100%;
        }
        .time-input:focus { border-color: var(--admin-accent); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">برنامه کاری هفتگی</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $specialist->name }}</p>
            </div>
            <a href="{{ route('admin.specialists.show', $specialist) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <form method="POST" action="{{ route('admin.specialists.schedules.update', $specialist) }}">
            @csrf @method('PUT')

            <div class="space-y-3 mb-5">
                @php
                    $days = [0=>'یکشنبه',1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنج‌شنبه',5=>'جمعه',6=>'شنبه'];
                @endphp
                @foreach($days as $dayNum => $dayName)
                    @php
                        $schedule = isset($schedules[$dayNum]) ? $schedules[$dayNum]->first() : null;
                        $isActive = $schedule && $schedule->is_active;
                    @endphp
                    <div class="day-card {{ $isActive ? 'active' : '' }}" id="day-card-{{ $dayNum }}">
                        <div class="day-header">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold"
                                     style="background:var(--admin-accent); color:#fff;">
                                    {{ mb_substr($dayName, 0, 1) }}
                                </div>
                                <span class="font-medium" style="color:var(--admin-text);">{{ $dayName }}</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="schedules[{{ $dayNum }}][is_active]"
                                       value="1"
                                       onchange="toggleDay({{ $dayNum }}, this.checked)"
                                    {{ $isActive ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <input type="hidden" name="schedules[{{ $dayNum }}][day_of_week]" value="{{ $dayNum }}">
                        <div class="p-4" id="day-body-{{ $dayNum }}"
                             style="{{ !$isActive ? 'opacity:0.4; pointer-events:none;' : '' }}">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">ساعت شروع</label>
                                    <input type="time" name="schedules[{{ $dayNum }}][start_time]"
                                           class="time-input"
                                           value="{{ $schedule?->start_time ?? '09:00' }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">ساعت پایان</label>
                                    <input type="time" name="schedules[{{ $dayNum }}][end_time]"
                                           class="time-input"
                                           value="{{ $schedule?->end_time ?? '18:00' }}">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">شروع استراحت (اختیاری)</label>
                                    <input type="time" name="schedules[{{ $dayNum }}][break_start]"
                                           class="time-input"
                                           value="{{ $schedule?->break_start }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">پایان استراحت (اختیاری)</label>
                                    <input type="time" name="schedules[{{ $dayNum }}][break_end]"
                                           class="time-input"
                                           value="{{ $schedule?->break_end }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl p-4 mb-5 flex items-start gap-3 text-sm"
                 style="background:#EFF6FF; border:1px solid #93C5FD; color:#1D4ED8;">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>روزهایی که toggle آن‌ها خاموش است به عنوان تعطیل در نظر گرفته می‌شوند و مشتریان نمی‌توانند برای آن روز نوبت بگیرند.</p>
            </div>

            <div class="flex items-center justify-between p-4 rounded-xl" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors"
                        style="background:var(--admin-accent);"
                        onmouseover="this.style.background='var(--admin-accent-hover)'"
                        onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                    </svg>
                    ذخیره برنامه کاری
                </button>
                <a href="{{ route('admin.specialists.show', $specialist) }}"
                   class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleDay(dayNum, isActive) {
            const card = document.getElementById('day-card-' + dayNum);
            const body = document.getElementById('day-body-' + dayNum);
            if (isActive) {
                card.classList.add('active');
                body.style.opacity = '1';
                body.style.pointerEvents = '';
            } else {
                card.classList.remove('active');
                body.style.opacity = '0.4';
                body.style.pointerEvents = 'none';
            }
        }
    </script>
@endpush
