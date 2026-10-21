@extends('layouts.superadmin')

@section('title', 'دستگاه پوش')

@section('content')
    @php
        $statuses = \App\Http\Controllers\SuperAdmin\PushDeviceController::STATUSES;
        $statusColor = ['active' => 'var(--sa-success)', 'never_acked' => 'var(--sa-warn)', 'disabled' => 'var(--sa-danger)', 'expired' => 'var(--sa-text-dim)'];
        $apps = ['customer' => 'ماهرو (مشتری)', 'staff' => 'ماهرو همکار'];
        $date = fn ($d) => $d ? to_persian_num(\Morilog\Jalali\Jalalian::fromCarbon($d)->format('Y/m/d H:i:s')) : '—';
        $deliveryStatus = ['queued' => 'در صف', 'accepted' => 'پذیرفته‌ی FCM', 'failed' => 'ناموفق', 'invalid' => 'توکن باطل'];
        $urgencies = ['urgent' => 'فوری', 'normal' => 'عادی', 'info' => 'فقط پوش'];
        $sec = fn ($a, $b) => ($a && $b) ? to_persian_num((string) $a->diffInSeconds($b, true)).' ثانیه' : '—';
        $canSend = in_array($status, ['active', 'never_acked'], true) && $mode !== 'off';
    @endphp

    @include('superadmin.push.partials.tabs')

    <div class="mb-4"><a href="{{ route('superadmin.push.devices.index') }}" class="text-sm" style="color: var(--sa-accent);">→ همه‌ی دستگاه‌ها</a></div>

    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <div class="sa-card p-5 md:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h2 class="font-bold text-lg">{{ $device->user?->name ?? '—' }} <span class="text-sm font-normal" dir="ltr" style="color: var(--sa-text-dim);">{{ $device->user?->phone }}</span></h2>
                <span class="sa-badge" style="margin: 0; color: {{ $statusColor[$status] }};" data-status="{{ $status }}">{{ $statuses[$status] }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="sa-label">سالن</dt><dd>{{ $device->salon?->name ?? '— (کادر بدون سالن)' }}</dd></div>
                <div><dt class="sa-label">اپ</dt><dd>{{ $apps[$device->app] ?? $device->app }} <span dir="ltr">{{ $device->app_version ?? '—' }}</span>
                    @if ($latest && $device->app_version && version_compare($device->app_version, $latest->version_name, '<'))
                        <span class="text-xs" style="color: var(--sa-warn);">(آخرین: <span dir="ltr">{{ $latest->version_name }}</span>)</span>
                    @endif
                </dd></div>
                <div><dt class="sa-label">گوشی</dt><dd dir="ltr" style="text-align: right;">{{ trim(($device->manufacturer ?? '').' '.($device->model ?? '')) ?: '—' }} · Android {{ $device->os_version ?? '—' }}</dd></div>
                <div><dt class="sa-label">سرویس</dt><dd dir="ltr" style="text-align: right;">{{ strtoupper($device->provider) }}</dd></div>
                <div><dt class="sa-label">ثبت</dt><dd>{{ $date($device->created_at) }}</dd></div>
                <div><dt class="sa-label">آخرین ثبت دوباره (باز شدن اپ)</dt><dd>{{ $date($device->updated_at) }}</dd></div>
                <div><dt class="sa-label">آخرین رسید</dt><dd>{{ $date($device->last_receipt_at) }}</dd></div>
                <div><dt class="sa-label">نشست اپ</dt><dd>
                    @if ($device->accessToken)
                        آخرین استفاده {{ $date($device->accessToken->last_used_at) }}
                        @if ($device->accessToken->expires_at)<div class="text-xs" style="color: var(--sa-text-dim);">انقضا {{ $date($device->accessToken->expires_at) }}</div>@endif
                    @else
                        —
                    @endif
                </dd></div>
                @if ($device->disabled_at)
                    <div class="col-span-2"><dt class="sa-label">غیرفعال از</dt><dd style="color: var(--sa-danger);">{{ $date($device->disabled_at) }} — {{ \App\Http\Controllers\SuperAdmin\PushDeviceController::DISABLED_REASONS[$device->disabled_reason] ?? $device->disabled_reason }}</dd></div>
                @endif
            </dl>
            @if ($unhealthy)
                <p class="text-sm mt-4 p-3 rounded-lg" style="background: var(--sa-danger-bg); color: var(--sa-danger);" data-unhealthy>
                    ناسالم: چند پوش آخر این گوشی بی‌رسید مانده است. پیام‌های فوری به این کاربر مستقیم پیامک هم می‌شوند تا اولین رسید تازه.
                    (شیائومی: Autostart؛ سامسونگ: بیرون آوردن اپ از «Deep sleeping».)
                </p>
            @endif
        </div>

        <div class="sa-card p-5 space-y-4">
            <div>
                <h3 class="font-bold mb-2">پوش آزمایشی</h3>
                <p class="text-xs mb-3" style="color: var(--sa-text-dim);">بدون پیامک. چند ثانیه بعد صفحه را تازه کنید تا پذیرش و رسید را در جدول پایین ببینید. اپ را از «اخیرها» ببندید تا رسید از اپ بسته سنجیده شود.</p>
                <form method="POST" action="{{ route('superadmin.push.devices.test', $device) }}">
                    @csrf
                    <button type="submit" class="sa-btn w-full" @disabled(! $canSend)>فرستادن پوش آزمایشی</button>
                </form>
                @unless ($canSend)
                    <p class="text-xs mt-2" style="color: var(--sa-text-dim);">{{ $mode === 'off' ? 'حالت پوش پلتفرم «خاموش» است.' : 'این دستگاه فعال نیست.' }}</p>
                @endunless
            </div>
            <div style="border-top: 1px solid var(--sa-border);" class="pt-4">
                <h3 class="font-bold mb-2">خروج این دستگاه</h3>
                <p class="text-xs mb-3" style="color: var(--sa-text-dim);">نشست اپ روی این گوشی باطل می‌شود (مثلاً گوشی گم شده) و کاربر باید دوباره با رمز و کد وارد شود. حساب و داده‌ها دست نمی‌خورند.</p>
                <form method="POST" action="{{ route('superadmin.push.devices.sign-out', $device) }}" onsubmit="return confirm('این گوشی از اپ خارج شود؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-lg py-2 font-bold" style="border: 1px solid var(--sa-danger); color: var(--sa-danger);">خروج از اپ روی این گوشی</button>
                </form>
            </div>
        </div>
    </div>

    <div class="sa-card overflow-x-auto">
        <h2 class="font-bold px-5 pt-5 pb-3">۲۰ ارسال آخر</h2>
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-2 px-4">زمان</th><th class="py-2 px-4">پیام</th><th class="py-2 px-4">دسته</th>
                <th class="py-2 px-4">ارسال</th><th class="py-2 px-4">رسید</th><th class="py-2 px-4">پیامک</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($deliveries as $d)
                <tr style="border-top: 1px solid var(--sa-border);" data-delivery="{{ $d->status }}">
                    <td class="py-2 px-4 whitespace-nowrap">{{ $date($d->message?->created_at ?? $d->created_at) }}</td>
                    <td class="py-2 px-4">{{ $d->message ? \App\Services\Push\PushPolicy::title((string) $d->message->event_key) : '—' }}</td>
                    <td class="py-2 px-4 whitespace-nowrap">{{ $urgencies[$d->message?->urgency] ?? '—' }}</td>
                    <td class="py-2 px-4">
                        {{ $deliveryStatus[$d->status] ?? $d->status }}
                        @if ($d->error)<div class="text-xs" dir="ltr" style="text-align: right; color: var(--sa-danger);">{{ $d->error }}</div>@endif
                    </td>
                    <td class="py-2 px-4 whitespace-nowrap">{{ $d->acked_at ? 'بعد از '.$sec($d->message?->created_at ?? $d->created_at, $d->acked_at) : '—' }}</td>
                    <td class="py-2 px-4 whitespace-nowrap">{{ $d->message?->sms_sent_at ? 'رفت' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center" style="color: var(--sa-text-dim);">هنوز پیامی به این گوشی نرفته است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
