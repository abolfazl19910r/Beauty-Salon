@extends('layouts.admin')

@section('title', 'مدیریت نوبت‌ها')

@push('styles')
    <style>
        .jcal-wrapper { position: relative; display: inline-block; }
        .jcal-popup {
            display: none; position: absolute;
            top: calc(100% + 6px); right: 0; z-index: 9999;
            width: 280px;
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12);
            padding: 12px;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; color: var(--admin-text-dim); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .jcal-header button:hover { background: var(--admin-accent-light); }
        .jcal-title { color: var(--admin-text); font-weight: bold; font-size: 13px; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
        .jcal-weekday { font-size: 10px; color: var(--admin-text-light); padding: 4px 0; }
        .jcal-day { font-size: 12px; color: var(--admin-text); padding: 6px 0; border-radius: 6px; cursor: pointer; }
        .jcal-day:hover { background: var(--admin-accent-light); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background: transparent; }
        .jcal-day.jcal-selected { background: var(--admin-accent); color: #fff; font-weight: bold; }
        .jcal-day.jcal-today { border: 1px solid var(--admin-accent); }

        .stat-mini {
            padding: 10px 16px;
            border-radius: 8px;
            text-align: center;
            min-width: 80px;
        }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                مدیریت نوبت‌ها
            </h1>
            @permission('create-bookings')
            <a href="{{ route('admin.bookings.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن نوبت
            </a>
            @endpermission
        </div>

        <div class="rounded-xl p-4 mb-5 flex flex-col md:flex-row gap-4 justify-between items-start md:items-center"
             style="background:var(--admin-surface); border:1px solid var(--admin-border);">

            <div class="flex flex-wrap items-center gap-3">
                <select onchange="window.location.href=this.value"
                        class="text-sm rounded-lg px-3 py-2 outline-none transition"
                        style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                        onfocus="this.style.borderColor='var(--admin-accent)'"
                        onblur="this.style.borderColor='var(--admin-border)'">
                    <option value="{{ route('admin.bookings.index', request('date') ? ['date' => request('date')] : []) }}"
                        {{ !request('status') ? 'selected' : '' }}>همه وضعیت‌ها</option>
                    <option value="{{ route('admin.bookings.index', array_filter(['status' => 'pending',   'date' => request('date')])) }}"
                        {{ request('status') == 'pending'   ? 'selected' : '' }}>در انتظار تایید</option>
                    <option value="{{ route('admin.bookings.index', array_filter(['status' => 'confirmed', 'date' => request('date')])) }}"
                        {{ request('status') == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                    <option value="{{ route('admin.bookings.index', array_filter(['status' => 'completed', 'date' => request('date')])) }}"
                        {{ request('status') == 'completed' ? 'selected' : '' }}>انجام شده</option>
                    <option value="{{ route('admin.bookings.index', array_filter(['status' => 'cancelled', 'date' => request('date')])) }}"
                        {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                </select>

                <div class="relative jcal-wrapper">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" style="color:var(--admin-text-light);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <input type="text" id="date-picker" readonly
                           class="text-sm rounded-lg px-3 py-2 pr-9 cursor-pointer outline-none transition persian-number"
                           style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text); min-width:140px;"
                           onfocus="this.style.borderColor='var(--admin-accent)'"
                           onblur="this.style.borderColor='var(--admin-border)'"
                           value="{{ request('date') ? verta(request('date'))->format('Y/m/d') : '' }}"
                           placeholder="انتخاب تاریخ">
                    <div class="jcal-popup" id="jcal-popup-date-picker"></div>
                </div>

                @if($hasDateFilter || request('status'))
                    <a href="{{ route('admin.bookings.index') }}"
                       class="inline-flex items-center gap-1 text-sm px-3 py-2 rounded-lg transition-colors"
                       style="border:1px solid var(--admin-border); color:var(--admin-text-dim);"
                       onmouseover="this.style.background='var(--admin-accent-light)'"
                       onmouseout="this.style.background=''">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        حذف فیلترها
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <p class="text-xs ml-2" style="color:var(--admin-text-light);">
                    {{ $hasDateFilter ? 'آمار تاریخ انتخابی' : 'آمار کل' }}
                </p>
                <div class="stat-mini" style="background:#EFF6FF;">
                    <div class="text-xs mb-0.5" style="color:#3B82F6;">کل</div>
                    <div class="text-lg font-bold persian-number" style="color:#1D4ED8;">{{ $totalBookings ?? 0 }}</div>
                </div>
                <div class="stat-mini" style="background:#F0FDF4;">
                    <div class="text-xs mb-0.5" style="color:#16A34A;">تایید</div>
                    <div class="text-lg font-bold persian-number" style="color:#166534;">{{ $confirmedBookings ?? 0 }}</div>
                </div>
                <div class="stat-mini" style="background:#FEF2F2;">
                    <div class="text-xs mb-0.5" style="color:#EF4444;">لغو</div>
                    <div class="text-lg font-bold persian-number" style="color:#991B1B;">{{ $cancelledBookings ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" dir="rtl">
                    <thead>
                    <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium">#</th>
                        <th class="px-4 py-3 text-right font-medium">مشتری</th>
                        <th class="px-4 py-3 text-right font-medium">خدمت</th>
                        <th class="px-4 py-3 text-right font-medium">متخصص</th>
                        <th class="px-4 py-3 text-right font-medium">تاریخ و ساعت</th>
                        <th class="px-4 py-3 text-right font-medium">پرداخت</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">

                            <td class="px-4 py-3 persian-number font-medium" style="color:var(--admin-text-dim);">{{ $booking->id }}</td>

                            <td class="px-4 py-3">
                                @if($booking->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                             style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                            {{ mb_substr($booking->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-medium" style="color:var(--admin-text);">{{ $booking->user->name }}</p>
                                            <p class="text-xs" dir="ltr" style="color:var(--admin-text-dim);">{{ $booking->user->phone }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span style="color:var(--admin-text-light);">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3" style="color:var(--admin-text);">{{ $booking->service->name ?? '—' }}</td>

                            <td class="px-4 py-3" style="color:var(--admin-text);">{{ $booking->specialist?->name ?? '—' }}</td>

                            <td class="px-4 py-3 persian-number">
                                <p style="color:var(--admin-text);">{{ verta($booking->booking_time)->format('Y/m/d') }}</p>
                                <p class="text-xs" style="color:var(--admin-text-dim);">{{ verta($booking->booking_time)->format('H:i') }}</p>
                            </td>

                            <td class="px-4 py-3">
                                @if($booking->payment_status == 'paid')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                          style="background:#F0FDF4; color:#166534;">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    پرداخت شده
                                </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                                          style="background:#FEF2F2; color:#991B1B;">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    پرداخت نشده
                                </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @php
                                    $statusMap = [
                                        'pending'   => ['label' => 'در انتظار', 'bg' => '#FFFBEB', 'color' => '#92400E'],
                                        'confirmed' => ['label' => 'تایید شده', 'bg' => '#F0FDF4', 'color' => '#166534'],
                                        'completed' => ['label' => 'انجام شده', 'bg' => '#EFF6FF', 'color' => '#1D4ED8'],
                                        'cancelled' => ['label' => 'لغو شده',   'bg' => '#FEF2F2', 'color' => '#991B1B'],
                                    ];
                                    $s = $statusMap[$booking->status] ?? ['label' => $booking->status, 'bg' => '#F1F5F9', 'color' => '#475569'];
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium"
                                      style="background:{{ $s['bg'] }}; color:{{ $s['color'] }};">
                                {{ $s['label'] }}
                            </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       title="مشاهده"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:var(--admin-accent);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    @permission('edit-bookings')
                                    <a href="{{ route('admin.bookings.edit', $booking) }}"
                                       title="ویرایش"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:#7C3AED;"
                                       onmouseover="this.style.background='#F5F3FF'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    @endpermission

                                    @permission('approve-bookings')
                                    @if($booking->status == 'pending')
                                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" title="تایید نوبت"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#16A34A;"
                                                    onmouseover="this.style.background='#F0FDF4'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    @endpermission

                                    @permission('delete-bookings')
                                    @if($booking->status != 'cancelled')
                                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" title="لغو نوبت"
                                                    data-confirm-delete data-confirm-message="آیا از لغو نوبت #{{ $booking->id }} اطمینان دارید؟"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#DC2626;"
                                                    onmouseover="this.style.background='#FEF2F2'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    @endpermission
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">
                                نوبتی یافت نشد
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- pagination --}}
            @if($bookings->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $bookings->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            function div(a, b) { return Math.trunc(a / b); }

            function gregorianToJalali(gy, gm, gd) {
                const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
                let jy;
                if (gy > 1600) { jy = 979; gy -= 1600; } else { jy = 0; gy -= 621; }
                const gy2 = (gm > 2) ? (gy + 1) : gy;
                let days = (365*gy) + div(gy2+3,4) - div(gy2+99,100) + div(gy2+399,400) - 80 + gd + g_d_m[gm-1];
                jy += 33 * div(days, 12053); days %= 12053;
                jy += 4 * div(days, 1461);   days %= 1461;
                if (days > 365) { jy += div(days-1, 365); days = (days-1) % 365; }
                const jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days-186, 30);
                const jd = 1 + ((days < 186) ? (days % 31) : ((days-186) % 30));
                return [jy, jm, jd];
            }

            function jalaliToGregorian(jy, jm, jd) {
                let gy;
                if (jy > 979) { gy = 1600; jy -= 979; } else { gy = 621; }
                let days = (365*jy) + (div(jy,33)*8) + div((jy%33)+3,4) + 78 + jd + ((jm<7)?(jm-1)*31:((jm-7)*30)+186);
                gy += 400 * div(days, 146097); days %= 146097;
                if (days > 36524) { gy += 100 * div(--days, 36524); days %= 36524; if (days >= 365) days++; }
                gy += 4 * div(days, 1461); days %= 1461;
                if (days > 365) { gy += div(days-1, 365); days = (days-1) % 365; }
                const gd = days + 1;
                const isLeap = (gy%4===0 && gy%100!==0) || (gy%400===0);
                const sal_a = [0,31,isLeap?29:28,31,30,31,30,31,31,30,31,30,31];
                let gm = 0, rem = gd;
                for (gm = 1; gm <= 12; gm++) { if (rem <= sal_a[gm]) break; rem -= sal_a[gm]; }
                return [gy, gm, rem];
            }

            const jMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            const jWeekdays = ['ش','ی','د','س','چ','پ','ج'];

            function jalaliMonthLength(jy, jm) {
                if (jm <= 6) return 31;
                if (jm <= 11) return 30;
                const g1 = jalaliToGregorian(jy, jm, 29);
                const g2 = jalaliToGregorian(jy+1, 1, 1);
                return Math.round((new Date(g2[0],g2[1]-1,g2[2]) - new Date(g1[0],g1[1]-1,g1[2])) / 86400000) + 28;
            }

            function gregorianStringFromJalali(jy, jm, jd) {
                const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);
                return gy + '-' + String(gm).padStart(2,'0') + '-' + String(gd).padStart(2,'0');
            }

            function buildCalendar(input, popup) {
                const today = new Date();
                const [tjy, tjm, tjd] = gregorianToJalali(today.getFullYear(), today.getMonth()+1, today.getDate());
                let viewYear = tjy, viewMonth = tjm;
                let selectedValue = input.value || '';
                if (selectedValue.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                    const p = selectedValue.split('/').map(Number);
                    viewYear = p[0]; viewMonth = p[1];
                }

                function render() {
                    const fd = jalaliToGregorian(viewYear, viewMonth, 1);
                    const startOffset = (new Date(fd[0], fd[1]-1, fd[2]).getDay() + 1) % 7;
                    const ml = jalaliMonthLength(viewYear, viewMonth);

                    let h = '<div class="jcal-header">';
                    h += '<button type="button" data-nav="prev">&#9658;</button>';
                    h += '<span class="jcal-title persian-number">' + jMonths[viewMonth-1] + ' ' + viewYear + '</span>';
                    h += '<button type="button" data-nav="next">&#9664;</button>';
                    h += '</div><div class="jcal-grid">';
                    jWeekdays.forEach(w => { h += '<div class="jcal-weekday">' + w + '</div>'; });
                    for (let i = 0; i < startOffset; i++) h += '<div class="jcal-day jcal-empty"></div>';
                    for (let d = 1; d <= ml; d++) {
                        const isToday = (viewYear===tjy && viewMonth===tjm && d===tjd);
                        const dv = viewYear+'/'+String(viewMonth).padStart(2,'0')+'/'+String(d).padStart(2,'0');
                        const isSel = (dv === selectedValue);
                        h += '<div class="jcal-day persian-number'+(isToday?' jcal-today':'')+(isSel?' jcal-selected':'')+'" data-day="'+d+'">'+d+'</div>';
                    }
                    h += '</div>';
                    popup.innerHTML = h;

                    popup.querySelector('[data-nav="prev"]').addEventListener('click', function(e) {
                        e.stopPropagation(); viewMonth--;
                        if (viewMonth < 1) { viewMonth = 12; viewYear--; } render();
                    });
                    popup.querySelector('[data-nav="next"]').addEventListener('click', function(e) {
                        e.stopPropagation(); viewMonth++;
                        if (viewMonth > 12) { viewMonth = 1; viewYear++; } render();
                    });
                    popup.querySelectorAll('.jcal-day[data-day]').forEach(function(el) {
                        el.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const gDate = gregorianStringFromJalali(viewYear, viewMonth, parseInt(this.dataset.day, 10));
                            const status = '{{ request("status") }}';
                            window.location.href = '{{ route("admin.bookings.index") }}?date=' + gDate + (status ? '&status=' + status : '');
                        });
                    });
                }
                render();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('date-picker');
                const popup = document.getElementById('jcal-popup-date-picker');
                if (input && popup) {
                    input.addEventListener('click', function(e) {
                        e.stopPropagation();
                        buildCalendar(input, popup);
                        popup.classList.add('open');
                    });
                }
                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
