@extends('layouts.specialist')

@section('title', 'مدیریت برنامه کاری')

@push('styles')
    <style>
        .day-card {
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .day-card.day-card-active {
            background-color: rgba(216, 174, 224, 0.06);
            border-color: var(--specialist-plum-mid) !important;
        }
        .toggle-track {
            transition: background-color 0.2s ease;
            background-color: var(--specialist-border);
        }
        .toggle-thumb {
            transition: transform 0.2s ease;
        }
    </style>
@endpush

@section('content')
    <div class="fade-in max-w-4xl mx-auto space-y-6">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa mb-1">مدیریت برنامه کاری</h1>
                <p class="text-sm text-[var(--specialist-text-dim)]">تنظیم ساعات کاری هفتگی</p>
            </div>
            <a href="{{ route('specialist.my-dashboard') }}"
               class="flex items-center px-4 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition"
               style="border: 1px solid var(--specialist-border);">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                بازگشت
            </a>
        </div>

        {{-- Auto-confirm card --}}
        <div class="specialist-card p-5">
            <div class="flex items-start gap-3">
                <svg class="w-7 h-7 text-[var(--specialist-plum-mid)] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-2">تایید خودکار نوبت‌های رزرو شده</h3>
                    <p class="text-sm text-[var(--specialist-text-dim)] mb-4">
                        با فعال کردن این گزینه، نوبت‌های رزرو شده به صورت خودکار تایید می‌شوند و نیازی به تایید دستی هر نوبت ندارید.
                    </p>

                    <label class="inline-flex items-center cursor-pointer gap-3 rounded-lg px-4 py-3" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                        <span class="relative inline-flex items-center toggle-switch">
                            <input type="checkbox"
                                   id="auto_confirm_toggle"
                                   name="auto_confirm_bookings"
                                   value="1"
                                   class="sr-only toggle-input"
                                {{ $specialist->auto_confirm_bookings ? 'checked' : '' }}>
                            <span class="toggle-track w-11 h-6 rounded-full block"></span>
                            <span class="toggle-thumb absolute top-0.5 right-0.5 bg-white w-5 h-5 rounded-full"></span>
                        </span>
                        <span class="font-medium text-[var(--specialist-text)]">فعال‌سازی تایید خودکار نوبت‌ها</span>
                    </label>

                    <div class="mt-4 rounded-lg p-3" style="background-color: var(--specialist-surface); border: 1px solid var(--specialist-border);">
                        <div class="flex items-start text-sm text-[var(--specialist-text-dim)] gap-2">
                            <svg class="w-5 h-5 text-[var(--specialist-plum-mid)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <div>
                                <strong class="text-[var(--specialist-text)]">توجه:</strong> با فعال کردن این گزینه:
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>نوبت‌های جدید بلافاصله تایید می‌شوند</li>
                                    <li>پیامک ارسالی به شما لینک تایید/لغو نخواهد داشت</li>
                                    <li>همچنان می‌توانید از پنل خود نوبت‌ها را مدیریت کنید</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Weekly schedule --}}
        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">ساعات کاری هفتگی</h2>
                <p class="text-sm text-[var(--specialist-text-dim)] mt-1">روزها و ساعات فعالیت خود را تنظیم کنید</p>
            </div>

            <form method="POST" action="{{ route('specialist.schedule.update') }}" id="scheduleForm">
                @csrf
                @method('PUT')

                <input type="hidden" name="auto_confirm_bookings" id="auto_confirm_hidden" value="{{ $specialist->auto_confirm_bookings ? '1' : '0' }}">

                <div class="p-5">
                    <div class="grid gap-4">
                        @php
                            $days = [
                                0 => 'یکشنبه',
                                1 => 'دوشنبه',
                                2 => 'سه‌شنبه',
                                3 => 'چهارشنبه',
                                4 => 'پنج‌شنبه',
                                5 => 'جمعه',
                                6 => 'شنبه'
                            ];
                        @endphp

                        @foreach($days as $dayNumber => $dayName)
                            @php
                                $isDayActive = isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->is_active) && $schedules[$dayNumber]->first()->is_active;
                            @endphp
                            <div class="day-card rounded-lg {{ $isDayActive ? 'day-card-active' : '' }}" style="border: 1px solid var(--specialist-border);">
                                <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--specialist-border);">
                                    <h3 class="text-base font-medium flex items-center text-[var(--specialist-text)]">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full ml-3" style="background-color: rgba(216, 174, 224, 0.12); color: var(--specialist-plum-mid);">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </span>
                                        {{ $dayName }}
                                    </h3>
                                    <label class="inline-flex items-center cursor-pointer gap-2">
                                        <span class="text-sm text-[var(--specialist-text-dim)]">فعال</span>
                                        <span class="relative inline-flex items-center toggle-switch">
                                            <input type="checkbox"
                                                   name="schedules[{{ $dayNumber }}][is_active]"
                                                   value="1"
                                                   class="sr-only toggle-input day-active-checkbox"
                                                {{ $isDayActive ? 'checked' : '' }}>
                                            <span class="toggle-track w-11 h-6 rounded-full block"></span>
                                            <span class="toggle-thumb absolute top-0.5 right-0.5 bg-white w-5 h-5 rounded-full"></span>
                                        </span>
                                    </label>
                                </div>

                                <input type="hidden"
                                       name="schedules[{{ $dayNumber }}][day_of_week]"
                                       value="{{ $dayNumber }}">

                                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-2 text-sm text-[var(--specialist-plum-muted)]">ساعت شروع</label>
                                        <input type="time"
                                               name="schedules[{{ $dayNumber }}][start_time]"
                                               value="{{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->start_time) ? $schedules[$dayNumber]->first()->start_time : '' }}"
                                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm text-[var(--specialist-plum-muted)]">ساعت پایان</label>
                                        <input type="time"
                                               name="schedules[{{ $dayNumber }}][end_time]"
                                               value="{{ isset($schedules[$dayNumber]) && isset($schedules[$dayNumber]->first()->end_time) ? $schedules[$dayNumber]->first()->end_time : '' }}"
                                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm text-[var(--specialist-plum-muted)]">شروع استراحت (اختیاری)</label>
                                        <input type="time"
                                               name="schedules[{{ $dayNumber }}][break_start]"
                                               value="{{ isset($schedules[$dayNumber]) ? $schedules[$dayNumber]->first()->break_start : '' }}"
                                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-sm text-[var(--specialist-plum-muted)]">پایان استراحت (اختیاری)</label>
                                        <input type="time"
                                               name="schedules[{{ $dayNumber }}][break_end]"
                                               value="{{ isset($schedules[$dayNumber]) ? $schedules[$dayNumber]->first()->break_end : '' }}"
                                               class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                               style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-5 border-t flex justify-end" style="border-color: var(--specialist-border);">
                    <button type="submit" class="specialist-cta px-6 py-2 rounded-lg transition-opacity hover:opacity-90">
                        <span class="flex items-center font-bold">
                            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            ذخیره تغییرات
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-lg p-4" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
            <div class="flex gap-3">
                <svg class="h-5 w-5 text-[var(--specialist-plum-mid)] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-[var(--specialist-plum-light)]">راهنمای تنظیم برنامه کاری</h3>
                    <p class="mt-1 text-sm text-[var(--specialist-text-dim)]">
                        برای فعال کردن هر روز کاری، تیک مربوط به آن روز را فعال کنید و ساعات شروع و پایان را تنظیم نمایید.
                        روزهای غیرفعال به عنوان تعطیل در نظر گرفته می‌شوند.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- toggle switches: driven entirely by JS, no CSS framework dependency ---
            function paintToggle(input) {
                const wrapper = input.closest('.toggle-switch');
                if (!wrapper) return;
                const track = wrapper.querySelector('.toggle-track');
                const thumb = wrapper.querySelector('.toggle-thumb');

                if (input.checked) {
                    track.style.backgroundColor = 'var(--specialist-plum-mid)';
                    thumb.style.transform = 'translateX(-20px)';
                } else {
                    track.style.backgroundColor = 'var(--specialist-border)';
                    thumb.style.transform = 'translateX(0)';
                }
            }

            const allToggles = document.querySelectorAll('.toggle-input');
            allToggles.forEach(input => {
                paintToggle(input); // initial state on page load
                input.addEventListener('change', function() {
                    paintToggle(this);
                });
            });

            // --- day card active/inactive highlight ---
            const checkboxes = document.querySelectorAll('input[name*="[is_active]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const container = this.closest('.day-card');
                    if (this.checked) {
                        container.classList.add('day-card-active');
                    } else {
                        container.classList.remove('day-card-active');
                    }
                });
            });

            const autoConfirmToggle = document.getElementById('auto_confirm_toggle');
            const autoConfirmHidden = document.getElementById('auto_confirm_hidden');

            autoConfirmToggle.addEventListener('change', function() {
                autoConfirmHidden.value = this.checked ? '1' : '0';
            });
        });
    </script>
@endpush
