@extends('layouts.specialist')

@section('title', 'جزئیات نوبت رزرو شده')

@php
    $statusBadgeMap = [
        'pending'         => ['label' => 'در انتظار تایید', 'class' => 'bg-amber-400/10 text-amber-300'],
        'confirmed'       => ['label' => 'تایید شده',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'completed'       => ['label' => 'انجام شده',        'class' => 'bg-[var(--specialist-plum-mid)]/15 text-[var(--specialist-plum-light)]'],
        'cancelled'       => ['label' => 'لغو شده',          'class' => 'bg-red-500/10 text-red-300'],
        'pending_payment' => ['label' => 'در انتظار پرداخت', 'class' => 'bg-orange-400/10 text-orange-300'],
    ];

    $paymentBadgeMap = [
        'paid'            => ['label' => 'پرداخت شده',      'class' => 'text-emerald-300'],
        'pending_payment' => ['label' => 'در انتظار پرداخت', 'class' => 'text-amber-300'],
        'unpaid'          => ['label' => 'پرداخت نشده',      'class' => 'text-red-300'],
    ];

    $status = trim($booking->status);
    $statusInfo = $statusBadgeMap[$status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
    $paymentInfo = $paymentBadgeMap[$booking->payment_status] ?? ['label' => $booking->payment_status, 'class' => 'text-[var(--specialist-text-dim)]'];
    $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
    $canShowButtons = !in_array($status, ['completed', 'cancelled', 'pending_payment']);
@endphp

@section('content')
    <div class="fade-in max-w-4xl mx-auto space-y-6">

        <a href="{{ route('specialist.bookings') }}" class="inline-flex items-center text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] transition-colors">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            بازگشت به لیست
        </a>

        <div class="specialist-card overflow-hidden">
            <div class="px-6 py-4 border-b flex justify-between items-center" style="border-color: var(--specialist-border);">
                <div>
                    <span class="text-xs text-[var(--specialist-plum-muted)] persian-number">شماره پیگیری: #{{ $booking->id }}</span>
                    <h1 class="text-xl font-bold text-[var(--specialist-text)] mt-1 font-serif-fa">{{ $booking->service?->name ?? '—' }}</h1>
                </div>
                <span class="px-4 py-2 rounded-lg font-bold text-sm {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa border-b pb-2" style="border-color: var(--specialist-border);">اطلاعات مشتری</h3>
                        <p class="text-[var(--specialist-text-dim)]"><span class="text-[var(--specialist-plum-muted)]">نام مشتری:</span> {{ $booking->user->name }}</p>
                        <p class="text-[var(--specialist-text-dim)]"><span class="text-[var(--specialist-plum-muted)]">شماره تماس:</span> <span dir="ltr">{{ $booking->user->phone }}</span></p>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa border-b pb-2" style="border-color: var(--specialist-border);">زمان و وضعیت مالی</h3>
                        <p class="text-[var(--specialist-text-dim)] persian-number"><span class="text-[var(--specialist-plum-muted)]">تاریخ نوبت:</span> {{ verta($booking->booking_time)->format('l، d F Y') }}</p>
                        <p class="text-[var(--specialist-text-dim)] persian-number" dir="ltr"><span class="text-[var(--specialist-plum-muted)]" dir="rtl">ساعت:</span> {{ $booking->booking_time->format('H:i') }}</p>
                        <p class="text-[var(--specialist-text-dim)]">
                            <span class="text-[var(--specialist-plum-muted)]">وضعیت پرداخت:</span>
                            <span class="font-bold {{ $paymentInfo['class'] }}">{{ $paymentInfo['label'] }}</span>
                        </p>
                    </div>
                </div>

                @if($canShowButtons)
                    <div class="mt-8 pt-6 border-t flex flex-wrap gap-4 justify-end" style="border-color: var(--specialist-border);">
                        @if($status == 'pending')
                            <form action="{{ route('specialist.bookings.complete', $booking->id) }}" method="POST" class="inline">
                                @csrf @method('PUT')
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-lg font-bold transition">
                                    پذیرش نوبت
                                </button>
                            </form>
                            <button onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                                    class="bg-red-600/90 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-bold transition">
                                لغو این نوبت
                            </button>
                        @endif

                        @if($status == 'confirmed')
                            <form action="{{ route('specialist.bookings.mark-completed', $booking->id) }}" method="POST" class="inline">
                                @csrf @method('PUT')
                                <button type="submit" class="specialist-cta px-8 py-3 rounded-lg font-bold transition-opacity hover:opacity-90">
                                    انجام شده
                                </button>
                            </form>

                            <button onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                                    class="bg-red-600/90 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-bold transition">
                                لغو این نوبت
                            </button>
                        @endif
                    </div>
                @endif

                @if($status == 'completed')
                    <div class="mt-8 pt-6 border-t" style="border-color: var(--specialist-border);">
                        <div class="rounded-lg p-4 text-center" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                            <p class="font-bold text-[var(--specialist-plum-light)]">این نوبت با موفقیت انجام شده است</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div id="cancelModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="specialist-card p-6 max-w-md w-full border" style="border-color: var(--specialist-border);">
            <h3 class="text-xl font-bold text-red-300 mb-2 font-serif-fa">لغو نوبت</h3>
            <p class="text-[var(--specialist-text-dim)] mb-4">آیا از لغو نوبت "{{ $booking->service?->name ?? '—' }}" اطمینان دارید؟</p>
            <form action="{{ route('specialist.bookings.cancel', $booking->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-5">
                    <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">دلیل لغو (برای اطلاع به مشتری):</label>
                    <textarea name="cancel_reason" required rows="3"
                              class="w-full rounded-lg p-3 text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                              style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                              placeholder="مثلاً: تداخل در برنامه کاری..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-500 transition">تایید لغو نوبت</button>
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="flex-1 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 transition" style="border: 1px solid var(--specialist-border);">انصراف</button>
                </div>
            </form>
        </div>
    </div>
@endsection
