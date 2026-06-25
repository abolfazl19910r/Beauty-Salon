@extends('layouts.admin')

@section('title', 'مدیریت کیف پول‌ها')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-blue-500">
                <p class="text-sm text-gray-500 mb-1">کل موجودی نزد متخصصین</p>
                <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format($totalBalance) }}</p>
                <p class="text-xs text-gray-400 mt-1">تومان</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-green-500">
                <p class="text-sm text-gray-500 mb-1">کل درآمد متخصصین</p>
                <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format($totalEarned) }}</p>
                <p class="text-xs text-gray-400 mt-1">تاکنون</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-red-500">
                <p class="text-sm text-gray-500 mb-1">کل برداشت‌ها</p>
                <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format($totalWithdrawn) }}</p>
                <p class="text-xs text-gray-400 mt-1">پرداخت شده</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-yellow-500">
                <p class="text-sm text-gray-500 mb-1">موجودی بلوکه شده</p>
                <p class="text-2xl font-bold text-gray-800 persian-number">{{ number_format($totalPending) }}</p>
                <p class="text-xs text-gray-400 mt-1">در انتظار تسویه</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('admin.wallet.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="نام متخصص یا شماره تماس..." class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="w-full md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">مرتب‌سازی</label>
                    <select name="sort_by" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="balance_desc" {{ request('sort_by') == 'balance_desc' ? 'selected' : '' }}>بیشترین موجودی</option>
                        <option value="balance_asc" {{ request('sort_by') == 'balance_asc' ? 'selected' : '' }}>کمترین موجودی</option>
                        <option value="earned_desc" {{ request('sort_by') == 'earned_desc' ? 'selected' : '' }}>بیشترین درآمد</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">جستجو</button>
                    @if(request()->anyFilled(['search', 'sort_by']))
                        <a href="{{ route('admin.wallet.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">پاک کردن</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">متخصص</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">موجودی فعلی</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">کل درآمد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">وضعیت شبا</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($wallets as $wallet)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                                            {{ mb_substr($wallet->specialist?->name ?? '؟' ?? '—', 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="mr-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $wallet->specialist?->name ?? '—' }}</div>
                                        <div class="text-sm text-gray-500">{{ $wallet->specialist?->phone ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 persian-number">{{ number_format($wallet->balance) }}</span>
                                <span class="text-xs text-gray-500">تومان</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 persian-number">{{ number_format($wallet->total_earned) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($wallet->iban)
                                    @if($wallet->iban_verified)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">تایید شده</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">در انتظار تایید</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">ثبت نشده</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.wallet.show', $wallet) }}" class="text-blue-600 hover:text-blue-900 ml-3">جزئیات</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">هیچ کیف پولی یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $wallets->links() }}
            </div>
        </div>
    </div>
@endsection
