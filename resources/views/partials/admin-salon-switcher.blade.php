{{-- جابه‌جایی بین سالن‌ها در هدر پنل مدیر (تصمیم ۵، ۲۰۲۶-۱۰-۲۲) — فقط برای عضو بیش از یک سالن؛ همه‌ی ۴ قالب این هدر را دارند --}}
@php
    $switcherUser = auth()->user();
    $switcherSalons = ($switcherUser && ! $switcherUser->hasRole('super-admin'))
        ? app(\App\Support\AdminSalonSelector::class)->memberships($switcherUser) : collect();
@endphp
@if ($switcherSalons->count() > 1 && ! \App\Services\SuperAdmin\SuperAdminImpersonation::active())
    @php $switcherUnread = app(\App\Support\AdminSalonSelector::class)->unreadCounts($switcherUser); @endphp
    <details class="relative" data-salon-switcher>
        <summary class="list-none cursor-pointer flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium max-w-[11rem]"
                 style="border: 1px solid var(--admin-border); color: var(--admin-text);" title="جابه‌جایی سالن">
            <span class="truncate">{{ $adminSalon?->name }}</span>
            @if (collect($switcherUnread)->except($adminSalon?->id)->sum() > 0)
                <span class="w-2 h-2 rounded-full shrink-0" style="background: rgb(var(--m-bad));" aria-label="اعلان در سالن دیگر"></span>
            @endif
            <svg class="w-4 h-4 shrink-0 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </summary>
        <div class="absolute left-0 md:left-auto md:right-0 z-50 mt-2 w-64 rounded-xl shadow-lg py-1" style="background: var(--admin-surface); border: 1px solid var(--admin-border);">
            @foreach ($switcherSalons as $s)
                <form method="POST" action="{{ route('admin.salons.switch') }}">
                    @csrf
                    <input type="hidden" name="salon_id" value="{{ $s->id }}">
                    <button type="submit" @disabled($s->is_suspended || $s->id === $adminSalon?->id)
                            class="w-full text-right flex items-center justify-between gap-2 px-4 py-2.5 text-sm {{ $s->is_suspended ? 'opacity-50' : '' }}"
                            style="color: {{ $s->id === $adminSalon?->id ? 'var(--admin-accent)' : 'var(--admin-text)' }}; {{ $s->id === $adminSalon?->id ? 'font-weight:700;' : '' }}">
                        <span class="truncate">{{ $s->name }} <span class="text-xs" style="color: var(--admin-text-dim);">— {{ $s->pivot->role === 'owner' ? 'مالک' : 'منشی' }}</span></span>
                        @if (($switcherUnread[$s->id] ?? 0) > 0 && $s->id !== $adminSalon?->id)
                            <span class="text-xs font-bold rounded-full px-2" style="background: rgb(var(--m-bad) / .12); color: rgb(var(--m-bad));">{{ to_persian_num((string) $switcherUnread[$s->id]) }}</span>
                        @endif
                    </button>
                </form>
            @endforeach
            <a href="{{ route('salon-signup.create') }}" class="block px-4 py-2.5 text-sm border-t" style="border-color: var(--admin-border); color: var(--admin-accent);">+ سالن تازه</a>
        </div>
    </details>
@endif
