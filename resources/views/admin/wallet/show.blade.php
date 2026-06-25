@extends('layouts.admin')

@section('title', 'جزئیات کیف پول ' . $wallet->specialist?->name ?? '—')

@section('content')
    <div class="container-fluid px-4 py-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">مدیریت کیف پول</h1>
                <p class="text-gray-500 mt-1">متخصص: {{ $wallet->specialist?->name ?? '—' }} ({{ $wallet->specialist?->phone ?? '—' }})</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.wallet.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    بازگشت به لیست
                </a>
                <button onclick="document.getElementById('adjustmentModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg text-sm font-medium hover:bg-pink-700 transition-colors">
                    تعدیل دستی موجودی
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">موجودی فعلی</p>
                <p class="text-2xl font-bold text-gray-900 persian-number">{{ number_format($wallet->balance) }} <span class="text-xs font-normal text-gray-500">تومان</span></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">در انتظار تسویه</p>
                <p class="text-2xl font-bold text-blue-600 persian-number">{{ number_format($wallet->pending_amount) }} <span class="text-xs font-normal text-gray-500">تومان</span></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">کل درآمد (ناخالص)</p>
                <p class="text-2xl font-bold text-green-600 persian-number">{{ number_format($wallet->total_earned) }} <span class="text-xs font-normal text-gray-500">تومان</span></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">کل برداشت شده</p>
                <p class="text-2xl font-bold text-orange-600 persian-number">{{ number_format($wallet->total_withdrawn) }} <span class="text-xs font-normal text-gray-500">تومان</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">اطلاعات حساب بانکی</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500">شماره شبا</p>
                            <p class="text-sm font-mono font-bold text-gray-800 dir-ltr text-right">{{ $wallet->formatted_iban ?? 'ثبت نشده' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">صاحب حساب</p>
                            <p class="text-sm font-medium text-gray-800">{{ $wallet->account_holder_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">نام بانک</p>
                            <p class="text-sm font-medium text-gray-800">{{ $wallet->bank_name ?? '-' }}</p>
                        </div>
                        <div class="pt-2">
                            @if($wallet->iban_verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                تایید شده
                            </span>
                            @else
                                <form action="{{ route('admin.wallet.verify-iban', $wallet) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full py-2 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold hover:bg-blue-100 transition-colors">
                                        تایید دستی شماره شبا
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">تراکنش‌های اخیر</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-4 font-medium">تاریخ</th>
                                <th class="px-6 py-4 font-medium">نوع</th>
                                <th class="px-6 py-4 font-medium">مبلغ</th>
                                <th class="px-6 py-4 font-medium">توضیحات</th>
                                <th class="px-6 py-4 font-medium">موجودی بعد</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @forelse($wallet->transactions()->latest()->paginate(10) as $transaction)
                                <tr class="hover:bg-gray-50 transition-colors text-sm">
                                    <td class="px-6 py-4 whitespace-nowrap persian-number text-gray-600">
                                        {{ jdate($transaction->created_at)->format('Y/m/d H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-md text-xs font-medium
                                            @if($transaction->type == 'income') bg-green-50 text-green-700
                                            @elseif($transaction->type == 'withdrawal') bg-red-50 text-red-700
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ $transaction->type_text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold persian-number {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($transaction->amount) }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-400 persian-number">
                                        {{ number_format($transaction->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">تراکنشی یافت نشد.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="adjustmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.wallet.adjust', $wallet) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4" id="modal-title">تعدیل دستی موجودی</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">مبلغ (تومان)</label>
                                <input type="number" name="amount" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500" placeholder="مثلاً 50000 برای افزایش یا -50000 برای کاهش">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">علت تعدیل</label>
                                <textarea name="description" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500" placeholder="دلیل این تغییر را بنویسید..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-pink-600 text-base font-medium text-white hover:bg-pink-700 focus:outline-none sm:w-auto sm:text-sm">ثبت تغییرات</button>
                        <button type="button" onclick="document.getElementById('adjustmentModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
