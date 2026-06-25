@extends('layouts.admin')

@section('title', 'جزئیات نوبت #' . $booking->id)

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                جزئیات نوبت
                <span class="persian-number text-base font-normal" style="color:var(--admin-text-dim);">#{{ $booking->id }}</span>
            </h1>
            <div class="flex items-center gap-2">
                @permission('edit-bookings')
                <a href="{{ route('admin.bookings.edit', $booking) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                   style="background:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-accent-hover)'"
                   onmouseout="this.style.background='var(--admin-accent)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    ویرایش
                </a>
                @endpermission
                <a href="{{ route('admin.bookings.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" transform="scale(-1,1) translate(-24,0)"/>
                    </svg>
                    بازگشت
                </a>
            </div>
        </div>

        @php
            $statusMap = [
                'pending'   => ['label'=>'در انتظار تایید', 'bg'=>'#FFFBEB', 'color'=>'#92400E', 'border'=>'#FCD34D'],
                'confirmed' => ['label'=>'تایید شده',       'bg'=>'#F0FDF4', 'color'=>'#166534', 'border'=>'#86EFAC'],
                'completed' => ['label'=>'انجام شده',       'bg'=>'#EFF6FF', 'color'=>'#1D4ED8', 'border'=>'#93C5FD'],
                'cancelled' => ['label'=>'لغو شده',         'bg'=>'#FEF2F2', 'color'=>'#991B1B', 'border'=>'#FCA5A5'],
            ];
            $s = $statusMap[$booking->status] ?? ['label'=>$booking->status,'bg'=>'#F1F5F9','color'=>'#475569','border'=>'#CBD5E1'];
        @endphp
        <div class="rounded-xl px-5 py-3 mb-5 flex items-center gap-3 border"
             style="background:{{ $s['bg'] }}; border-color:{{ $s['border'] }};">
            <span class="text-sm font-semibold" style="color:{{ $s['color'] }};">وضعیت نوبت:</span>
            <span class="text-sm font-bold" style="color:{{ $s['color'] }};">{{ $s['label'] }}</span>
            @if($booking->payment_status == 'paid')
                <span class="mr-auto inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium"
                      style="background:#F0FDF4; color:#166534;">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                پرداخت شده
            </span>
            @else
                <span class="mr-auto inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium"
                      style="background:#FEF2F2; color:#991B1B;">
                پرداخت نشده
            </span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <h2 class="text-sm font-bold flex items-center gap-2 mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">
                    <svg class="w-4 h-4" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    اطلاعات مشتری
                </h2>
                @if($booking->user)
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold flex-shrink-0"
                             style="background:var(--admin-accent); color:#fff;">
                            {{ mb_substr($booking->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold" style="color:var(--admin-text);">{{ $booking->user->name }}</p>
                            <p class="text-sm" dir="ltr" style="color:var(--admin-text-dim);">{{ $booking->user->phone }}</p>
                        </div>
                        <a href="{{ route('admin.users.show', $booking->user) }}"
                           class="mr-auto text-xs px-2.5 py-1 rounded-lg transition-colors"
                           style="color:var(--admin-accent); background:var(--admin-accent-light);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">
                            پروفایل
                        </a>
                    </div>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">ایمیل</span>
                            <span style="color:var(--admin-text);">{{ $booking->user->email ?? 'ثبت نشده' }}</span>
                        </div>
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">تاریخ عضویت</span>
                            <span class="persian-number" style="color:var(--admin-text);">{{ verta($booking->user->created_at)->format('Y/m/d') }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span style="color:var(--admin-text-dim);">تعداد نوبت‌ها</span>
                            <span class="persian-number font-bold" style="color:var(--admin-accent);">{{ $booking->user->bookings()->count() }}</span>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-center gap-2 h-24 rounded-lg text-sm"
                         style="background:#FEF2F2; color:#991B1B;">
                        اطلاعات کاربر در دسترس نیست
                    </div>
                @endif
            </div>

            <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <h2 class="text-sm font-bold flex items-center gap-2 mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">
                    <svg class="w-4 h-4" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    جزئیات نوبت
                </h2>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                        <span style="color:var(--admin-text-dim);">تاریخ</span>
                        <span class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                        <span style="color:var(--admin-text-dim);">ساعت</span>
                        <span class="persian-number font-medium" style="color:var(--admin-text);">{{ verta($booking->booking_time)->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                        <span style="color:var(--admin-text-dim);">خدمت</span>
                        <span class="font-medium" style="color:var(--admin-text);">{{ $booking->service?->name ?? '—' ?? '—' }}</span>
                    </div>
                    @if($booking->service)
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">قیمت خدمت</span>
                            <span class="persian-number font-medium" style="color:var(--admin-text);">{{ number_format($booking->service->price) }} تومان</span>
                        </div>
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">مدت زمان</span>
                            <span class="persian-number" style="color:var(--admin-text);">{{ $booking->service->duration ?? 60 }} دقیقه</span>
                        </div>
                    @endif
                    <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                        <span style="color:var(--admin-text-dim);">متخصص</span>
                        <span class="font-medium" style="color:var(--admin-text);">
                        @if($booking->specialist)
                                <a href="{{ route('admin.specialists.show', $booking->specialist) }}"
                                   style="color:var(--admin-accent);">{{ $booking->specialist?->name ?? '—' }}</a>
                            @else
                                <span style="color:var(--admin-text-light);">—</span>
                            @endif
                    </span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span style="color:var(--admin-text-dim);">تاریخ ثبت</span>
                        <span class="persian-number" style="color:var(--admin-text);">{{ verta($booking->created_at)->format('Y/m/d H:i') }}</span>
                    </div>
                    @if($booking->notes)
                        <div class="mt-3 p-3 rounded-lg text-sm" style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                            <p class="font-medium mb-1" style="color:var(--admin-text);">یادداشت:</p>
                            {{ $booking->notes }}
                        </div>
                    @endif
                </div>
            </div>

            @if($booking->payment_status == 'paid')
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold flex items-center gap-2 mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">
                        <svg class="w-4 h-4" style="color:#16A34A;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        اطلاعات مالی
                    </h2>
                    @php
                        $commissionRate = \App\Models\WalletSetting::first()->admin_commission_percentage ?? 10;
                        $salonShare = $booking->prepayment_amount * ($commissionRate / 100);
                        $specialistShare = $booking->prepayment_amount * (1 - $commissionRate / 100);
                    @endphp
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">مبلغ پرداختی</span>
                            <span class="persian-number font-bold" style="color:var(--admin-text);">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">سهم سالن ({{ $commissionRate }}%)</span>
                            <span class="persian-number font-medium" style="color:#16A34A;">{{ number_format($salonShare) }} تومان</span>
                        </div>
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">سهم متخصص</span>
                            <span class="persian-number font-medium" style="color:var(--admin-text);">{{ number_format($specialistShare) }} تومان</span>
                        </div>
                        <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">شماره پیگیری</span>
                            <span class="font-mono text-xs" style="color:var(--admin-text);">{{ $booking->payment_reference ?? 'ندارد' }}</span>
                        </div>
                        @if($booking->paid_at)
                            <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                                <span style="color:var(--admin-text-dim);">تاریخ پرداخت</span>
                                <span class="persian-number" style="color:var(--admin-text);">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</span>
                            </div>
                        @endif
                        @if($booking->discount_amount)
                            <div class="flex justify-between py-1" style="border-bottom:1px dashed var(--admin-border);">
                                <span style="color:var(--admin-text-dim);">تخفیف ({{ $booking->discount_code }})</span>
                                <span class="persian-number" style="color:#DC2626;">- {{ number_format($booking->discount_amount) }} تومان</span>
                            </div>
                        @endif
                        <div class="flex justify-between py-1">
                            <span style="color:var(--admin-text-dim);">نوع پرداخت</span>
                            <span style="color:var(--admin-text);">{{ $booking->payment_method ?? 'آنلاین' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <h2 class="text-sm font-bold flex items-center gap-2 mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">
                    <svg class="w-4 h-4" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    عملیات
                </h2>
                <div class="space-y-2">
                    @permission('approve-bookings')
                    @if($booking->status == 'pending')
                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium text-white transition-colors"
                                    style="background:#16A34A;"
                                    onmouseover="this.style.background='#15803D'"
                                    onmouseout="this.style.background='#16A34A'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                تایید نوبت
                            </button>
                        </form>
                    @endif
                    @endpermission

                    @permission('delete-bookings')
                    @if($booking->status != 'cancelled')
                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit"
                                    data-confirm-delete data-confirm-message="آیا از لغو نوبت #{{ $booking->id }} اطمینان دارید؟"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium text-white transition-colors"
                                    style="background:#DC2626;"
                                    onmouseover="this.style.background='#B91C1C'"
                                    onmouseout="this.style.background='#DC2626'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                لغو نوبت
                            </button>
                        </form>
                    @endif
                    @endpermission

                    @permission('edit-bookings')
                    <a href="{{ route('admin.bookings.edit', $booking) }}"
                       class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium transition-colors"
                       style="background:var(--admin-accent-light); color:var(--admin-accent);"
                       onmouseover="this.style.background='var(--admin-border)'"
                       onmouseout="this.style.background='var(--admin-accent-light)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        ویرایش نوبت
                    </a>
                    @endpermission
                </div>
            </div>

        </div>
    </div>
@endsection
