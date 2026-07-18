@extends('layouts.admin')
@section('title', 'ویرایش اطلاعیه')

@push('styles')
    <style>
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
    <div class="fade-in max-w-2xl">
        <h1 class="text-xl font-bold mb-5" style="color:var(--admin-text);">ویرایش اطلاعیه</h1>

        @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(220,38,38,0.1); color: #DC2626;">
                <ul class="list-disc pr-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST"
              class="rounded-xl p-6 space-y-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm mb-1" style="color:var(--admin-text);">عنوان</label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required
                       class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
            </div>

            <div>
                <label class="block text-sm mb-1" style="color:var(--admin-text);">متن اطلاعیه</label>
                <textarea name="content" rows="4" required
                          class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">{{ old('content', $announcement->content) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">نوع</label>
                    <select name="type" required class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                        <option value="general" @selected(old('type', $announcement->type) === 'general')>عمومی</option>
                        <option value="maintenance" @selected(old('type', $announcement->type) === 'maintenance')>تعمیرات</option>
                        <option value="promotion" @selected(old('type', $announcement->type) === 'promotion')>تبلیغاتی</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">اولویت</label>
                    <input type="number" name="priority" min="0" value="{{ old('priority', $announcement->priority) }}" required
                           class="w-full px-3 py-2 rounded-lg" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">تاریخ انتشار</label>
                    <div class="flex gap-2">
                        <div class="jcal-wrapper flex-1">
                            <input type="text" id="published-jalali" placeholder="انتخاب کنید..." readonly
                                   value="{{ old('published_at_jalali', $announcement->published_at ? jalali_date($announcement->published_at,'Y/m/d') : '') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm cursor-pointer"
                                   style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                            <input type="hidden" name="published_at" id="published-hidden"
                                   value="{{ old('published_at', $announcement->published_at) }}">
                            <div class="jcal-popup" id="published-popup"></div>
                        </div>
                        <input type="time" id="published-time"
                               value="{{ $announcement->published_at ? $announcement->published_at->format('H:i') : '00:00' }}"
                               class="w-24 rounded-lg px-2 py-2 text-sm" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1" style="color:var(--admin-text);">تاریخ انقضا (اختیاری)</label>
                    <div class="flex gap-2">
                        <div class="jcal-wrapper flex-1">
                            <input type="text" id="expires-jalali" placeholder="انتخاب کنید..." readonly
                                   value="{{ old('expires_at_jalali', $announcement->expires_at ? jalali_date($announcement->expires_at,'Y/m/d') : '') }}"
                                   class="w-full rounded-lg px-3 py-2 text-sm cursor-pointer"
                                   style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                            <input type="hidden" name="expires_at" id="expires-hidden"
                                   value="{{ old('expires_at', $announcement->expires_at) }}">
                            <div class="jcal-popup" id="expires-popup"></div>
                        </div>
                        <input type="time" id="expires-time"
                               value="{{ $announcement->expires_at ? $announcement->expires_at->format('H:i') : '00:00' }}"
                               class="w-24 rounded-lg px-2 py-2 text-sm" style="border:1px solid var(--admin-border); color:var(--admin-text);">
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm" style="color:var(--admin-text);">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                فعال باشد
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.announcements.index') }}"
                   class="px-4 py-2 rounded-lg text-sm" style="border:1px solid var(--admin-border); color:var(--admin-text-dim);">انصراف</a>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm text-white" style="background-color: var(--admin-accent);">
                    به‌روزرسانی
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        /* ── jcal (عیناً از admin/reports/index.blade.php) ── */
        (function(){
            function toJalali(gy,gm,gd){var g_d_no,j_d_no,j_np,i,j_y,j_m,j_d,g_days_in_month=[31,28,31,30,31,30,31,31,30,31,30,31],j_days_in_month=[31,31,31,31,31,31,30,30,30,30,30,29];gy-=1600;gm-=1;gd-=1;g_d_no=365*gy+Math.floor((gy+3)/4)-Math.floor((gy+99)/100)+Math.floor((gy+399)/400);for(i=0;i<gm;i++)g_d_no+=g_days_in_month[i];if(gm>1&&((gy%4===0&&gy%100!==0)||(gy%400===0)))g_d_no++;g_d_no+=gd;j_d_no=g_d_no-79;j_np=Math.floor(j_d_no/12053);j_d_no%=12053;j_y=979+33*j_np+4*Math.floor(j_d_no/1461);j_d_no%=1461;if(j_d_no>=366){j_y+=Math.floor((j_d_no-1)/365);j_d_no=(j_d_no-1)%365;}for(i=0;i<11&&j_d_no>=j_days_in_month[i];i++)j_d_no-=j_days_in_month[i];j_m=i+1;j_d=j_d_no+1;return[j_y,j_m,j_d];}
            function toGregorian(jy,jm,jd){var gy,gm,gd,g_d_no,j_d_no,i,j_days_in_month=[31,31,31,31,31,31,30,30,30,30,30,29],g_days_in_month=[31,28,31,30,31,30,31,31,30,31,30,31];jy-=979;jm-=1;jd-=1;j_d_no=365*jy+Math.floor(jy/33)*8+Math.floor((jy%33+3)/4);for(i=0;i<jm;i++)j_d_no+=j_days_in_month[i];j_d_no+=jd;g_d_no=j_d_no+79;gy=1600+400*Math.floor(g_d_no/146097);g_d_no%=146097;var leap=true;if(g_d_no>=36525){g_d_no--;gy+=100*Math.floor(g_d_no/36524);g_d_no%=36524;if(g_d_no>=365)g_d_no++;else leap=false;}gy+=4*Math.floor(g_d_no/1461);g_d_no%=1461;if(g_d_no>=366){leap=false;g_d_no--;gy+=Math.floor(g_d_no/365);g_d_no%=365;}for(i=0;g_d_no>=g_days_in_month[i]+((i===1&&leap)?1:0);i++)g_d_no-=g_days_in_month[i]+((i===1&&leap)?1:0);gm=i+1;gd=g_d_no+1;return[gy,gm,gd];}
            var JM=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            var JD=['ش','ی','د','س','چ','پ','ج'];
            function pad(n){return n<10?'0'+n:''+n;}
            var now=new Date(), todayJ=toJalali(now.getFullYear(),now.getMonth()+1,now.getDate());

            function combine(hidEl, dateStr, timeEl){
                var t = timeEl.value || '00:00';
                hidEl.value = dateStr + ' ' + t + ':00';
            }

            function buildCal(popup,dispEl,hidEl,timeEl,yr,mo){
                popup.innerHTML='';
                var hdr=document.createElement('div'); hdr.className='jcal-header';
                var bp=document.createElement('button'); bp.innerHTML='&#8594;'; bp.type='button';
                var bn=document.createElement('button'); bn.innerHTML='&#8592;'; bn.type='button';
                var ti=document.createElement('span'); ti.textContent=JM[mo-1]+' '+yr;
                hdr.appendChild(bp); hdr.appendChild(ti); hdr.appendChild(bn); popup.appendChild(hdr);
                bp.onclick=function(e){e.stopPropagation();var m=mo-1,y=yr;if(m<1){m=12;y--;}buildCal(popup,dispEl,hidEl,timeEl,y,m);};
                bn.onclick=function(e){e.stopPropagation();var m=mo+1,y=yr;if(m>12){m=1;y++;}buildCal(popup,dispEl,hidEl,timeEl,y,m);};
                var wd=document.createElement('div'); wd.className='jcal-weekdays';
                JD.forEach(function(d){var s=document.createElement('span');s.textContent=d;wd.appendChild(s);}); popup.appendChild(wd);
                var grid=document.createElement('div'); grid.className='jcal-grid';
                var fg=toGregorian(yr,mo,1); var fd=new Date(fg[0],fg[1]-1,fg[2]); var dow=(fd.getDay()+1)%7;
                var dim=[31,31,31,31,31,31,30,30,30,30,30,29][mo-1];
                var selVal=dispEl.value;
                var selParts=selVal?selVal.split('/').map(Number):null;
                for(var i=0;i<dow;i++){var e=document.createElement('div');e.className='jcal-day empty';grid.appendChild(e);}
                for(var d=1;d<=dim;d++){
                    (function(day){
                        var cell=document.createElement('div'); cell.className='jcal-day'; cell.textContent=day;
                        if(todayJ[0]===yr&&todayJ[1]===mo&&todayJ[2]===day) cell.classList.add('today');
                        if(selParts&&selParts[0]===yr&&selParts[1]===mo&&selParts[2]===day) cell.classList.add('selected');
                        cell.onclick=function(e){
                            e.stopPropagation();
                            var jalStr=yr+'/'+pad(mo)+'/'+pad(day);
                            var greg=toGregorian(yr,mo,day);
                            var gregStr=greg[0]+'-'+pad(greg[1])+'-'+pad(greg[2]);
                            dispEl.value=jalStr;
                            combine(hidEl, gregStr, timeEl);
                            popup.classList.remove('open');
                        };
                        grid.appendChild(cell);
                    })(d);
                }
                popup.appendChild(grid);
                var tb=document.createElement('div'); tb.className='jcal-today-btn';
                var tbtn=document.createElement('button'); tbtn.type='button'; tbtn.textContent='برو به امروز';
                tbtn.onclick=function(e){e.stopPropagation();buildCal(popup,dispEl,hidEl,timeEl,todayJ[0],todayJ[1]);};
                tb.appendChild(tbtn); popup.appendChild(tb);
            }

            function initJcal(dispId,hidId,popupId,timeId){
                var disp=document.getElementById(dispId);
                var hid=document.getElementById(hidId);
                var popup=document.getElementById(popupId);
                var timeEl=document.getElementById(timeId);
                if(!disp||!popup) return;
                var curY=todayJ[0], curM=todayJ[1];
                if(disp.value){var p=disp.value.split('/').map(Number);if(p.length===3){curY=p[0];curM=p[1];}}
                disp.onclick=function(e){
                    e.stopPropagation();
                    document.querySelectorAll('.jcal-popup.open').forEach(function(p){if(p!==popup)p.classList.remove('open');});
                    buildCal(popup,disp,hid,timeEl,curY,curM);
                    popup.classList.toggle('open');
                };
                popup.onclick=function(e){e.stopPropagation();};
                document.addEventListener('click',function(){popup.classList.remove('open');});
                timeEl.addEventListener('change', function(){
                    if(!disp.value) return;
                    var p=disp.value.split('/').map(Number);
                    var greg=toGregorian(p[0],p[1],p[2]);
                    combine(hid, greg[0]+'-'+pad(greg[1])+'-'+pad(greg[2]), timeEl);
                });
            }

            document.addEventListener('DOMContentLoaded',function(){
                initJcal('published-jalali','published-hidden','published-popup','published-time');
                initJcal('expires-jalali','expires-hidden','expires-popup','expires-time');
            });
        })();
    </script>
@endpush
