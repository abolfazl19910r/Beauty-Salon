@extends('layouts.admin')

@section('title', 'درخواست‌های برداشت')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-yellow-400">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">درخواست‌های منتظر بررسی</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1 persian-number">{{ $pendingCount }}</h3>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-full text-yellow-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">مجموع مبلغ در انتظار</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1 persian-number">{{ number_format($pendingAmount) }} <span class="text-xs font-normal">تومان</span></h3>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-full text-blue-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border-r-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">تسویه شده امروز</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1 persian-number">{{ $completedToday }} <span class="text-xs font-normal">مورد</span></h3>
                    </div>
                    <div class="p-3 bg-green-50 rounded-full text-green-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('admin.wallet.withdrawals') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">وضعیت</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار بررسی</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>ناموفق/رد شده</option>
                    </select>
                </div>
                <div class="w-full md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">روش واریز</label>
                    <select name="method" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">همه روش‌ها</option>
                        <option value="iban" {{ request('method') == 'iban' ? 'selected' : '' }}>شبا</option>
                        <option value="instant" {{ request('method') == 'instant' ? 'selected' : '' }}>فوری</option>
                    </select>
                </div>
                <div class="w-full md:w-1/3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">جستجو</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="کد رهگیری، نام متخصص..." class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">فیلتر</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">کد پیگیری</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">متخصص</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">مبلغ خالص</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">روش</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">وضعیت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($withdrawals as $withdrawal)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                                {{ $withdrawal->reference_code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $withdrawal->specialist->name }}</div>
                                <div class="text-xs text-gray-500">{{ $withdrawal->specialist->phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 persian-number">{{ number_format($withdrawal->net_amount) }}</span>
                                <span class="text-xs text-gray-500">تومان</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $withdrawal->method_text }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $withdrawal->status_badge_color }}-100 text-{{ $withdrawal->status_badge_color }}-800">
                                    {{ $withdrawal->status_text }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 persian-number">
                                {{ verta($withdrawal->created_at)->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.wallet.withdrawals.show', $withdrawal) }}" class="text-blue-600 hover:text-blue-900">بررسی</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">هیچ درخواست برداشتی یافت نشد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $withdrawals->links() }}
            </div>
        </div>
    </div>
@endsection
