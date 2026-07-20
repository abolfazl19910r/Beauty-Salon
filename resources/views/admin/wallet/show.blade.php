@extends('layouts.admin')
@section('title', 'کیف پول ' . ($wallet->specialist?->name ?? ''))

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">کیف پول متخصص</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $wallet->specialist?->name ?? '—' }} — {{ $wallet->specialist?->phone ?? '—' }}</p>
            </div>
            <div class="flex gap-2">
                @if($wallet->pending_amount > 0)
                    <button onclick="document.getElementById('settlePendingModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:#16A34A;"
                            onmouseover="this.style.background='#15803D'"
                            onmouseout="this.style.background='#16A34A'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        تسویه‌ی دستی این متخصص
                    </button>
                @endif
                <button onclick="document.getElementById('adjustmentModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
                        style="background:#7C3AED;"
                        onmouseover="this.style.background='#6D28D9'"
                        onmouseout="this.style.background='#7C3AED'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    تعدیل دستی
                </button>
                <a href="{{ route('admin.wallet.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        {{-- Statistics cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid var(--admin-accent);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">موجودی فعلی</p>
                <p class="text-xl font-bold persian-number" style="color:var(--admin-text);">{{ number_format($wallet->balance) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تومان</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #F59E0B;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">در انتظار تسویه</p>
                <p class="text-xl font-bold persian-number" style="color:#D97706;">{{ number_format($wallet->pending_amount) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تومان</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #16A34A;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل درآمد</p>
                <p class="text-xl font-bold persian-number" style="color:#16A34A;">{{ number_format($wallet->total_earned) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تومان</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #DC2626;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل برداشت شده</p>
                <p class="text-xl font-bold persian-number" style="color:#DC2626;">{{ number_format($wallet->total_withdrawn) }}</p>
                <p class="text-xs mt-0.5" style="color:var(--admin-text-light);">تومان</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Bank information --}}
            <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات حساب بانکی</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim);">شماره شبا</p>
                        <p class="font-mono font-bold text-xs" dir="ltr" style="color:var(--admin-text);">{{ $wallet->formatted_iban ?? 'ثبت نشده' }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim);">صاحب حساب</p>
                        <p style="color:var(--admin-text);">{{ $wallet->account_holder_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim);">نام بانک</p>
                        <p style="color:var(--admin-text);">{{ $wallet->bank_name ?? '—' }}</p>
                    </div>
                    <div class="pt-2">
                        @if($wallet->iban_verified)
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">✓ تایید شده</span>
                        @elseif($wallet->iban)
                            <form action="{{ route('admin.wallet.verify-iban', $wallet) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full py-2 rounded-lg text-xs font-bold transition-colors"
                                        style="background:var(--admin-accent-light); color:var(--admin-accent);"
                                        onmouseover="this.style.background='var(--admin-border)'"
                                        onmouseout="this.style.background='var(--admin-accent-light)'">
                                    تایید دستی شماره شبا
                                </button>
                            </form>
                        @else
                            <span class="text-xs" style="color:var(--admin-text-light);">شبایی ثبت نشده</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent transactions --}}
            <div class="lg:col-span-2 rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                    تراکنش‌های اخیر
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">نوع</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">مبلغ</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">توضیحات</th>
                            <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">موجودی بعد</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($wallet->transactions()->latest()->paginate(10) as $transaction)
                            <tr style="border-top:1px solid var(--admin-border);"
                                onmouseover="this.style.background='var(--admin-accent-light)'"
                                onmouseout="this.style.background=''">
                                <td class="px-4 py-2.5 persian-number text-xs" style="color:var(--admin-text-dim);">{{ verta($transaction->created_at)->format('Y/m/d H:i') }}</td>
                                <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded-md text-xs font-medium"
                                      style="{{ $transaction->type=='income' ? 'background:#F0FDF4; color:#166534;' : ($transaction->type=='withdrawal' ? 'background:#FEF2F2; color:#991B1B;' : 'background:var(--admin-accent-light); color:var(--admin-accent);') }}">
                                    {{ $transaction->type_text }}
                                </span>
                                </td>
                                <td class="px-4 py-2.5 font-bold persian-number" style="color:{{ $transaction->amount >= 0 ? '#16A34A' : '#DC2626' }};">
                                    {{ number_format($transaction->amount) }}
                                </td>
                                <td class="px-4 py-2.5 max-w-xs truncate" style="color:var(--admin-text-dim);">{{ $transaction->description }}</td>
                                <td class="px-4 py-2.5 persian-number text-xs" style="color:var(--admin-text-dim);">{{ number_format($transaction->balance_after) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">تراکنشی یافت نشد</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: manual settlement of pending amount for this specialist only --}}
    <div id="settlePendingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center"
         style="background:rgba(15,23,42,0.5);"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="rounded-xl shadow-xl w-full max-w-md mx-4 fade-in" style="background:var(--admin-surface);">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--admin-border);">
                <h2 class="text-base font-bold" style="color:var(--admin-text);">تسویه‌ی دستی موجودی در انتظار</h2>
                <button type="button" onclick="document.getElementById('settlePendingModal').classList.add('hidden')"
                        class="w-7 h-7 rounded flex items-center justify-center"
                        style="color:var(--admin-text-dim);"
                        onmouseover="this.style.background='var(--admin-accent-light)'"
                        onmouseout="this.style.background=''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('admin.wallet.settle-pending-wallet', $wallet) }}" method="POST">
                @csrf
                <div class="p-5 space-y-3 text-sm">
                    <p style="color:var(--admin-text-dim);">
                        مبلغ در انتظار فعلی این متخصص:
                        <span class="font-bold persian-number" style="color:#D97706;">{{ number_format($wallet->pending_amount) }}</span>
                        تومان
                    </p>
                    <label class="flex items-center gap-2" style="color:var(--admin-text-dim);">
                        <input type="checkbox" name="ignore_delay" value="1" class="rounded">
                        نادیده گرفتن مهلت تسویه (حتی تراکنش‌های هنوز سررسیدنشده هم تسویه شوند)
                    </label>
                </div>
                <div class="flex justify-end gap-2 px-5 py-4" style="border-top:1px solid var(--admin-border);">
                    <button type="button" onclick="document.getElementById('settlePendingModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg text-sm"
                            style="background:var(--admin-accent-light); color:var(--admin-text-dim);">انصراف</button>
                    <button type="submit"
                            data-confirm-action
                            data-confirm-message="این عملیات مبلغ در انتظار این متخصص را به موجودی قابل‌برداشت او منتقل می‌کند. ادامه می‌دهید؟"
                            data-confirm-text="بله، تسویه شود"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:#16A34A;"
                            onmouseover="this.style.background='#15803D'"
                            onmouseout="this.style.background='#16A34A'">تسویه شود</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal modifier --}}
    <div id="adjustmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center"
         style="background:rgba(15,23,42,0.5);"
         onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="rounded-xl shadow-xl w-full max-w-md mx-4 fade-in" style="background:var(--admin-surface);">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--admin-border);">
                <h2 class="text-base font-bold" style="color:var(--admin-text);">تعدیل دستی موجودی</h2>
                <button type="button" onclick="document.getElementById('adjustmentModal').classList.add('hidden')"
                        class="w-7 h-7 rounded flex items-center justify-center"
                        style="color:var(--admin-text-dim);"
                        onmouseover="this.style.background='var(--admin-accent-light)'"
                        onmouseout="this.style.background=''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('admin.wallet.adjust', $wallet) }}" method="POST">
                @csrf
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">مبلغ (تومان)</label>
                        <input type="number" name="amount" required
                               class="w-full rounded-lg px-3 py-2 text-sm outline-none transition"
                               style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                               onfocus="this.style.borderColor='var(--admin-accent)'"
                               onblur="this.style.borderColor='var(--admin-border)'"
                               placeholder="مثبت برای افزایش، منفی برای کاهش">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text-dim);">علت تعدیل</label>
                        <textarea name="description" required rows="3"
                                  class="w-full rounded-lg px-3 py-2 text-sm outline-none transition font-inherit"
                                  style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text); font-family:inherit;"
                                  onfocus="this.style.borderColor='var(--admin-accent)'"
                                  onblur="this.style.borderColor='var(--admin-border)'"
                                  placeholder="دلیل این تغییر را بنویسید..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-4" style="border-top:1px solid var(--admin-border);">
                    <button type="button" onclick="document.getElementById('adjustmentModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg text-sm"
                            style="background:var(--admin-accent-light); color:var(--admin-text-dim);">انصراف</button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">ثبت تغییرات</button>
                </div>
            </form>
        </div>
    </div>
@endsection
