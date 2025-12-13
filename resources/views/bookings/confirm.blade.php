@extends('layouts.app')

@section('title', 'تأیید رزرو')

@section('content')
    <div class="max-w-3xl mx-auto fade-in">
        <h1 class="text-2xl font-bold mb-6 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">تأیید رزرو نوبت</h1>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="mb-8">
                <h2 class="text-lg font-bold mb-4">اطلاعات نوبت</h2>
                <div class="bg-gray-50 p-5 rounded-lg space-y-4">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">خدمت:</span>
                        <span class="font-medium">{{ $service->name }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">متخصص:</span>
                        <span class="font-medium">{{ $specialist->name }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">تاریخ:</span>
                        <span class="font-medium persian-number">{{ verta($bookingTime)->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">ساعت:</span>
                        <span class="font-medium">{{ verta($bookingTime)->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">مدت زمان:</span>
                        <span class="font-medium persian-number">{{ $service->duration }} دقیقه</span>
                    </div>
                    <div class="flex justify-between font-bold pt-2 text-lg">
                        <span>مبلغ پیش پرداخت:</span>
                        <span class="text-pink-600 persian-number" id="final-price">{{ number_format($prepaymentAmount) }} تومان</span>
                    </div>
                </div>
            </div>

            <div class="mb-4 bg-blue-50 p-4 rounded-lg">
                <p class="text-blue-700 text-sm">با تأیید نهایی و پرداخت، نوبت شما ثبت خواهد شد. لطفاً اطلاعات بالا را با دقت بررسی کنید.</p>
            </div>

            <div class="border-t pt-4 mb-6">
                <h3 class="font-bold mb-2">کد تخفیف</h3>
                <div class="flex gap-2">
                    <input type="text" id="discount-code" name="discount_code" placeholder="کد تخفیف خود را وارد کنید"
                           class="flex-1 border rounded-lg px-4 py-2 focus:border-pink-500 focus:ring focus:ring-pink-200">
                    <button type="button" id="apply-discount"
                            class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                        اعمال
                    </button>
                </div>
                <div id="discount-message" class="mt-2 text-sm hidden"></div>
            </div>

            <form action="{{ route('bookings.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
                <input type="hidden" name="booking_time" value="{{ $bookingTime }}">
                <input type="hidden" name="discount_code" id="hidden-discount-code" value="">

                <div class="flex gap-4">
                    <a href="{{ route('bookings.create') }}"
                       class="flex-1 bg-gray-200 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-300 transition-colors text-center">
                        بازگشت و ویرایش
                    </a>
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity">
                        تأیید و رفتن به درگاه پرداخت
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const applyDiscountBtn = document.getElementById('apply-discount');
            const discountCodeInput = document.getElementById('discount-code');
            const hiddenDiscountCode = document.getElementById('hidden-discount-code');
            const discountMessage = document.getElementById('discount-message');
            const finalPriceEl = document.getElementById('final-price');

            applyDiscountBtn.addEventListener('click', async function() {
                const code = discountCodeInput.value.trim();
                if (!code) {
                    discountMessage.innerHTML = 'لطفا کد تخفیف را وارد کنید';
                    discountMessage.className = 'mt-2 text-sm text-red-600';
                    discountMessage.classList.remove('hidden');
                    return;
                }

                try {
                    discountMessage.innerHTML = 'در حال بررسی کد تخفیف...';
                    discountMessage.className = 'mt-2 text-sm text-blue-600';
                    discountMessage.classList.remove('hidden');

                    const response = await fetch('/api/check-discount', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            code: code,
                            service_id: '{{ $service->id }}'
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.valid) {
                        discountMessage.innerHTML = `✓ کد تخفیف معتبر است: ${result.discount_amount.toLocaleString()} تومان تخفیف`;
                        discountMessage.className = 'mt-2 text-sm text-green-600';
                        hiddenDiscountCode.value = code;

                        if (result.final_price !== undefined) {
                            finalPriceEl.innerHTML = `${result.final_price.toLocaleString()} تومان`;
                        }
                    } else {
                        discountMessage.innerHTML = '✗ ' + (result.message || 'کد تخفیف نامعتبر است');
                        discountMessage.className = 'mt-2 text-sm text-red-600';
                        hiddenDiscountCode.value = '';
                        finalPriceEl.innerHTML = '{{ number_format($prepaymentAmount) }} تومان';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    discountMessage.innerHTML = 'خطا در بررسی کد تخفیف';
                    discountMessage.className = 'mt-2 text-sm text-red-600';
                }
            });
        });
    </script>
@endpush
