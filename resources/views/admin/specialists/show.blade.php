@extends('layouts.admin')
@section('title', 'اطلاعات متخصص')

@section('content')
    <div class="fade-in">

        {{-- Heather --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold flex-shrink-0"
                     style="background:var(--admin-accent); color:#fff;">
                    {{ mb_substr($specialist->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $specialist->name }}</h1>
                    <p class="text-sm" style="color:var(--admin-text-dim);">متخصص زیبایی</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @permission('edit-specialists')
                <a href="{{ route('admin.specialists.edit', $specialist) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-white"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    ویرایش
                </a>
                @endpermission
                <a href="{{ route('admin.schedule.index', $specialist) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:#F0FDF4; color:#166534;"
                   onmouseover="this.style.background='#DCFCE7'"
                   onmouseout="this.style.background='#F0FDF4'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    برنامه کاری هفتگی
                </a>
                <a href="{{ route('admin.specialists.work-schedule.index', $specialist) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:#EFF6FF; color:#1D4ED8;"
                   onmouseover="this.style.background='#DBEAFE'"
                   onmouseout="this.style.background='#EFF6FF'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    برنامه کاری (تکی)
                </a>
                <a href="{{ route('admin.specialists.leaves.index', $specialist) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:#F5F3FF; color:#7C3AED;"
                   onmouseover="this.style.background='#EDE9FE'"
                   onmouseout="this.style.background='#F5F3FF'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    مرخصی‌ها
                </a>
                <a href="{{ route('admin.specialists.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Left column --}}
            <div class="space-y-5">

                {{-- Contact information --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        اطلاعات تماس
                    </div>
                    <div class="p-4 space-y-3 text-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span dir="ltr" style="color:var(--admin-text);">{{ $specialist->phone }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" style="color:var(--admin-text-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span dir="ltr" style="color:var(--admin-text);">{{ $specialist->email ?? '—' }}</span>
                        </div>

                        {{-- Account connection status --}}
                        @php
                            $linkedUser = \App\Models\User::where('phone', $specialist->phone)->first();
                        @endphp
                        <div class="pt-2" style="border-top:1px solid var(--admin-border);">
                            @if($linkedUser)
                                <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium"
                                     style="background:#F0FDF4; color:#166534;">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    متصل به حساب کاربری «{{ $linkedUser->name }}»
                                </div>
                            @else
                                <div class="flex items-start gap-2 px-3 py-2 rounded-lg text-xs"
                                     style="background:#FFFBEB; color:#92400E;">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <span>
                                        هنوز هیچ کاربری با این شماره ثبت‌نام نکرده — متخصص تا ثبت‌نام با همین شماره، نمی‌تواند وارد پنل شود.
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- خدمات --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        خدمات قابل ارائه
                    </div>
                    <div class="divide-y" style="border-color:var(--admin-border);">
                        @forelse($specialist->services as $service)
                            <div class="flex justify-between items-center px-4 py-2.5 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" style="background:#16A34A;"></span>
                                    <span style="color:var(--admin-text);">{{ $service->name }}</span>
                                </div>
                                <span class="persian-number" style="color:var(--admin-text-dim);">{{ number_format($service->price) }} تومان</span>
                            </div>
                        @empty
                            <p class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">هیچ خدمتی ثبت نشده</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right column (2/3) --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Weekly work schedule --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 flex justify-between items-center text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        برنامه کاری هفتگی
                        <a href="{{ route('admin.schedule.index', $specialist) }}" class="text-xs font-normal" style="color:var(--admin-accent);">ویرایش</a>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-7 gap-2">
                            @php $days = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه','شنبه']; @endphp
                            @foreach($days as $index => $day)
                                @php
                                    $schedule = $specialist->schedules()
                                        ->where('day_of_week', $index)->where('is_active', true)->first();
                                @endphp
                                <div class="rounded-lg p-2 text-center text-xs"
                                     style="{{ $schedule ? 'background:#F0FDF4; border:1px solid #86EFAC;' : 'background:#FEF2F2; border:1px solid #FCA5A5;' }}">
                                    <div class="font-bold mb-1" style="color:var(--admin-text);">{{ $day }}</div>
                                    @if($schedule)
                                        <div class="persian-number" style="color:#166534;">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                            <br>تا<br>
                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </div>
                                    @else
                                        <div style="color:#991B1B;">تعطیل</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Single work schedule (WorkSchedule) --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 flex justify-between items-center text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        برنامه کاری (تکی)
                        <a href="{{ route('admin.specialists.work-schedule.index', $specialist) }}" class="text-xs font-normal" style="color:var(--admin-accent);">ویرایش</a>
                    </div>
                    @php
                        $workSchedule = $specialist->workSchedule;
                        $wsDays = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه','شنبه'];
                    @endphp
                    <div class="p-4 text-sm">
                        @if(!$workSchedule)
                            <p class="text-center py-4" style="color:var(--admin-text-dim);">هنوز برنامه کاری تکی تعریف نشده</p>
                        @else
                            <div class="flex items-center justify-between mb-3">
                                <span style="color:var(--admin-text-dim);">وضعیت</span>
                                @if($workSchedule->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">فعال</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#FEF2F2; color:#991B1B;">غیرفعال</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mb-3">
                                <span style="color:var(--admin-text-dim);">ساعت</span>
                                <span class="persian-number" style="color:var(--admin-text);">
                                    {{ $workSchedule->start_time?->format('H:i') }} تا {{ $workSchedule->end_time?->format('H:i') }}
                                </span>
                            </div>
                            <div>
                                <span style="color:var(--admin-text-dim);">روزهای کاری</span>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($wsDays as $dayNum => $dayName)
                                        <span class="px-2 py-1 rounded-md text-xs"
                                              style="{{ in_array($dayNum, $workSchedule->work_days ?? []) ? 'background:#EFF6FF; color:#1D4ED8;' : 'background:var(--admin-accent-light); color:var(--admin-text-light);' }}">
                                            {{ $dayName }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Current vacations --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 flex justify-between items-center text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        مرخصی‌های فعلی
                        <a href="{{ route('admin.specialists.leaves.index', $specialist) }}" class="text-xs font-normal" style="color:var(--admin-accent);">مدیریت</a>
                    </div>
                    @php
                        $currentLeaves = $specialist->leaves()
                            ->where('end_date', '>=', now())->where('status', 'approved')->get();
                    @endphp
                    @if($currentLeaves->isEmpty())
                        <p class="px-4 py-6 text-center text-sm" style="color:var(--admin-text-dim);">هیچ مرخصی فعالی وجود ندارد</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                                    <th class="px-4 py-2.5 text-right font-medium">تاریخ شروع</th>
                                    <th class="px-4 py-2.5 text-right font-medium">تاریخ پایان</th>
                                    <th class="px-4 py-2.5 text-right font-medium">دلیل</th>
                                    <th class="px-4 py-2.5 text-right font-medium">وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($currentLeaves as $leave)
                                    <tr style="border-top:1px solid var(--admin-border);">
                                        <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text);">{{ verta($leave->start_date)->format('Y/m/d') }}</td>
                                        <td class="px-4 py-2.5 persian-number" style="color:var(--admin-text);">{{ verta($leave->end_date)->format('Y/m/d') }}</td>
                                        <td class="px-4 py-2.5" style="color:var(--admin-text-dim);">{{ $leave->reason ?: '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">تایید شده</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Today's shifts --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        نوبت‌های امروز
                    </div>
                    @php
                        $todayBookings = $specialist->bookings()
                            ->whereDate('booking_time', today())
                            ->with(['user','service'])->orderBy('booking_time')->get();
                    @endphp
                    @if($todayBookings->isEmpty())
                        <p class="px-4 py-6 text-center text-sm" style="color:var(--admin-text-dim);">هیچ نوبتی برای امروز ثبت نشده</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                                    <th class="px-4 py-2.5 text-right font-medium">ساعت</th>
                                    <th class="px-4 py-2.5 text-right font-medium">مشتری</th>
                                    <th class="px-4 py-2.5 text-right font-medium">خدمت</th>
                                    <th class="px-4 py-2.5 text-right font-medium">وضعیت</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($todayBookings as $booking)
                                    @php
                                        $sm=['pending'=>['در انتظار','#FFFBEB','#92400E'],'confirmed'=>['تایید شده','#F0FDF4','#166534'],'cancelled'=>['لغو شده','#FEF2F2','#991B1B'],'completed'=>['انجام شده','#EFF6FF','#1D4ED8']];
                                        $bs=$sm[$booking->status]??[$booking->status,'#F1F5F9','#475569'];
                                    @endphp
                                    <tr style="border-top:1px solid var(--admin-border);">
                                        <td class="px-4 py-2.5 persian-number font-medium" style="color:var(--admin-text);">{{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}</td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                                     style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                                    {{ mb_substr($booking->user->name??'?',0,1) }}
                                                </div>
                                                <span style="color:var(--admin-text);">{{ $booking->user->name??'—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5" style="color:var(--admin-text-dim);">{{ $booking->service?->name ?? '—'??'—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:{{ $bs[1] }}; color:{{ $bs[2] }};">{{ $bs[0] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
