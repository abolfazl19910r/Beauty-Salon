@extends('layouts.admin')
@section('title', 'مرخصی‌های ' . $specialist->name)

@push('styles')
    <style>
        .jcal-wrapper { position:relative; }
        .jcal-popup { display:none; position:absolute; top:calc(100% + 6px); right:0; z-index:9999; width:280px; background:var(--admin-surface); border:1px solid var(--admin-border); border-radius:10px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.12); padding:12px; }
        .jcal-popup.open { display:block; }
        .jcal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .jcal-header button { background:none; border:none; color:var(--admin-text-dim); cursor:pointer; padding:4px 8px; border-radius:6px; }
        .jcal-header button:hover { background:var(--admin-accent-light); }
        .jcal-title { color:var(--admin-text); font-weight:bold; font-size:13px; }
        .jcal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center; }
        .jcal-weekday { font-size:10px; color:var(--admin-text-light); padding:4px 0; }
        .jcal-day { font-size:12px; color:var(--admin-text); padding:6px 0; border-radius:6px; cursor:pointer; }
        .jcal-day:hover { background:var(--admin-accent-light); }
        .jcal-day.empty { cursor:default; }
        .jcal-day.empty:hover { background:transparent; }
        .jcal-day.selected { background:var(--admin-accent); color:#fff; font-weight:bold; }
        .jcal-day.today { border:1px solid var(--admin-accent); }
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:8px 12px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">مدیریت مرخصی‌ها</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $specialist->name }}</p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('leave-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                        style="background:var(--admin-accent);"
                        onmouseover="this.style.background='var(--admin-accent-hover)'"
                        onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    ثبت مرخصی جدید
                </button>
                <a href="{{ route('admin.specialists.show', $specialist) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ شروع</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ پایان</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">دلیل</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leaves as $leave)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text);">{{ verta($leave->start_date)->format('Y/m/d') }}</td>
                            <td class="px-4 py-3 persian-number" style="color:var(--admin-text);">{{ verta($leave->end_date)->format('Y/m/d') }}</td>
                            <td class="px-4 py-3" style="color:var(--admin-text-dim);">{{ $leave->reason ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#FFFBEB; color:#92400E;">در انتظار</span>
                                @elseif($leave->status === 'approved')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">تایید شده</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#FEF2F2; color:#991B1B;">رد شده</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" title="تایید"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#16A34A;"
                                                    onmouseover="this.style.background='#F0FDF4'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" title="رد"
                                                    class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                    style="color:#DC2626;"
                                                    onmouseover="this.style.background='#FEF2F2'"
                                                    onmouseout="this.style.background=''">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span style="color:var(--admin-text-light);">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">هیچ مرخصی ثبت نشده</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($leaves->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $leaves->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <div id="leave-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center"
         style="background:rgba(15,23,42,0.5);"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="rounded-xl shadow-xl w-full max-w-md mx-4 fade-in"
             style="background:var(--admin-surface);">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--admin-border);">
                <h2 class="text-base font-bold" style="color:var(--admin-text);">ثبت مرخصی جدید</h2>
                <button type="button" onclick="document.getElementById('leave-modal').classList.add('hidden')"
                        class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                        style="color:var(--admin-text-dim);"
                        onmouseover="this.style.background='var(--admin-accent-light)'"
                        onmouseout="this.style.background=''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('admin.specialists.leaves.store', $specialist) }}" method="POST">
                @csrf
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">تاریخ شروع</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="start-display" readonly class="form-input cursor-pointer persian-number" placeholder="انتخاب تاریخ">
                            <input type="hidden" name="start_date" id="start-hidden">
                            <div class="jcal-popup" id="jcal-start"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">تاریخ پایان</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="end-display" readonly class="form-input cursor-pointer persian-number" placeholder="انتخاب تاریخ">
                            <input type="hidden" name="end_date" id="end-hidden">
                            <div class="jcal-popup" id="jcal-end"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">دلیل (اختیاری)</label>
                        <textarea name="reason" rows="3" class="form-input" placeholder="توضیحات مرخصی..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-4" style="border-top:1px solid var(--admin-border);">
                    <button type="button" onclick="document.getElementById('leave-modal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg text-sm transition-colors"
                            style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                            onmouseover="this.style.background='var(--admin-border)'"
                            onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">ثبت مرخصی</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){
            function div(a,b){return Math.trunc(a/b);}
            function gregorianToJalali(gy,gm,gd){const g=[0,31,59,90,120,151,181,212,243,273,304,334];let jy;if(gy>1600){jy=979;gy-=1600;}else{jy=0;gy-=621;}const gy2=(gm>2)?(gy+1):gy;let days=(365*gy)+div(gy2+3,4)-div(gy2+99,100)+div(gy2+399,400)-80+gd+g[gm-1];jy+=33*div(days,12053);days%=12053;jy+=4*div(days,1461);days%=1461;if(days>365){jy+=div(days-1,365);days=(days-1)%365;}const jm=(days<186)?1+div(days,31):7+div(days-186,30);const jd=1+((days<186)?(days%31):((days-186)%30));return[jy,jm,jd];}
            function jalaliToGregorian(jy,jm,jd){let gy;if(jy>979){gy=1600;jy-=979;}else{gy=621;}let days=(365*jy)+(div(jy,33)*8)+div((jy%33)+3,4)+78+jd+((jm<7)?(jm-1)*31:((jm-7)*30)+186);gy+=400*div(days,146097);days%=146097;if(days>36524){gy+=100*div(--days,36524);days%=36524;if(days>=365)days++;}gy+=4*div(days,1461);days%=1461;if(days>365){gy+=div(days-1,365);days=(days-1)%365;}const gd2=days+1;const isLeap=(gy%4===0&&gy%100!==0)||(gy%400===0);const sa=[0,31,isLeap?29:28,31,30,31,30,31,31,30,31,30,31];let gm2=0,rem=gd2;for(gm2=1;gm2<=12;gm2++){if(rem<=sa[gm2])break;rem-=sa[gm2];}return[gy,gm2,rem];}
            const jM=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            const jW=['ش','ی','د','س','چ','پ','ج'];
            function jML(jy,jm){if(jm<=6)return 31;if(jm<=11)return 30;const g1=jalaliToGregorian(jy,jm,29);const g2=jalaliToGregorian(jy+1,1,1);return Math.round((new Date(g2[0],g2[1]-1,g2[2])-new Date(g1[0],g1[1]-1,g1[2]))/86400000)+28;}
            function pad(n){return String(n).padStart(2,'0');}

            function buildCal(display, hidden, popup) {
                const today = new Date();
                const [tjy,tjm,tjd] = gregorianToJalali(today.getFullYear(),today.getMonth()+1,today.getDate());
                let vy=tjy, vm=tjm;

                function render() {
                    const fd=jalaliToGregorian(vy,vm,1);
                    const off=(new Date(fd[0],fd[1]-1,fd[2]).getDay()+1)%7;
                    const ml=jML(vy,vm);
                    let h=`<div class="jcal-header"><button type="button" data-p="prev">&#9658;</button><span class="jcal-title persian-number">${jM[vm-1]} ${vy}</span><button type="button" data-p="next">&#9664;</button></div><div class="jcal-grid">`;
                    jW.forEach(w=>{h+=`<div class="jcal-weekday">${w}</div>`;});
                    for(let i=0;i<off;i++)h+='<div class="jcal-day empty"></div>';
                    const selJal=hidden.value?gregorianToJalali(...hidden.value.split('-').map(Number)):null;
                    for(let d=1;d<=ml;d++){const isT=(vy===tjy&&vm===tjm&&d===tjd);const isS=selJal&&selJal[0]===vy&&selJal[1]===vm&&selJal[2]===d;h+=`<div class="jcal-day persian-number${isT?' today':''}${isS?' selected':''}" data-d="${d}">${d}</div>`;}
                    h+='</div>';
                    popup.innerHTML=h;
                    popup.querySelector('[data-p="prev"]').addEventListener('click',e=>{e.stopPropagation();vm--;if(vm<1){vm=12;vy--;}render();});
                    popup.querySelector('[data-p="next"]').addEventListener('click',e=>{e.stopPropagation();vm++;if(vm>12){vm=1;vy++;}render();});
                    popup.querySelectorAll('.jcal-day[data-d]').forEach(el=>{
                        el.addEventListener('click',e=>{
                            e.stopPropagation();
                            const [gy,gm,gd]=jalaliToGregorian(vy,vm,parseInt(el.dataset.d,10));
                            hidden.value=`${gy}-${pad(gm)}-${pad(gd)}`;
                            display.value=`${vy}/${pad(vm)}/${pad(parseInt(el.dataset.d,10))}`;
                            popup.classList.remove('open');
                        });
                    });
                }

                display.addEventListener('click',e=>{e.stopPropagation();render();popup.classList.add('open');});
                document.addEventListener('click',()=>popup.classList.remove('open'));
                popup.addEventListener('click',e=>e.stopPropagation());
            }

            document.addEventListener('DOMContentLoaded',function(){
                buildCal(document.getElementById('start-display'),document.getElementById('start-hidden'),document.getElementById('jcal-start'));
                buildCal(document.getElementById('end-display'),document.getElementById('end-hidden'),document.getElementById('jcal-end'));
            });
        })();
    </script>
@endpush
