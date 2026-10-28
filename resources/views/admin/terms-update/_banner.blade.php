{{-- پذیرش دوباره (۲۰۲۶-۱۰-۲۸): منشی و کادر قفل نمی‌شوند؛ فقط می‌بینند که مالک باید قوانین تازه را بپذیرد (EnsureSalonTermsAccepted) --}}
@if (! empty($salonTermsAwaitingOwner))
    <div class="px-4 py-2 text-sm" data-terms-awaiting-owner
         style="background: rgb(var(--m-warn) / .16); color: var(--admin-text); border-bottom: 1px solid rgb(var(--m-warn) / .35);">
        قوانین سالن‌ها در {{ config('brand.name') }} به‌روز شده است و مالک سالن باید نسخه‌ی تازه را بپذیرد. پنل شما و رزرو مشتری‌ها مثل همیشه کار می‌کند.
    </div>
@endif
