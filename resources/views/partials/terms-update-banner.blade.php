{{--
    پذیرش دوباره‌ی قوانین (۲۰۲۶-۱۰-۲۸): نوار سایت سالن برای مشتری‌ای که تغییر مهم قوانین را هنوز نپذیرفته. بی نسخه‌ی مهم هیچ کوئری‌ای
    اجرا نمی‌شود (LegalStatus::requiredVersion از config).
--}}
@php($tuUser = auth()->user())
@php($tuSalon = app(\App\Support\CurrentSalon::class)->get())
@if ($tuUser && $tuUser->user_type === 'customer' && $tuSalon && (int) $tuUser->salon_id === (int) $tuSalon->id && ! request()->routeIs('terms-update.*', 'legacy.terms-update.*')
    && app(\App\Services\Legal\LegalStatus::class)->pending($tuUser, \App\Services\Legal\LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS) !== [])
    <div class="container mx-auto px-4 pt-3 relative z-10">
        <div data-terms-update-banner class="px-4 py-3 text-sm leading-7 rounded-xl"
             style="background: rgb(var(--m-warn) / .12); border: 1px solid rgb(var(--m-warn) / .35); color: rgb(var(--m-tx));">
            <p class="max-w-5xl mx-auto">
                <strong style="color: rgb(var(--m-warn));">قوانین به‌روز شد.</strong>
                برای گرفتن نوبت تازه، نسخه‌ی تازه را بپذیرید.
                <a href="{{ route('terms-update.show') }}" class="font-bold underline mr-1" style="color: rgb(var(--m-warn));">دیدن تغییرها</a>
            </p>
        </div>
    </div>
@endif
