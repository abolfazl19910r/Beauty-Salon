{{-- بسته‌ی «ح»، ۸: توقف موقت رزرو (AdminSalonPauseController) --}}
@php($paused = $salon->isBookingPaused())
<section class="rounded-xl p-6 mb-5 space-y-3" data-salon-pause
         style="background:var(--admin-surface); border:1px solid {{ $paused ? 'rgb(var(--m-warn) / .5)' : 'var(--admin-border)' }};">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-bold" style="color: var(--admin-accent);">توقف موقت رزرو</h2>
        @if ($paused)
            <span class="text-xs font-bold px-2 py-1 rounded-full" style="background:rgb(var(--m-warn) / .15); color:rgb(var(--m-warn));">متوقف</span>
        @else
            <span class="text-xs font-bold px-2 py-1 rounded-full" style="background:rgb(var(--m-ok) / .15); color:rgb(var(--m-ok));">باز</span>
        @endif
    </div>
    <p class="text-sm" style="color: var(--admin-text-dim);">
        برای تعطیلی، تعمیرات یا سفر: رزرو آنلاین، جابه‌جایی، پرداخت و خرید دوره بسته می‌شود؛ نوبت‌های ثبت‌شده، یادآوری‌ها و ورود مشتری‌ها مثل همیشه است
        و زمان اشتراک هم ادامه پیدا می‌کند.
    </p>

    @if ($paused)
        <div class="text-sm space-y-1" style="color: var(--admin-text);">
            <div>از {{ verta($salon->paused_at)->format('Y/m/d H:i') }}
                {{ $salon->paused_until ? '— باز شدن خودکار '.verta($salon->paused_until)->format('Y/m/d') : '— تا بازکردن دستی' }}</div>
            @if (filled($salon->pause_reason))
                <div>دلیلی که مشتری می‌بیند: «{{ $salon->pause_reason }}»</div>
            @endif
        </div>
        @if ($salon->isPausedByPlatform())
            <p class="text-sm font-bold" style="color:rgb(var(--m-warn));">این توقف را مدیریت ماهرو گذاشته است؛ برای بازکردن با پشتیبانی تماس بگیرید.</p>
        @else
            <form method="POST" action="{{ route('admin.salon-settings.resume') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">بازکردن رزرو</button>
            </form>
        @endif
    @else
        <form method="POST" action="{{ route('admin.salon-settings.pause') }}" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="block text-sm mb-1" for="pause-days">مدت</label>
                <select id="pause-days" name="days" class="w-full rounded-lg px-3 py-2 text-sm" style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                    @foreach (\App\Http\Controllers\Admin\SalonSettings\AdminSalonPauseController::DURATIONS as $days => $label)
                        <option value="{{ $days }}" @selected((int) old('days', 0) === $days)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1" for="pause-reason">دلیل برای مشتری <span style="color:var(--admin-text-light);">(اختیاری)</span></label>
                <input id="pause-reason" name="reason" maxlength="200" value="{{ old('reason') }}" placeholder="مثلاً: سالن تا پایان تعمیرات بسته است."
                       class="w-full rounded-lg px-3 py-2 text-sm" style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-bold" style="border:1px solid rgb(var(--m-warn)); color:rgb(var(--m-warn));">توقف رزرو</button>
            </div>
        </form>
    @endif
</section>
