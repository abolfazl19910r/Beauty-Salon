@extends('layouts.admin')

@section('title', 'ویرایش نوبت #' . $booking->id)

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input,.form-select,.form-textarea { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .jcal-wrapper { position:relative; }
        .jcal-popup { display:none; position:absolute; top:calc(100% + 6px); right:0; z-index:9999; width:300px; background:var(--admin-surface); border:1px solid var(--admin-border); border-radius:12px; box-shadow:0 12px 30px -5px rgba(0,0,0,0.15); padding:14px; }
        .jcal-popup.open { display:block; }
        .jcal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .jcal-header button { background:none; border:none; color:var(--admin-text-dim); cursor:pointer; padding:4px 8px; border-radius:6px; font-size:13px; }
        .jcal-header button:hover { background:var(--admin-accent-light); }
        .jcal-title { color:var(--admin-text); font-weight:bold; font-size:13px; }
        .jcal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center; }
        .jcal-weekday { font-size:10px; color:var(--admin-text-light); padding:4px 0; }
        .jcal-day { font-size:12px; color:var(--admin-text); padding:7px 0; border-radius:6px; cursor:pointer; }
        .jcal-day:hover { background:var(--admin-accent-light); }
        .jcal-day.empty { cursor:default; }
        .jcal-day.empty:hover { background:transparent; }
        .jcal-day.selected { background:var(--admin-accent); color:#fff; font-weight:bold; }
        .jcal-day.today { border:1px solid var(--admin-accent); }
        .jcal-time { display:flex; align-items:center; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid var(--admin-border); }
        .jcal-time label { font-size:12px; color:var(--admin-text-dim); white-space:nowrap; }
        .jcal-time select { flex:1; border:1px solid var(--admin-border); border-radius:6px; padding:4px 6px; font-size:13px; font-family:inherit; background:var(--admin-bg); color:var(--admin-text); outline:none; }
        .jcal-time select:focus { border-color:var(--admin-accent); }
        .jcal-confirm { margin-top:10px; width:100%; background:var(--admin-accent); color:#fff; border:none; border-radius:7px; padding:8px; font-size:13px; font-family:inherit; cursor:pointer; }
        .jcal-confirm:hover { background:var(--admin-accent-hover); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                ویرایش نوبت
                <span class="persian-number text-base font-normal" style="color:var(--admin-text-dim);">#{{ $booking->id }}</span>
            </h1>
            <a href="{{ route('admin.bookings.show', $booking) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                بازگشت
            </a>
        </div>

        <div class="rounded-xl px-5 py-3 mb-5 flex flex-wrap items-center gap-4 text-sm"
             style="background:var(--admin-accent-light); border:1px solid var(--admin-border);">
            <span style="color:var(--admin-text-dim);">وضعیت فعلی:</span>
            @php
                $statusMap=['pending'=>['در انتظار','#FFFBEB','#92400E'],'confirmed'=>['تایید شده','#F0FDF4','#166534'],'completed'=>['انجام شده','#EFF6FF','#1D4ED8'],'cancelled'=>['لغو شده','#FEF2F2','#991B1B']];
                $st=$statusMap[$booking->status]??[$booking->status,'#F1F5F9','#475569'];
            @endphp
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
            <span style="color:var(--admin-text-dim);">تاریخ نوبت:</span>
            <span class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
            <span style="color:var(--admin-text-dim);">مشتری:</span>
            <span class="font-medium" style="color:var(--admin-text);">{{ $booking->user->name ?? '—' }}</span>
        </div>

        <div class="rounded-xl p-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label for="user_id" class="form-label">مشتری</label>
                        @if($booking->payment_status === 'paid')
                            {{-- ⭐ Fix (fix/admin-booking-slot-conflict, commit 4): server-side is now
                                 enforced in UpdateAdminBookingRequest::withValidator() — this disabled
                                 select is just the matching UI cue so the admin doesn't fill out a
                                 change that's guaranteed to be rejected. disabled fields aren't
                                 submitted, so a hidden input keeps the current user_id in the payload. --}}
                            <select id="user_id" class="form-select" disabled>
                                <option>{{ $booking->user->name }} ({{ $booking->user->phone }})</option>
                            </select>
                            <input type="hidden" name="user_id" value="{{ $booking->user_id }}">
                            <p style="font-size:0.75rem; color:var(--admin-text-dim); margin-top:4px;">
                                این نوبت پرداخت شده — تغییر مشتری امکان‌پذیر نیست.
                            </p>
                        @else
                            <select id="user_id" name="user_id" class="form-select">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (old('user_id', $booking->user_id) == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->phone }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('user_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="service_id" class="form-label">خدمت</label>
                        <select id="service_id" name="service_id" class="form-select">
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ (old('service_id', $booking->service_id) == $service->id) ? 'selected' : '' }}>
                                    {{ $service->name }} — {{ number_format($service->price) }} تومان
                                </option>
                            @endforeach
                        </select>
                        @error('service_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="specialist_id" class="form-label">متخصص</label>
                        <select id="specialist_id" name="specialist_id" class="form-select">
                            @foreach($specialists as $specialist)
                                <option value="{{ $specialist->id }}" {{ (old('specialist_id', $booking->specialist_id) == $specialist->id) ? 'selected' : '' }}>
                                    {{ $specialist->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialist_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label">تاریخ و ساعت نوبت</label>
                        <div class="jcal-wrapper">
                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" style="color:var(--admin-text-light);">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                </div>
                                <input type="text" id="booking_time_display" readonly
                                       class="form-input pr-9 cursor-pointer persian-number"
                                       value="{{ verta($booking->booking_time)->format('Y/m/d H:i') }}">
                            </div>
                            <input type="hidden" name="booking_time" id="booking_time_hidden"
                                   value="{{ old('booking_time', $booking->booking_time) }}">
                            <div class="jcal-popup" id="jcal-edit"></div>
                        </div>
                        @error('booking_time') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="form-label">وضعیت نوبت</label>
                        <select id="status" name="status" class="form-select">
                            <option value="pending"   {{ old('status',$booking->status)=='pending'   ?'selected':'' }}>در انتظار تایید</option>
                            <option value="confirmed" {{ old('status',$booking->status)=='confirmed' ?'selected':'' }}>تایید شده</option>
                            <option value="completed" {{ old('status',$booking->status)=='completed' ?'selected':'' }}>انجام شده</option>
                            <option value="cancelled" {{ old('status',$booking->status)=='cancelled' ?'selected':'' }}>لغو شده</option>
                        </select>
                        @error('status') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="payment_status" class="form-label">وضعیت پرداخت</label>
                        <select id="payment_status" name="payment_status" class="form-select">
                            <option value="unpaid" {{ old('payment_status',$booking->payment_status)=='unpaid'?'selected':'' }}>پرداخت نشده</option>
                            <option value="paid"   {{ old('payment_status',$booking->payment_status)=='paid'  ?'selected':'' }}>پرداخت شده</option>
                        </select>
                        @error('payment_status') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="form-label">یادداشت (اختیاری)</label>
                        <textarea id="notes" name="notes" rows="3" class="form-textarea">{{ old('notes', $booking->notes) }}</textarea>
                        @error('notes') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6 pt-5" style="border-top:1px solid var(--admin-border);">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                        </svg>
                        ذخیره تغییرات
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking) }}"
                       class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                       style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                       onmouseover="this.style.background='var(--admin-border)'"
                       onmouseout="this.style.background='var(--admin-accent-light)'">
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){
            function div(a,b){return Math.trunc(a/b);}
            function gregorianToJalali(gy,gm,gd){const g_d_m=[0,31,59,90,120,151,181,212,243,273,304,334];let jy;if(gy>1600){jy=979;gy-=1600;}else{jy=0;gy-=621;}const gy2=(gm>2)?(gy+1):gy;let days=(365*gy)+div(gy2+3,4)-div(gy2+99,100)+div(gy2+399,400)-80+gd+g_d_m[gm-1];jy+=33*div(days,12053);days%=12053;jy+=4*div(days,1461);days%=1461;if(days>365){jy+=div(days-1,365);days=(days-1)%365;}const jm=(days<186)?1+div(days,31):7+div(days-186,30);const jd=1+((days<186)?(days%31):((days-186)%30));return[jy,jm,jd];}
            function jalaliToGregorian(jy,jm,jd){let gy;if(jy>979){gy=1600;jy-=979;}else{gy=621;}let days=(365*jy)+(div(jy,33)*8)+div((jy%33)+3,4)+78+jd+((jm<7)?(jm-1)*31:((jm-7)*30)+186);gy+=400*div(days,146097);days%=146097;if(days>36524){gy+=100*div(--days,36524);days%=36524;if(days>=365)days++;}gy+=4*div(days,1461);days%=1461;if(days>365){gy+=div(days-1,365);days=(days-1)%365;}const gd2=days+1;const isLeap=(gy%4===0&&gy%100!==0)||(gy%400===0);const sal_a=[0,31,isLeap?29:28,31,30,31,30,31,31,30,31,30,31];let gm2=0,rem=gd2;for(gm2=1;gm2<=12;gm2++){if(rem<=sal_a[gm2])break;rem-=sal_a[gm2];}return[gy,gm2,rem];}
            const jMonths=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            const jWeekdays=['ش','ی','د','س','چ','پ','ج'];
            function jalaliMonthLength(jy,jm){if(jm<=6)return 31;if(jm<=11)return 30;const g1=jalaliToGregorian(jy,jm,29);const g2=jalaliToGregorian(jy+1,1,1);return Math.round((new Date(g2[0],g2[1]-1,g2[2])-new Date(g1[0],g1[1]-1,g1[2]))/86400000)+28;}
            function pad(n){return String(n).padStart(2,'0');}

            function initDatetimePicker(displayInput, hiddenInput, popup) {
                const today = new Date();
                const [tjy,tjm,tjd] = gregorianToJalali(today.getFullYear(), today.getMonth()+1, today.getDate());
                let selJY=null,selJM=null,selJD=null,selHour=9,selMinute=0;
                let viewYear=tjy,viewMonth=tjm;

                const initVal = hiddenInput.value;
                if (initVal) {
                    const d = new Date(initVal);
                    if (!isNaN(d)) {
                        [selJY,selJM,selJD]=gregorianToJalali(d.getFullYear(),d.getMonth()+1,d.getDate());
                        selHour=d.getHours(); selMinute=d.getMinutes();
                        viewYear=selJY; viewMonth=selJM;
                    }
                }

                function buildHourOpts(){let h='';for(let i=6;i<=23;i++)h+=`<option value="${i}" ${i===selHour?'selected':''}>${pad(i)}</option>`;return h;}
                function buildMinOpts(){let h='';for(let i=0;i<60;i+=15)h+=`<option value="${i}" ${i===selMinute?'selected':''}>${pad(i)}</option>`;return h;}

                function render(){
                    const fd=jalaliToGregorian(viewYear,viewMonth,1);
                    const startOffset=(new Date(fd[0],fd[1]-1,fd[2]).getDay()+1)%7;
                    const ml=jalaliMonthLength(viewYear,viewMonth);
                    let h=`<div class="jcal-header"><button type="button" data-nav="prev">&#9658;</button><span class="jcal-title persian-number">${jMonths[viewMonth-1]} ${viewYear}</span><button type="button" data-nav="next">&#9664;</button></div><div class="jcal-grid">`;
                    jWeekdays.forEach(w=>{h+=`<div class="jcal-weekday">${w}</div>`;});
                    for(let i=0;i<startOffset;i++)h+='<div class="jcal-day empty"></div>';
                    for(let d=1;d<=ml;d++){const isT=(viewYear===tjy&&viewMonth===tjm&&d===tjd);const isS=(selJY===viewYear&&selJM===viewMonth&&selJD===d);h+=`<div class="jcal-day persian-number${isT?' today':''}${isS?' selected':''}" data-day="${d}">${d}</div>`;}
                    h+=`</div><div class="jcal-time"><label>ساعت:</label><select id="jcal-hour-e">${buildHourOpts()}</select><label>دقیقه:</label><select id="jcal-minute-e">${buildMinOpts()}</select></div><button type="button" class="jcal-confirm">تأیید</button>`;
                    popup.innerHTML=h;
                    popup.querySelector('[data-nav="prev"]').addEventListener('click',e=>{e.stopPropagation();viewMonth--;if(viewMonth<1){viewMonth=12;viewYear--;}render();});
                    popup.querySelector('[data-nav="next"]').addEventListener('click',e=>{e.stopPropagation();viewMonth++;if(viewMonth>12){viewMonth=1;viewYear++;}render();});
                    popup.querySelectorAll('.jcal-day[data-day]').forEach(el=>{el.addEventListener('click',e=>{e.stopPropagation();selJY=viewYear;selJM=viewMonth;selJD=parseInt(el.dataset.day,10);render();});});
                    popup.querySelector('#jcal-hour-e').addEventListener('change',e=>{selHour=parseInt(e.target.value,10);});
                    popup.querySelector('#jcal-minute-e').addEventListener('change',e=>{selMinute=parseInt(e.target.value,10);});
                    popup.querySelector('.jcal-confirm').addEventListener('click',e=>{
                        e.stopPropagation();if(!selJY)return;
                        const [gy,gm,gd]=jalaliToGregorian(selJY,selJM,selJD);
                        hiddenInput.value=`${gy}-${pad(gm)}-${pad(gd)} ${pad(selHour)}:${pad(selMinute)}:00`;
                        displayInput.value=`${selJY}/${pad(selJM)}/${pad(selJD)} ${pad(selHour)}:${pad(selMinute)}`;
                        popup.classList.remove('open');
                    });
                }

                displayInput.addEventListener('click',e=>{e.stopPropagation();render();popup.classList.add('open');});
                document.addEventListener('click',()=>popup.classList.remove('open'));
                popup.addEventListener('click',e=>e.stopPropagation());
            }

            document.addEventListener('DOMContentLoaded', function(){
                initDatetimePicker(
                    document.getElementById('booking_time_display'),
                    document.getElementById('booking_time_hidden'),
                    document.getElementById('jcal-edit')
                );
            });
        })();
    </script>
@endpush
