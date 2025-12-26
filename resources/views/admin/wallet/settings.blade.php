@extends('layouts.admin')

@section('title', 'تنظیمات مالی و کیف پول')

@section('content')
    <div class="max-w-4xl mx-auto">
        <form action="{{ route('admin.wallet.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">قوانین برداشت وجه</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">درصد کارمزد برداشت (شبا)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="withdrawal_fee_percentage" value="{{ $settings->withdrawal_fee_percentage }}" class="w-full rounded-lg border-gray-300 pl-8 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">%</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">تاخیر تسویه (روز)</label>
                            <input type="number" name="settlement_delay_days" value="{{ $settings->settlement_delay_days }}" class="w-full rounded-lg border-gray-300 persian-number">
                            <p class="text-xs text-gray-500 mt-1">تعداد روزهایی که درآمد پس از انجام خدمت، قابل برداشت می‌شود.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">حداقل مبلغ برداشت</label>
                            <div class="relative">
                                <input type="number" step="1000" name="minimum_withdrawal_amount" value="{{ $settings->minimum_withdrawal_amount }}" class="w-full rounded-lg border-gray-300 pl-12 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 text-xs">تومان</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">حداکثر مبلغ برداشت</label>
                            <div class="relative">
                                <input type="number" step="1000" name="maximum_withdrawal_amount" value="{{ $settings->maximum_withdrawal_amount }}" class="w-full rounded-lg border-gray-300 pl-12 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 text-xs">تومان</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">قوانین لغو نوبت</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">بازه زمانی جریمه لغو (ساعت)</label>
                            <input type="number" name="cancellation_before_hours" value="{{ $settings->cancellation_before_hours }}" class="w-full rounded-lg border-gray-300 persian-number">
                            <p class="text-xs text-gray-500 mt-1">اگر کاربر کمتر از این مقدار مانده به نوبت لغو کند، جریمه می‌شود.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">درصد جریمه کاربر</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="customer_cancellation_fee_percentage" value="{{ $settings->customer_cancellation_fee_percentage }}" class="w-full rounded-lg border-gray-300 pl-8 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">%</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">درصد جریمه متخصص</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="specialist_cancellation_penalty_percentage" value="{{ $settings->specialist_cancellation_penalty_percentage }}" class="w-full rounded-lg border-gray-300 pl-8 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">%</div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">اگر متخصص نوبت را لغو کند.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">تنظیمات برداشت فوری</h3>
                        <div class="flex items-center">
                            <input type="hidden" name="instant_withdrawal_enabled" value="0">
                            <input type="checkbox" name="instant_withdrawal_enabled" value="1" id="instant_toggle" {{ $settings->instant_withdrawal_enabled ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5">
                            <label for="instant_toggle" class="mr-2 text-sm text-gray-700">فعال‌سازی</label>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="{{ $settings->instant_withdrawal_enabled ? '' : 'opacity-50 pointer-events-none' }}" id="instant_settings">
                            <label class="block text-sm font-medium text-gray-700 mb-1">کارمزد ثابت برداشت فوری</label>
                            <div class="relative max-w-md">
                                <input type="number" step="1000" name="instant_withdrawal_fee" value="{{ $settings->instant_withdrawal_fee }}" class="w-full rounded-lg border-gray-300 pl-12 persian-number">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 text-xs">تومان</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition shadow-lg flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        ذخیره تنظیمات
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.getElementById('instant_toggle').addEventListener('change', function() {
                const settingsDiv = document.getElementById('instant_settings');
                if (this.checked) {
                    settingsDiv.classList.remove('opacity-50', 'pointer-events-none');
                } else {
                    settingsDiv.classList.add('opacity-50', 'pointer-events-none');
                }
            });
        </script>
    @endpush
@endsection
