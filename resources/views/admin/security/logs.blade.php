@extends('layouts.admin')
@section('title', 'لاگ‌های امنیتی')

@push('styles')
    <style>
        /* ── jcal (same template as admin/reports/index.blade.php) ── */
        .jcal-wrapper { position: relative; }
        .jcal-popup {
            display: none; position: absolute; top: calc(100% + 6px); right: 0;
            z-index: 9999; background: var(--admin-surface); border: 1px solid var(--admin-border);
            border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.12);
            padding: 12px; width: 280px; direction: rtl;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 6px; font-size: 16px; color: var(--admin-text-dim); }
        .jcal-header button:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-header span { font-size: .875rem; font-weight: 600; color: var(--admin-text); }
        .jcal-weekdays { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; margin-bottom: 4px; }
        .jcal-weekdays span { text-align: center; font-size: .7rem; color: var(--admin-text-light); padding: 4px 0; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
        .jcal-day { text-align: center; padding: 6px 2px; font-size: .8rem; border-radius: 6px; cursor: pointer; color: var(--admin-text); transition: background .15s; }
        .jcal-day:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-day.selected { background: var(--admin-accent); color: #fff; font-weight: 600; }
        .jcal-day.today { border: 1px solid var(--admin-accent); color: var(--admin-accent); font-weight: 600; }
        .jcal-day.empty { cursor: default; }
        .jcal-day.empty:hover { background: none; }
        .jcal-today-btn { display: block; text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--admin-border); }
        .jcal-today-btn button { font-size: .75rem; padding: 3px 12px; border-radius: 6px; border: none; cursor: pointer; background: var(--admin-accent-light); color: var(--admin-accent); }
    </style>
@endpush

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    لاگ‌های امنیتی
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    تاریخچه‌ی تلاش‌های ورود، فعالیت‌های مشکوک و تغییرات حساس کل کاربران سایت.
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.security.users') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    وضعیت کاربران
                </a>
                <a href="{{ route('admin.security.settings') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    تنظیمات
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">رخداد (۳۰ روز اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:var(--admin-text);">{{ $stats['logs_last_30_days'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">هشدار (۳۰ روز اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#D97706;">{{ $stats['warnings_last_30_days'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">ورود ناموفق (۲۴ ساعت اخیر)</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#DC2626;">{{ $stats['failed_logins_last_24h'] }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs" style="color:var(--admin-text-dim);">کاربران با ۲FA فعال</p>
                <p class="text-xl font-bold mt-1 persian-number" style="color:#16A34A;">{{ $stats['users_with_2fa'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.security.logs') }}"
              class="rounded-xl p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end"
              style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">نوع رخداد</label>
                <select name="event" class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                    <option value="">همه</option>
                    @foreach(['login_attempt' => 'تلاش برای ورود', 'session_terminated' => 'پایان یک نشست', 'all_sessions_terminated' => 'پایان تمام نشست‌ها', 'payment_attempt' => 'تلاش برای پرداخت امن', 'profile_change' => 'تغییر پروفایل'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">سطح</label>
                <select name="level" class="w-full rounded-lg px-3 py-2 text-sm" style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                    <option value="">همه</option>
                    <option value="info" @selected(request('level') === 'info')>عادی</option>
                    <option value="warning" @selected(request('level') === 'warning')>هشدار</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">از تاریخ</label>
                <div class="jcal-wrapper">
                    <input type="text" id="date-from-jalali" placeholder="انتخاب کنید..." readonly
                           value="{{ request('date_from') ? jalali_date(request('date_from'), 'Y/m/d') : '' }}"
                           class="w-full rounded-lg px-3 py-2 text-sm cursor-pointer"
                           style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                    <input type="hidden" name="date_from" id="date-from-val" value="{{ request('date_from') }}">
                    <div class="jcal-popup" id="date-from-popup"></div>
                </div>
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs mb-1" style="color:var(--admin-text-dim);">تا تاریخ</label>
                    <div class="jcal-wrapper">
                        <input type="text" id="date-to-jalali" placeholder="انتخاب کنید..." readonly
                               value="{{ request('date_to') ? jalali_date(request('date_to'), 'Y/m/d') : '' }}"
                               class="w-full rounded-lg px-3 py-2 text-sm cursor-pointer"
                               style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
                        <input type="hidden" name="date_to" id="date-to-val" value="{{ request('date_to') }}">
                        <div class="jcal-popup" id="date-to-popup"></div>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white self-end" style="background-color: var(--admin-accent);">فیلتر</button>
            </div>
        </form>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background-color: var(--admin-accent-light);">
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">کاربر</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">رخداد</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">سطح</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">IP</th>
                        <th class="px-4 py-3 text-right" style="color:var(--admin-text-dim);">زمان</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr style="border-top: 1px solid var(--admin-border);">
                            <td class="px-4 py-3" style="color:var(--admin-text);">
                                @if($log->user)
                                    {{ $log->user->name }}
                                    <span class="text-xs persian-number" style="color:var(--admin-text-light);" dir="ltr">{{ $log->user->phone }}</span>
                                @else
                                    <span style="color:var(--admin-text-light);">ناشناس</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $log->event_label }}</td>
                            <td class="px-4 py-3">
                                @if($log->level === 'warning')
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:rgba(217,119,6,0.12); color:#D97706;">هشدار</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:rgba(22,163,74,0.12); color:#16A34A;">عادی</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 persian-number" dir="ltr" style="color:var(--admin-text-dim);">{{ $log->ip_address ?: '—' }}</td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ jalali_date($log->created_at, 'Y/m/d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center" style="color:var(--admin-text-light);">لاگی با این فیلترها یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
@endsection

@push('scripts')
    <script>
        /* ── jcal (Exact reproduction of admin/reports/index.blade.php — Bug fix: previously this page used the browser's input[type=date] rather than the project's self-contained solar calendar) ── */        (function () {
            function pad2(n) { return n < 10 ? '0' + n : '' + n; }
            function toJalaliShared(gy, gm, gd) { var g_d_no, j_d_no, j_np, i, j_y, j_m, j_d, g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31], j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29]; gy -= 1600; gm -= 1; gd -= 1; g_d_no = 365 * gy + Math.floor((gy + 3) / 4) - Math.floor((gy + 99) / 100) + Math.floor((gy + 399) / 400); for (i = 0; i < gm; i++) g_d_no += g_days_in_month[i]; if (gm > 1 && ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0))) g_d_no++; g_d_no += gd; j_d_no = g_d_no - 79; j_np = Math.floor(j_d_no / 12053); j_d_no %= 12053; j_y = 979 + 33 * j_np + 4 * Math.floor(j_d_no / 1461); j_d_no %= 1461; if (j_d_no >= 366) { j_y += Math.floor((j_d_no - 1) / 365); j_d_no = (j_d_no - 1) % 365; } for (i = 0; i < 11 && j_d_no >= j_days_in_month[i]; i++) j_d_no -= j_days_in_month[i]; j_m = i + 1; j_d = j_d_no + 1; return [j_y, j_m, j_d]; }
            function toGregorianShared(jy, jm, jd) { var gy, gm, gd, g_d_no, j_d_no, i, j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29], g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]; jy -= 979; jm -= 1; jd -= 1; j_d_no = 365 * jy + Math.floor(jy / 33) * 8 + Math.floor((jy % 33 + 3) / 4); for (i = 0; i < jm; i++) j_d_no += j_days_in_month[i]; j_d_no += jd; g_d_no = j_d_no + 79; gy = 1600 + 400 * Math.floor(g_d_no / 146097); g_d_no %= 146097; var leap = true; if (g_d_no >= 36525) { g_d_no--; gy += 100 * Math.floor(g_d_no / 36524); g_d_no %= 36524; if (g_d_no >= 365) g_d_no++; else leap = false; } gy += 4 * Math.floor(g_d_no / 1461); g_d_no %= 1461; if (g_d_no >= 366) { leap = false; g_d_no--; gy += Math.floor(g_d_no / 365); g_d_no %= 365; } for (i = 0; g_d_no >= g_days_in_month[i] + ((i === 1 && leap) ? 1 : 0); i++) g_d_no -= g_days_in_month[i] + ((i === 1 && leap) ? 1 : 0); gm = i + 1; gd = g_d_no + 1; return [gy, gm, gd]; }

            var toJalali = toJalaliShared, toGregorian = toGregorianShared, pad = pad2;
            var JM = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            var JD = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
            var now = new Date(), todayJ = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());

            function buildCal(popup, dispEl, hidEl, yr, mo) {
                popup.innerHTML = '';
                var hdr = document.createElement('div'); hdr.className = 'jcal-header';
                var bp = document.createElement('button'); bp.innerHTML = '&#8594;'; bp.type = 'button';
                var bn = document.createElement('button'); bn.innerHTML = '&#8592;'; bn.type = 'button';
                var ti = document.createElement('span'); ti.textContent = JM[mo - 1] + ' ' + yr;
                hdr.appendChild(bp); hdr.appendChild(ti); hdr.appendChild(bn); popup.appendChild(hdr);
                bp.onclick = function (e) { e.stopPropagation(); var m = mo - 1, y = yr; if (m < 1) { m = 12; y--; } buildCal(popup, dispEl, hidEl, y, m); };
                bn.onclick = function (e) { e.stopPropagation(); var m = mo + 1, y = yr; if (m > 12) { m = 1; y++; } buildCal(popup, dispEl, hidEl, y, m); };
                var wd = document.createElement('div'); wd.className = 'jcal-weekdays';
                JD.forEach(function (d) { var s = document.createElement('span'); s.textContent = d; wd.appendChild(s); }); popup.appendChild(wd);
                var grid = document.createElement('div'); grid.className = 'jcal-grid';
                var fg = toGregorian(yr, mo, 1); var fd = new Date(fg[0], fg[1] - 1, fg[2]); var dow = (fd.getDay() + 1) % 7;
                var dim = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29][mo - 1];
                var selVal = dispEl.value;
                var selParts = selVal ? selVal.split('/').map(Number) : null;
                for (var i = 0; i < dow; i++) { var e = document.createElement('div'); e.className = 'jcal-day empty'; grid.appendChild(e); }
                for (var d = 1; d <= dim; d++) {
                    (function (day) {
                        var cell = document.createElement('div'); cell.className = 'jcal-day'; cell.textContent = day;
                        if (todayJ[0] === yr && todayJ[1] === mo && todayJ[2] === day) cell.classList.add('today');
                        if (selParts && selParts[0] === yr && selParts[1] === mo && selParts[2] === day) cell.classList.add('selected');
                        cell.onclick = function (e) {
                            e.stopPropagation();
                            var jalStr = yr + '/' + pad(mo) + '/' + pad(day);
                            var greg = toGregorian(yr, mo, day);
                            var gregStr = greg[0] + '-' + pad(greg[1]) + '-' + pad(greg[2]);
                            dispEl.value = jalStr; hidEl.value = gregStr;
                            popup.classList.remove('open');
                        };
                        grid.appendChild(cell);
                    })(d);
                }
                popup.appendChild(grid);
                var tb = document.createElement('div'); tb.className = 'jcal-today-btn';
                var tbtn = document.createElement('button'); tbtn.type = 'button'; tbtn.textContent = 'برو به امروز';
                tbtn.onclick = function (e) { e.stopPropagation(); buildCal(popup, dispEl, hidEl, todayJ[0], todayJ[1]); };
                tb.appendChild(tbtn); popup.appendChild(tb);
            }

            function initJcal(dispId, hidId, popupId) {
                var disp = document.getElementById(dispId);
                var hid = document.getElementById(hidId);
                var popup = document.getElementById(popupId);
                if (!disp || !popup) return;
                var curY = todayJ[0], curM = todayJ[1];
                if (disp.value) { var p = disp.value.split('/').map(Number); if (p.length === 3) { curY = p[0]; curM = p[1]; } }
                disp.onclick = function (e) {
                    e.stopPropagation();
                    document.querySelectorAll('.jcal-popup.open').forEach(function (p) { if (p !== popup) p.classList.remove('open'); });
                    buildCal(popup, disp, hid, curY, curM);
                    popup.classList.toggle('open');
                };
                popup.onclick = function (e) { e.stopPropagation(); };
                document.addEventListener('click', function () { popup.classList.remove('open'); });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initJcal('date-from-jalali', 'date-from-val', 'date-from-popup');
                initJcal('date-to-jalali', 'date-to-val', 'date-to-popup');
            });
        })();
    </script>
@endpush
