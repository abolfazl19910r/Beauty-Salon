{{-- امکانی که در پلن سالن نیست (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴) — با لینک صورتحساب برای ارتقا --}}
<div class="rounded-lg p-3 text-sm mt-2" style="background:rgb(var(--m-warn) / .14); color:rgb(var(--m-warn));" data-feature-locked="{{ $feature->value }}">
    🔒 «{{ $feature->label() }}» در پلن سالن شما نیست.
    <a href="{{ route('admin.billing.index') }}" class="underline font-bold">ارتقای پلن</a>
</div>
