@extends('layouts.superadmin')

@section('title', 'دستگاه‌های پوش')

@section('content')
    @php
        $fa = fn ($n) => to_persian_num(number_format((float) $n));
        $statuses = \App\Http\Controllers\SuperAdmin\PushDeviceController::STATUSES;
        $statusColor = ['active' => 'var(--sa-success)', 'never_acked' => 'var(--sa-warn)', 'disabled' => 'var(--sa-danger)', 'expired' => 'var(--sa-text-dim)'];
        $apps = ['customer' => 'ماهرو (مشتری)', 'staff' => 'ماهرو همکار'];
        $roles = ['customer' => 'مشتری', 'staff' => 'کادر'];
        $date = fn ($d) => $d ? to_persian_num(\Morilog\Jalali\Jalalian::fromCarbon($d)->format('Y/m/d H:i')) : '—';
    @endphp

    @include('superadmin.push.partials.tabs')

    {{-- نسخه‌ی اپ روی گوشی‌ها --}}
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        @foreach ($versions as $app => $v)
            <div class="sa-card p-5" data-app-versions="{{ $app }}">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-bold">{{ $apps[$app] ?? $app }}</h2>
                    <span class="text-xs" style="color: var(--sa-text-dim);">آخرین نسخه‌ی منتشرشده: <span dir="ltr">{{ $v['latest'] ?? '—' }}</span></span>
                </div>
                <div class="text-sm">
                    {{ $fa($v['total']) }} گوشی فعال
                    @if ($v['latest'] !== null && $v['outdated'] > 0)
                        · <span style="color: var(--sa-warn);" data-outdated>{{ $fa($v['outdated']) }} گوشی هنوز به‌روز نشده</span>
                    @elseif ($v['latest'] !== null && $v['total'] > 0)
                        · <span style="color: var(--sa-success);">همه به‌روزند</span>
                    @endif
                </div>
                @if ($v['by_version'] !== [])
                    <div class="flex flex-wrap gap-2 mt-3 text-xs">
                        @foreach ($v['by_version'] as $version => $count)
                            <a href="{{ route('superadmin.push.devices.index', ['app' => $app, 'version' => $version === '—' ? null : $version, 'status' => 'active']) }}"
                               class="px-3 py-1 rounded-full" style="border: 1px solid var(--sa-border);">
                                <span dir="ltr">{{ $version }}</span>: {{ $fa($count) }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <form method="GET" class="sa-card p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        <div>
            <label class="sa-label" for="f-phone">شماره</label>
            <input id="f-phone" name="phone" value="{{ $filters['phone'] }}" class="sa-input" dir="ltr" inputmode="numeric" placeholder="0912...">
        </div>
        <div>
            <label class="sa-label" for="f-app">اپ</label>
            <select id="f-app" name="app" class="sa-input">
                <option value="">همه</option>
                @foreach ($apps as $key => $label)
                    <option value="{{ $key }}" @selected($filters['app'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-label" for="f-status">وضعیت</label>
            <select id="f-status" name="status" class="sa-input">
                <option value="">همه</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-label" for="f-salon">سالن</label>
            <select id="f-salon" name="salon" class="sa-input">
                <option value="">همه</option>
                @foreach ($salons as $id => $name)
                    <option value="{{ $id }}" @selected($filters['salon'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-label" for="f-maker">سازنده</label>
            <select id="f-maker" name="maker" class="sa-input">
                <option value="">همه</option>
                @foreach ($makers as $maker)
                    <option value="{{ $maker }}" @selected($filters['maker'] === $maker)>{{ $maker }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            @if ($filters['version'])<input type="hidden" name="version" value="{{ $filters['version'] }}">@endif
            <button type="submit" class="sa-btn">نمایش</button>
        </div>
    </form>

    @if ($filters['version'])
        <p class="text-sm mb-3" style="color: var(--sa-text-dim);">فقط نسخه‌ی <span dir="ltr">{{ $filters['version'] }}</span> —
            <a href="{{ route('superadmin.push.devices.index', array_filter(\Illuminate\Support\Arr::except($filters, 'version'))) }}" style="color: var(--sa-accent);">همه‌ی نسخه‌ها</a></p>
    @endif

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
            <tr style="color: var(--sa-text-dim);">
                <th class="py-3 px-4">کاربر</th>
                <th class="py-3 px-4">سالن</th>
                <th class="py-3 px-4">اپ</th>
                <th class="py-3 px-4">گوشی</th>
                <th class="py-3 px-4">ثبت</th>
                <th class="py-3 px-4">آخرین رسید</th>
                <th class="py-3 px-4">وضعیت</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($devices as $device)
                @php $status = \App\Http\Controllers\SuperAdmin\PushDeviceController::statusOf($device); @endphp
                <tr style="border-top: 1px solid var(--sa-border);" data-device-row="{{ $device->id }}">
                    <td class="py-3 px-4">
                        <a href="{{ route('superadmin.push.devices.show', $device) }}" style="color: var(--sa-accent);" class="font-bold">{{ $device->user?->name ?? '—' }}</a>
                        <div class="text-xs" dir="ltr" style="text-align: right; color: var(--sa-text-dim);">{{ $device->user?->phone }}</div>
                        <div class="text-xs" style="color: var(--sa-text-dim);">{{ $roles[$device->user?->user_type] ?? '' }}</div>
                    </td>
                    <td class="py-3 px-4">{{ $device->salon?->name ?? '—' }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $apps[$device->app] ?? $device->app }}
                        <div class="text-xs" dir="ltr" style="text-align: right; color: var(--sa-text-dim);">{{ $device->app_version ?? '—' }}{{ $device->provider !== 'fcm' ? ' · '.$device->provider : '' }}</div>
                    </td>
                    <td class="py-3 px-4">
                        <div dir="ltr" style="text-align: right;">{{ trim(($device->manufacturer ?? '').' '.($device->model ?? '')) ?: '—' }}</div>
                        <div class="text-xs" style="color: var(--sa-text-dim);">اندروید <span dir="ltr">{{ $device->os_version ?? '—' }}</span></div>
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $date($device->created_at) }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $date($device->last_receipt_at) }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">
                        <span class="sa-badge" style="margin: 0; color: {{ $statusColor[$status] }};" data-status="{{ $status }}">{{ $statuses[$status] }}</span>
                        @if ($status === 'disabled' && $device->disabled_reason)
                            <div class="text-xs mt-1" style="color: var(--sa-text-dim);">{{ \App\Http\Controllers\SuperAdmin\PushDeviceController::DISABLED_REASONS[$device->disabled_reason] ?? $device->disabled_reason }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center" style="color: var(--sa-text-dim);">دستگاهی با این شرایط ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $devices->links() }}</div>
    </div>
@endsection
