@extends('layouts.admin')
@section('title', 'متن پیامک‌ها')

@section('content')
    <div class="fade-in" style="max-width: 56rem;">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">متن پیامک‌ها</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    متن هر پیامک سالن را خودتان بنویسید. جای‌خالی‌ها (مثل <span dir="ltr">{تاریخ}</span>) موقع ارسال با اطلاعات واقعی پر می‌شوند؛
                    جای‌خالی‌های <b>ضروری</b> باید در متن بمانند. هر قطعه‌ی پیامک فارسی ۷۰ نویسه است (بیشتر از آن، هر ۶۷ نویسه یک قطعه) و از
                    سهمیه‌ی پیامک سالن کم می‌شود. پیامک‌های کد تأیید و ورود قابل تغییر نیستند.
                </p>
            </div>
            <a href="{{ route('admin.notification-settings.index') }}" class="text-sm font-medium" style="color:var(--admin-accent);">روشن/خاموش کردن پیامک‌ها ←</a>
        </div>

        @foreach ($groups as $audience => $templates)
            <h2 class="text-sm font-bold mb-3 mt-6" style="color:var(--admin-accent);">پیامک‌های {{ $audience }}</h2>
            @foreach ($templates as $t)
                @php($bag = $errors->getBag('sms_'.$t['key']))
                <div id="sms-{{ $t['key'] }}" class="rounded-xl p-4 mb-4" style="background:var(--admin-surface); border:1px solid {{ $bag->any() ? 'rgb(var(--m-bad))' : 'var(--admin-border)' }};"
                     data-sms-template data-placeholders='@json(collect($t['placeholders'])->map(fn ($p) => $p[1]))'>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3 class="font-bold" style="color:var(--admin-text);">{{ $t['label'] }}</h3>
                        @if ($t['custom'])
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--admin-accent-light); color:var(--admin-accent);">متن اختصاصی سالن</span>
                        @else
                            <span class="text-xs" style="color:var(--admin-text-dim);">متن پیش‌فرض</span>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.sms-templates.update', $t['key']) }}">
                        @csrf
                        @method('PUT')
                        <textarea name="body" rows="3" maxlength="{{ $maxLength }}" data-sms-body
                                  class="w-full rounded-lg px-3 py-2 text-sm" style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text); line-height:1.9;">{{ $bag->any() ? old('body') : $t['body'] }}</textarea>

                        <div class="flex flex-wrap gap-1.5 mt-2" aria-label="درج جای‌خالی">
                            @foreach ($t['placeholders'] as $name => [$hint, $sample])
                                <button type="button" data-insert="{{ '{'.$name.'}' }}" title="{{ $hint }}"
                                        class="text-xs px-2 py-1 rounded-md" dir="ltr"
                                        style="border:1px solid var(--admin-border); color:{{ in_array($name, $t['required'], true) ? 'var(--admin-accent)' : 'var(--admin-text-dim)' }}; font-weight:{{ in_array($name, $t['required'], true) ? '700' : '400' }};">{{ '{'.$name.'}' }}</button>
                            @endforeach
                        </div>
                        @if ($t['required'])
                            <p class="text-xs mt-1" style="color:var(--admin-text-dim);">ضروری: <span dir="ltr">{{ collect($t['required'])->map(fn ($r) => '{'.$r.'}')->implode(' ') }}</span></p>
                        @endif

                        @if ($bag->any())
                            <ul class="text-sm mt-2" style="color:rgb(var(--m-bad));" role="alert">
                                @foreach ($bag->get('body') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="rounded-lg p-3 mt-3 text-sm" style="background:var(--admin-bg); color:var(--admin-text);">
                            <div class="text-xs mb-1" style="color:var(--admin-text-dim);">پیش‌نمایش با اطلاعات نمونه — <span data-sms-parts></span></div>
                            <div data-sms-preview style="white-space:pre-wrap;">{{ $t['sample'] }}</div>
                        </div>

                        <div class="flex items-center gap-3 mt-3">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-m-onsolid" style="background-color: var(--admin-accent);">ذخیره</button>
                        </div>
                    </form>
                    @if ($t['custom'])
                        <form method="POST" action="{{ route('admin.sms-templates.reset', $t['key']) }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs" style="color:var(--admin-text-dim);">بازگشت به متن پیش‌فرض</button>
                        </form>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    <script>
        (function () {
            // شمارش قطعه مثل App\Support\SmsParts برای متن فارسی: طول UTF-16؛ تا ۷۰ یک قطعه، بیشتر هر ۶۷
            function parts(text) { var n = text.length; return n <= 70 ? 1 : Math.ceil(n / 67); }
            document.querySelectorAll('[data-sms-template]').forEach(function (card) {
                var samples = JSON.parse(card.getAttribute('data-placeholders') || '{}');
                var body = card.querySelector('[data-sms-body]');
                var preview = card.querySelector('[data-sms-preview]');
                var partsEl = card.querySelector('[data-sms-parts]');
                function render() {
                    var text = body.value.replace(/\{([^{}\s]+)\}/g, function (m, name) { return Object.prototype.hasOwnProperty.call(samples, name) ? samples[name] : m; });
                    text = text.replace(/ {2,}/g, ' ').trim();
                    preview.textContent = text;
                    var p = parts(text);
                    partsEl.textContent = p.toLocaleString('fa-IR') + ' قطعه، ' + text.length.toLocaleString('fa-IR') + ' نویسه';
                    partsEl.style.color = p > 2 ? '#B45309' : '';
                }
                body.addEventListener('input', render);
                card.querySelectorAll('[data-insert]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var v = btn.getAttribute('data-insert'), s = body.selectionStart || body.value.length, e = body.selectionEnd || s;
                        body.value = body.value.slice(0, s) + v + body.value.slice(e);
                        body.focus(); body.selectionStart = body.selectionEnd = s + v.length; render();
                    });
                });
                render();
            });
        })();
    </script>
@endsection
