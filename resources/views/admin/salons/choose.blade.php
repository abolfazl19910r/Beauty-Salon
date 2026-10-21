{{-- «سالن‌های من» پنل مدیر (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): عضو چند سالن، سالن پنل را انتخاب می‌کند --}}
<x-guest-layout>
    @include('central.my-salons._head', ['subtitle' => 'پنل کدام سالن را باز کنید؟'])

    @if ($errors->any())
        <p class="text-sm text-center mb-4" style="color: rgb(var(--m-bad));">{{ $errors->first() }}</p>
    @endif

    <ul class="space-y-3" data-admin-salons>
        @foreach ($memberships as $salon)
            <li>
                <form method="POST" action="{{ route('admin.salons.switch') }}">
                    @csrf
                    <input type="hidden" name="salon_id" value="{{ $salon->id }}">
                    <button type="submit" @disabled($salon->is_suspended) data-admin-salon="{{ $salon->id }}"
                            class="w-full text-right flex items-center gap-3 rounded-xl p-3 border transition-colors {{ $salon->is_suspended ? 'opacity-50 cursor-not-allowed border-m-tx/10' : 'border-m-gold/20 hover:border-m-gold/60' }} {{ $current === $salon->id ? 'border-m-gold/70' : '' }}">
                        @if ($salon->logoUrl())
                            <img src="{{ $salon->logoUrl() }}" alt="" class="w-11 h-11 rounded-lg object-contain shrink-0">
                        @else
                            <span class="w-11 h-11 rounded-lg shrink-0 flex items-center justify-center font-bold text-m-gold bg-m-gold/10">{{ mb_substr($salon->name, 0, 1) }}</span>
                        @endif
                        <span class="flex-1 min-w-0">
                            <span class="block font-bold text-m-tx truncate">{{ \App\Support\SalonTitle::for($salon->name) }}</span>
                            <span class="block text-xs text-m-tx/50">
                                {{ $salon->pivot->role === 'owner' ? 'مالک' : 'منشی' }}
                                @if ($salon->is_suspended) · غیرفعال
                                @elseif ($salon->subscription_ends_at?->isPast()) · اشتراک تمام شده
                                @endif
                            </span>
                        </span>
                        @if (($unread[$salon->id] ?? 0) > 0)
                            <span class="text-xs font-bold rounded-full px-2 py-0.5 shrink-0" style="background: rgb(var(--m-gold) / .18); color: rgb(var(--m-gold2));">{{ to_persian_num((string) $unread[$salon->id]) }} اعلان</span>
                        @endif
                        <span class="text-sm text-m-gold2 shrink-0">ورود ←</span>
                    </button>
                </form>
            </li>
        @endforeach
    </ul>

    <div class="flex items-center justify-between mt-6 text-sm">
        <a href="{{ route('salon-signup.create') }}" class="text-m-gold2 hover:text-m-gold" data-add-salon>+ ساختن سالن تازه با همین حساب</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-m-tx/60 hover:text-m-gold">خروج</button>
        </form>
    </div>
</x-guest-layout>
