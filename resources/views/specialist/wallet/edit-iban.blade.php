@extends('layouts.specialist')

@section('title', 'تنظیم شماره شبا')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">تنظیم اطلاعات حساب بانکی</h2>

            <form action="{{ route('specialist.wallet.update-iban') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label for="iban" class="block text-sm font-medium text-gray-700 mb-2">
                        شماره شبا <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500 font-mono">IR</span>
                        <input
                            type="text"
                            name="iban"
                            id="iban"
                            class="w-full pr-12 pl-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent font-mono persian-number"
                            value="{{ old('iban', $wallet->iban ? substr($wallet->iban, 2) : '') }}"
                            placeholder="000000000000000000000000"
                            maxlength="24"
                            required
                        >
                    </div>
                    @error('iban')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">شماره شبا 24 رقمی خود را بدون IR وارد کنید</p>
                </div>

                <div class="mb-6">
                    <label for="account_holder_name" class="block text-sm font-medium text-gray-700 mb-2">
                        نام صاحب حساب <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="account_holder_name"
                        id="account_holder_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        value="{{ old('account_holder_name', $wallet->account_holder_name) }}"
                        required
                    >
                    @error('account_holder_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">نام باید دقیقاً مطابق با نام روی کارت بانکی باشد</p>
                </div>

                <div class="mb-6">
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                        نام بانک <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="bank_name"
                        id="bank_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        required
                    >
                        <option value="">انتخاب کنید</option>
                        <option value="ملی" {{ old('bank_name', $wallet->bank_name) == 'ملی' ? 'selected' : '' }}>بانک ملی ایران</option>
                        <option value="ملت" {{ old('bank_name', $wallet->bank_name) == 'ملت' ? 'selected' : '' }}>بانک ملت</option>
                        <option value="تجارت" {{ old('bank_name', $wallet->bank_name) == 'تجارت' ? 'selected' : '' }}>بانک تجارت</option>
                        <option value="صادرات" {{ old('bank_name', $wallet->bank_name) == 'صادرات' ? 'selected' : '' }}>بانک صادرات ایران</option>
                        <option value="سپه" {{ old('bank_name', $wallet->bank_name) == 'سپه' ? 'selected' : '' }}>بانک سپه</option>
                        <option value="رفاه" {{ old('bank_name', $wallet->bank_name) == 'رفاه' ? 'selected' : '' }}>بانک رفاه کارگران</option>
                        <option value="پاسارگاد" {{ old('bank_name', $wallet->bank_name) == 'پاسارگاد' ? 'selected' : '' }}>بانک پاسارگاد</option>
                        <option value="پارسیان" {{ old('bank_name', $wallet->bank_name) == 'پارسیان' ? 'selected' : '' }}>بانک پارسیان</option>
                        <option value="سامان" {{ old('bank_name', $wallet->bank_name) == 'سامان' ? 'selected' : '' }}>بانک سامان</option>
                        <option value="سرمایه" {{ old('bank_name', $wallet->bank_name) == 'سرمایه' ? 'selected' : '' }}>بانک سرمایه</option>
                        <option value="سینا" {{ old('bank_name', $wallet->bank_name) == 'سینا' ? 'selected' : '' }}>بانک سینا</option>
                        <option value="کشاورزی" {{ old('bank_name', $wallet->bank_name) == 'کشاورزی' ? 'selected' : '' }}>بانک کشاورزی</option>
                        <option value="مسکن" {{ old('bank_name', $wallet->bank_name) == 'مسکن' ? 'selected' : '' }}>بانک مسکن</option>
                        <option value="پست بانک" {{ old('bank_name', $wallet->bank_name) == 'پست بانک' ? 'selected' : '' }}>پست بانک ایران</option>
                        <option value="توسعه تعاون" {{ old('bank_name', $wallet->bank_name) == 'توسعه تعاون' ? 'selected' : '' }}>بانک توسعه تعاون</option>
                        <option value="اقتصاد نوین" {{ old('bank_name', $wallet->bank_name) == 'اقتصاد نوین' ? 'selected' : '' }}>بانک اقتصاد نوین</option>
                        <option value="دی" {{ old('bank_name', $wallet->bank_name) == 'دی' ? 'selected' : '' }}>بانک دی</option>
                        <option value="شهر" {{ old('bank_name', $wallet->bank_name) == 'شهر' ? 'selected' : '' }}>بانک شهر</option>
                        <option value="آینده" {{ old('bank_name', $wallet->bank_name) == 'آینده' ? 'selected' : '' }}>بانک آینده</option>
                        <option value="انصار" {{ old('bank_name', $wallet->bank_name) == 'انصار' ? 'selected' : '' }}>بانک انصار</option>
                        <option value="گردشگری" {{ old('bank_name', $wallet->bank_name) == 'گردشگری' ? 'selected' : '' }}>بانک گردشگری</option>
                        <option value="حکمت ایرانیان" {{ old('bank_name', $wallet->bank_name) == 'حکمت ایرانیان' ? 'selected' : '' }}>بانک حکمت ایرانیان</option>
                        <option value="ایران زمین" {{ old('bank_name', $wallet->bank_name) == 'ایران زمین' ? 'selected' : '' }}>بانک ایران زمین</option>
                        <option value="قرض الحسنه مهر ایران" {{ old('bank_name', $wallet->bank_name) == 'قرض الحسنه مهر ایران' ? 'selected' : '' }}>بانک قرض الحسنه مهر ایران</option>
                        <option value="رسالت" {{ old('bank_name', $wallet->bank_name) == 'رسالت' ? 'selected' : '' }}>بانک رسالت</option>
                        <option value="کارآفرین" {{ old('bank_name', $wallet->bank_name) == 'کارآفرین' ? 'selected' : '' }}>بانک کارآفرین</option>
                        <option value="توسعه صادرات" {{ old('bank_name', $wallet->bank_name) == 'توسعه صادرات' ? 'selected' : '' }}>بانک توسعه صادرات ایران</option>
                        <option value="صنعت و معدن" {{ old('bank_name', $wallet->bank_name) == 'صنعت و معدن' ? 'selected' : '' }}>بانک صنعت و معدن</option>
                        <option value="تات" {{ old('bank_name', $wallet->bank_name) == 'تات' ? 'selected' : '' }}>بانک تات</option>
                        <option value="مهر اقتصاد" {{ old('bank_name', $wallet->bank_name) == 'مهر اقتصاد' ? 'selected' : '' }}>بانک مهر اقتصاد</option>
                        <option value="خاورمیانه" {{ old('bank_name', $wallet->bank_name) == 'خاورمیانه' ? 'selected' : '' }}>بانک خاورمیانه</option>
                    </select>
                    @error('bank_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6 bg-yellow-50 border-r-4 border-yellow-400 p-4 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 ml-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm text-yellow-800">
                            <p class="font-semibold mb-1">نکات مهم:</p>
                            <ul class="list-disc list-inside space-y-1 mr-2 text-xs">
                                <li>اطلاعات باید دقیقاً مطابق با اطلاعات حساب بانکی شما باشد</li>
                                <li>شماره شبا باید متعلق به شما باشد</li>
                                <li>پس از ثبت، اطلاعات توسط تیم پشتیبانی بررسی و تایید می‌شود</li>
                                <li>تا زمان تایید، امکان برداشت وجه وجود ندارد</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="flex-1 bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        ذخیره اطلاعات
                    </button>
                    <a
                        href="{{ route('specialist.wallet.index') }}"
                        class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        انصراف
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-6 bg-blue-50 rounded-xl p-6">
            <h3 class="text-lg font-bold text-blue-900 mb-3">راهنمای یافتن شماره شبا</h3>
            <div class="space-y-2 text-sm text-blue-800">
                <p>• از طریق اپلیکیشن موبایل بانک خود</p>
                <p>• از طریق سیستم بانکداری اینترنتی</p>
                <p>• با مراجعه به شعبه بانک</p>
                <p>• با تماس با مرکز تماس بانک</p>
                <p>• از طریق دستگاه خودپرداز (ATM)</p>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ibanInput = document.getElementById('iban');

                ibanInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');

                    if (value.length > 24) {
                        value = value.substring(0, 24);
                    }

                    e.target.value = value;
                });
            });
        </script>
    @endpush
@endsection
