{{-- راهنمای «در اشتباه» صفحه‌ی ورود (LoginDoorHint): رمز با حساب نوع دیگر درست بود ← لینک صفحه‌ی ورود درست --}}
@if ($hint = session('login_door_hint'))
    <div class="mb-5 rounded-xl border border-m-gold/30 bg-m-gold/10 p-4 text-sm text-m-tx" role="alert" data-login-door-hint>
        <p class="leading-7">{{ $hint['text'] }}</p>
        <a href="{{ $hint['url'] }}" class="inline-block mt-2 font-bold text-m-gold2 hover:text-m-gold">{{ $hint['label'] }} ←</a>
    </div>
@endif
