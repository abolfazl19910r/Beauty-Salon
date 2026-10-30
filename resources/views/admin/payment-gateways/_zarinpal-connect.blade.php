{{-- ⭐ «اتصال حساب زرین‌پال» (بسته‌ی 5ز، OAuth + PKCE) — ورودی: $connect (connection، bankAccounts)، $returnDomain، $salon، $input، $inputStyle --}}
@php
    $connection = $connect['connection'];
    $usable = $connection?->tokenUsable();
@endphp
<section class="rounded-xl p-5 space-y-3" style="background:var(--admin-card, var(--admin-bg)); border:1px solid var(--admin-border);" id="zarinpal-connect">
    <h2 class="font-bold" style="color: var(--admin-accent);">اتصال حساب زرین‌پال</h2>
    @if (! $connection)
        <p class="text-sm leading-7" style="color: var(--admin-text-dim);">
            به‌جای وارد کردن دستی کد پذیرنده، حساب زرین‌پال خود را وصل کنید: ماهرو درگاه‌های حساب را می‌خواند و کد پذیرنده‌ی درگاهی را که
            دامنه‌اش <b dir="ltr">{{ $returnDomain }}</b> است خودش ثبت می‌کند؛ اگر چنین درگاهی ندارید، از همین‌جا درخواست درگاه می‌دهید.
            ماهرو فقط اجازه‌ی دیدن درگاه‌ها و حساب‌های بانکی و درخواست درگاه را می‌گیرد.
        </p>
        <a href="{{ route('admin.payment-gateways.zarinpal.connect') }}" class="inline-block px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">اتصال حساب زرین‌پال</a>
    @else
        <p class="text-sm" style="color: var(--admin-text-dim);">
            حساب زرین‌پال وصل است
            @if ($connection->synced_at) — آخرین به‌روزرسانی {{ jalali_date($connection->synced_at, 'Y/m/d H:i') }}@endif
            @unless ($usable) <b style="color:rgb(var(--m-bad));">(اتصال منقضی شده؛ دوباره وصل شوید)</b>@endunless
        </p>

        @if (! empty($connection->terminals))
            <ul class="text-xs space-y-1" style="color: var(--admin-text-dim);">
                @foreach ($connection->terminals as $terminal)
                    <li>
                        <span dir="ltr">{{ $terminal['domain'] ?: '—' }}</span> · {{ $terminal['name'] }} · <span dir="ltr">{{ $terminal['status'] }}</span>
                        @if ($connection->terminal_id === $terminal['id']) <b style="color:rgb(var(--m-ok));">← کد پذیرنده‌ی همین درگاه ثبت شد</b>@endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($connection->requested_terminal)
            <div class="rounded-lg p-3 text-sm" style="background:rgb(var(--m-warn) / .14);">
                درخواست درگاه <span dir="ltr">{{ $connection->requested_terminal['domain'] }}</span> در انتظار تأیید زرین‌پال است
                (وضعیت: <span dir="ltr">{{ $connection->requested_terminal['status'] }}</span>). پس از تأیید «به‌روزرسانی از زرین‌پال» را بزنید.
            </div>
        @elseif ($usable && ! $connection->terminal_id && ! empty($connect['bankAccounts']))
            <form method="POST" action="{{ route('admin.payment-gateways.zarinpal.terminal') }}" class="space-y-3 rounded-lg p-3" style="border:1px dashed var(--admin-border);">
                @csrf
                <div class="text-sm font-bold">درخواست درگاه زرین‌پال برای {{ $returnDomain }}</div>
                <div>
                    <label class="block text-sm mb-1" for="zp-bank">حساب بانکی تسویه‌ی درگاه</label>
                    <select id="zp-bank" name="bank_account_id" class="{{ $input }}" style="{{ $inputStyle }}">
                        @foreach ($connect['bankAccounts'] as $account)
                            <option value="{{ $account['id'] }}">{{ $account['iban'] }} — {{ $account['holder_name'] }} {{ $account['bank'] }} ({{ $account['status'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1" for="zp-phone">موبایل پشتیبانی درگاه</label>
                        <input id="zp-phone" name="support_phone" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('support_phone', auth()->user()?->phone) }}">
                    </div>
                    <div>
                        <label class="block text-sm mb-1" for="zp-name">نام درگاه</label>
                        <input id="zp-name" name="name" maxlength="100" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ old('name', $salon->name) }}">
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ثبت درخواست درگاه در زرین‌پال</button>
            </form>
        @endif

        <div class="flex flex-wrap gap-2">
            @if ($usable)
                <form method="POST" action="{{ route('admin.payment-gateways.zarinpal.sync') }}">@csrf
                    <button type="submit" class="px-3 py-1 rounded text-sm" style="border:1px solid var(--admin-border); color: var(--admin-accent);">به‌روزرسانی از زرین‌پال</button>
                </form>
            @else
                <a href="{{ route('admin.payment-gateways.zarinpal.connect') }}" class="px-3 py-1 rounded text-sm" style="border:1px solid var(--admin-border); color: var(--admin-accent);">اتصال دوباره</a>
            @endif
            <form method="POST" action="{{ route('admin.payment-gateways.zarinpal.disconnect') }}">@csrf @method('DELETE')
                <button type="submit" class="px-3 py-1 rounded text-sm" style="border:1px solid rgb(var(--m-bad) / .35); color:rgb(var(--m-bad));"
                        data-confirm-delete data-confirm-message="اتصال حساب زرین‌پال قطع شود؟ کد پذیرنده‌ی ثبت‌شده می‌ماند.">قطع اتصال</button>
            </form>
        </div>
    @endif
</section>
