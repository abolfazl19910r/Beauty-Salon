@extends('layouts.specialist')

@section('title', 'کیف پول')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 rounded-lg p-3">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-green-100 mb-1">موجودی قابل برداشت</p>
                    <p class="text-3xl font-bold persian-number">{{ number_format($wallet->balance) }}</p>
                    <p class="text-xs text-green-100 mt-1">تومان</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 rounded-lg p-3">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-yellow-100 mb-1">در انتظار تسویه</p>
                    <p class="text-3xl font-bold persian-number">{{ number_format($wallet->pending_amount) }}</p>
                    <p class="text-xs text-yellow-100 mt-1">تومان ({{ $settings->settlement_delay_days }} روز تاخیر)</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white/20 rounded-lg p-3">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-blue-100 mb-1">کل درآمد</p>
                    <p class="text-3xl font-bold persian-number">{{ number_format($wallet->total_earned) }}</p>
                    <p class="text-xs text-blue-100 mt-1">تومان</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">درآمد این ماه</p>
                        <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format($currentMonthIncome) }}</p>
                        <p class="text-xs text-gray-500 mt-1">تومان</p>
                    </div>
                    <div class="bg-green-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">برداشت این ماه</p>
                        <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format(abs($currentMonthWithdrawals)) }}</p>
                        <p class="text-xs text-gray-500 mt-1">تومان</p>
                    </div>
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('specialist.wallet.create-withdrawal') }}" class="bg-pink-500 hover:bg-pink-600 text-white rounded-xl shadow-md p-6 text-center transition-colors">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-lg font-bold">درخواست برداشت</p>
                <p class="text-sm text-pink-100 mt-1">برداشت وجه از کیف پول</p>
            </a>

            <a href="{{ route('specialist.wallet.transactions') }}" class="bg-blue-500 hover:bg-blue-600 text-white rounded-xl shadow-md p-6 text-center transition-colors">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-lg font-bold">تراکنش‌ها</p>
                <p class="text-sm text-blue-100 mt-1">مشاهده تاریخچه تراکنش‌ها</p>
            </a>

            <a href="{{ route('specialist.wallet.edit-iban') }}" class="bg-gray-500 hover:bg-gray-600 text-white rounded-xl shadow-md p-6 text-center transition-colors">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <p class="text-lg font-bold">تنظیم شماره شبا</p>
                <p class="text-sm text-gray-100 mt-1">{{ $wallet->iban ? 'ویرایش شماره شبا' : 'ثبت شماره شبا' }}</p>
            </a>
        </div>

        @if($wallet->iban)
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    اطلاعات حساب بانکی
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">شماره شبا</p>
                        <p class="text-base font-semibold text-gray-800 persian-number font-mono">{{ $wallet->iban }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">نام صاحب حساب</p>
                        <p class="text-base font-semibold text-gray-800">{{ $wallet->account_holder_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">نام بانک</p>
                        <p class="text-base font-semibold text-gray-800">{{ $wallet->bank_name ?? 'ثبت نشده' }}</p>
                    </div>
                </div>
                @if($wallet->iban_verified)
                    <div class="mt-3 flex items-center text-green-600">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">شماره شبا تایید شده است</span>
                    </div>
                @else
                    <div class="mt-3 flex items-center text-yellow-600">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm">شماره شبا در انتظار تایید است</span>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-yellow-50 border-r-4 border-yellow-400 p-6 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 ml-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-yellow-800 font-semibold mb-1">شماره شبا ثبت نشده است</h4>
                        <p class="text-yellow-700 text-sm mb-3">برای برداشت وجه، لطفاً ابتدا شماره شبا خود را ثبت کنید.</p>
                        <a href="{{ route('specialist.wallet.edit-iban') }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-medium transition-colors">
                            ثبت شماره شبا
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">آخرین تراکنش‌ها</h3>
                <a href="{{ route('specialist.wallet.transactions') }}" class="text-pink-600 hover:text-pink-700 text-sm font-medium">
                    مشاهده همه
                    <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            </div>

            @if($recentTransactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">نوع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">توضیحات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $transaction->type_badge_color }}-100 text-{{ $transaction->type_badge_color }}-800">
                                {{ $transaction->type_text }}
                            </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                            <span class="persian-number font-semibold {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->formatted_amount }}
                            </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $transaction->description }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 persian-number">
                                    {{ verta($transaction->created_at)->format('Y/m/d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>تراکنشی ثبت نشده است</p>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">درخواست‌های برداشت</h3>

            @if($withdrawalRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">کد پیگیری</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">کارمزد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">مبلغ خالص</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">روش</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">وضعیت</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($withdrawalRequests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono persian-number">
                                    {{ $request->reference_code }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm persian-number font-semibold">
                                    {{ number_format($request->amount) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm persian-number text-red-600">
                                    {{ number_format($request->fee) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm persian-number font-semibold text-green-600">
                                    {{ number_format($request->net_amount) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    {{ $request->method_text }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-{{ $request->status_badge_color }}-100 text-{{ $request->status_badge_color }}-800">
                                {{ $request->status_text }}
                            </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 persian-number">
                                    {{ verta($request->created_at)->format('Y/m/d') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($request->canBeCancelled())
                                        <form action="{{ route('specialist.wallet.cancel-withdrawal', $request) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از لغو این درخواست اطمینان دارید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">لغو</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $withdrawalRequests->links() }}
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>درخواست برداشتی ثبت نشده است</p>
                </div>
            @endif
        </div>
    </div>
@endsection
