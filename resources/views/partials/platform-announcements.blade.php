{{--
    ⭐ «اطلاعیه‌ی سراسری» ابرمدیر (۲۰۲۶-۱۰-۱۵) بالای پنل مدیر/متخصص. $panel: admins | specialists.
    «اطلاع» و «هشدار» با × بسته می‌شوند (localStorage، کلید شامل زمان ویرایش تا ویرایش تازه دوباره نشان داده شود)؛ «مهم / قطعی» نه.
--}}
@php
    try {
        $platformAnnouncements = \App\Models\PlatformAnnouncement::query()->visibleTo($panel)->limit(3)->get();
    } catch (\Throwable $e) {
        $platformAnnouncements = collect(); // پیش از migrate
    }
@endphp
@foreach ($platformAnnouncements as $pa)
    @php
        $paKey = 'pa-'.$pa->id.'-'.$pa->updated_at?->timestamp;
        $paColor = ['info' => 'info', 'warning' => 'warn', 'critical' => 'bad'][$pa->level] ?? 'info';
    @endphp
    <div data-platform-announcement="{{ $paKey }}" class="px-4 py-3 text-sm flex items-start gap-3"
         style="background: rgb(var(--m-{{ $paColor }}) / .12); border-bottom: 1px solid rgb(var(--m-{{ $paColor }}) / .35); color: rgb(var(--m-tx));">
        <span class="font-bold shrink-0" style="color: rgb(var(--m-{{ $paColor }}));">{{ $pa->title }}</span>
        <span class="flex-1" style="white-space: pre-line;">{{ $pa->body }}</span>
        @if ($pa->level !== 'critical')
            <button type="button" aria-label="بستن اطلاعیه" class="shrink-0 px-2" style="color: rgb(var(--m-mu));"
                    onclick="try{localStorage.setItem(this.parentNode.dataset.platformAnnouncement,'1')}catch(e){}this.parentNode.remove()">×</button>
            <script>try{if(localStorage.getItem(@json($paKey)))document.querySelector('[data-platform-announcement="{{ $paKey }}"]').remove()}catch(e){}</script>
        @endif
    </div>
@endforeach
