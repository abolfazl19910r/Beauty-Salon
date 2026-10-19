{{--
    بسته‌ی «ح»، ۸ (۲۰۲۶-۱۰-۲۰): نوار «رزرو موقتاً متوقف است» — $panel: admins | specialists | site.
    مدیر لینک بازکردن دارد (اگر توقف را ابرمدیر نگذاشته باشد)، متخصص و مشتری فقط پیام.
--}}
@php($bpSalon = app(\App\Support\CurrentSalon::class)->get())
@if ($bpSalon?->isBookingPaused() && ! ($panel === 'site' && request()->routeIs('bookings.create')))
    @if ($panel === 'site')<div class="container mx-auto px-4 pt-3 relative z-10">@endif
    <div data-booking-paused class="px-4 py-3 text-sm leading-7 {{ $panel === 'site' ? 'rounded-xl' : '' }}"
         style="background: rgb(var(--m-warn) / .12); {{ $panel === 'site' ? 'border' : 'border-bottom' }}: 1px solid rgb(var(--m-warn) / .35); color: rgb(var(--m-tx));">
        <p class="max-w-5xl mx-auto">
            <strong style="color: rgb(var(--m-warn));">رزرو موقتاً متوقف است.</strong>
            @if ($panel === 'site')
                {{ $bpSalon->bookingPauseMessage() }} نوبت‌های ثبت‌شده‌ی شما سر جایشان هستند.
            @elseif ($panel === 'specialists')
                نوبت تازه ثبت نمی‌شود؛ نوبت‌های ثبت‌شده مثل همیشه انجام می‌شوند.{{ $bpSalon->paused_until ? ' باز شدن خودکار: '.verta($bpSalon->paused_until)->format('Y/m/d') : '' }}
            @else
                {{ $bpSalon->isPausedByPlatform() ? 'مدیریت ماهرو رزرو این سالن را متوقف کرده است.' : 'مشتری‌ها نوبت تازه نمی‌توانند بگیرند.' }}{{ $bpSalon->paused_until ? ' باز شدن خودکار: '.verta($bpSalon->paused_until)->format('Y/m/d') : '' }}
                @unless ($bpSalon->isPausedByPlatform())
                    <a href="{{ route('admin.salon-settings.edit') }}" class="font-bold underline mr-1" style="color: rgb(var(--m-warn));">بازکردن رزرو</a>
                @endunless
            @endif
        </p>
    </div>
    @if ($panel === 'site')</div>@endif
@endif
