@extends('layouts.admin')

@section('title', 'بررسی درخواست تسویه')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">درخواست تسویه حساب</h1>
                    <p class="text-sm text-gray-500 mt-1">کد پیگیری: <span class="font-mono font-bold">{{ $withdrawalRequest->reference_code }}</span></p>
                </div>
                <div class="text-left">
                <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-{{ $withdrawalRequest->status_badge_color }}-100 text-{{ $withdrawalRequest->status_badge_color }}-800">
                    {{ $withdrawalRequest->status_text }}
                </span>
                    <p class="text-xs text-gray-400 mt-2 persian-number">{{ verta($withdrawalRequest->created_at)->format('Y/m/d H:i') }}</p>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">جزئیات مالی</h3>
                    <div class="bg-blue-50 p-4 rounded-xl space-y-3">
                        <div class="flex justify-between text-sm text-blue-800">
                            <span>مبلغ درخواستی:</span>
                            <span class="persian-number">{{ number_format($withdrawalRequest->amount) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-red-600">
                            <span>کارمزد سیستم:</span>
                            <span class="persian-number">{{ number_format($withdrawalRequest->fee) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-black text-blue-900 border-t border-blue-200 pt-2">
                            <span>خالص واریزی:</span>
                            <span class="persian-number">{{ number_format($withdrawalRequest->net_amount) }} <small class="text-[10px]">تومان</small></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">حساب مقصد (شبا)</h3>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600">صاحب حساب: <span class="font-bold text-gray-900">{{ $withdrawalRequest->account_holder_name }}</span></p>
                        <div class="bg-gray-100 p-3 rounded-lg border border-gray-200">
                            <span class="block text-[10px] text-gray-400 mb-1">IR - شماره شبا:</span>
                            <span class="font-mono text-sm tracking-widest text-gray-800 break-all dir-ltr block">{{ $withdrawalRequest->formatted_iban }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">اطلاعات متخصص</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold">
                            {{ mb_substr($withdrawalRequest->specialist->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ $withdrawalRequest->specialist->name }}</p>
                            <p class="text-xs text-gray-500 persian-number">{{ $withdrawalRequest->specialist->phone }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(in_array($withdrawalRequest->status, ['pending', 'processing']))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border-2 border-orange-100 overflow-hidden">
                    <div class="bg-orange-50 px-6 py-4 flex items-center gap-3">
                        <div class="p-2 bg-orange-500 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <h4 class="font-bold text-orange-900">پرداخت خودکار (تسویه زرین‌پال)</h4>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                            با کلیک بر روی دکمه زیر، مبلغ به صورت سیستمی از پنل زرین‌پال شما کسر و به شبا متخصص واریز می‌گردد.
                        </p>
                        <form action="{{ route('admin.wallet.withdrawals.auto-payout', $withdrawalRequest) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-orange-200 transition-all flex justify-center items-center gap-2">
                                <span>اجرای تسویه آنی</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 flex items-center gap-3 border-b border-gray-100">
                        <div class="p-2 bg-gray-800 rounded-lg text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h4 class="font-bold text-gray-800">ثبت دستی فیش واریزی</h4>
                    </div>
                    <div class="p-6 text-right">
                        <form action="{{ route('admin.wallet.withdrawals.approve', $withdrawalRequest) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="text-xs font-bold text-gray-500 block mb-2 mr-1">شماره پیگیری / کد ارجاع بانکی</label>
                                <input type="text" name="payment_reference" required class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 bg-gray-50" placeholder="مثلاً: 12345678">
                            </div>
                            <button type="submit" class="w-full bg-gray-800 hover:bg-black text-white font-bold py-3 rounded-xl transition-all">
                                تایید و بستن درخواست
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-red-50 p-6 rounded-2xl border border-red-100 shadow-sm">
                <h4 class="text-red-800 font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    رد درخواست
                </h4>
                <form action="{{ route('admin.wallet.withdrawals.reject', $withdrawalRequest) }}" method="POST" class="flex flex-col md:flex-row gap-4">
                    @csrf
                    <input type="text" name="rejection_reason" required class="flex-1 rounded-xl border-red-200 focus:ring-red-500 focus:border-red-500" placeholder="دلیل رد درخواست را برای متخصص بنویسید...">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-red-100">
                        رد و بازگشت وجه به کیف پول
                    </button>
                </form>
            </div>
        @else
            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 flex items-center gap-4">
                <div class="flex-1">
                    <h4 class="font-bold text-gray-800">این درخواست قبلاً پردازش شده است</h4>
                    @if($withdrawalRequest->status === 'completed')
                        <p class="text-sm text-green-700 mt-1">تایید شده توسط ادمین در تاریخ {{ verta($withdrawalRequest->processed_at)->format('Y/m/d') }}</p>
                        <div class="mt-4 bg-white p-3 rounded-lg border inline-block">
                            <span class="text-xs text-gray-400">کد رهگیری بانکی:</span>
                            <span class="font-mono font-bold">{{ $withdrawalRequest->payment_details['payment_reference'] ?? '---' }}</span>
                        </div>
                    @else
                        <p class="text-sm text-red-700 mt-1">رد شده به دلیل: {{ $withdrawalRequest->rejection_reason }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
