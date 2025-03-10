@extends('layouts.admin')

@section('title', 'مدیریت مرخصی‌ها')

@section('content')
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت مرخصی‌ها</h1>
                <p class="text-sm text-gray-500">مدیریت زمان‌های مرخصی {{ $specialist->name }}</p>
            </div>
            <button type="button"
                    onclick="document.getElementById('new-leave-modal').classList.remove('hidden')"
                    class="mt-3 md:mt-0 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm hover:shadow flex items-center transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                ثبت مرخصی جدید
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">لیست مرخصی‌ها</h2>
                <p class="text-sm text-gray-500 mt-1">مشاهده و مدیریت درخواست‌های مرخصی</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm">
                        <th class="px-4 py-3 text-right font-medium">تاریخ شروع</th>
                        <th class="px-4 py-3 text-right font-medium">تاریخ پایان</th>
                        <th class="px-4 py-3 text-right font-medium">دلیل</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($leaves as $leave)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <span class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center ml-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    {{ verta($leave->start_date)->format('Y/m/d') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ verta($leave->end_date)->format('Y/m/d') }}</td>
                            <td class="px-4 py-3">
                                @if($leave->reason)
                                    {{ $leave->reason }}
                                @else
                                    <span class="text-gray-400 text-sm">بدون توضیحات</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @switch($leave->status)
                                    @case('pending')
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                            در انتظار تایید
                                        </span>
                                        @break
                                    @case('approved')
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            تایید شده
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                            رد شده
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <div class="flex space-x-2 space-x-reverse">
                                        <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="text-green-600 hover:text-green-800 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="text-red-600 hover:text-red-800 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p>هیچ درخواست مرخصی ثبت نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaves->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>
    </div>

    <div id="new-leave-modal" class="hidden fixed inset-0 bg-black bg-opacity-30 backdrop-blur-sm z-50 transition-all duration-200">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">ثبت مرخصی جدید</h2>
                    <button type="button"
                            onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.specialists.leaves.store', $specialist) }}" method="POST">
                    @csrf
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ شروع</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="date" name="start_date" required
                                       class="w-full pr-10 border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ پایان</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="date" name="end_date" required
                                       class="w-full pr-10 border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">دلیل</label>
                            <textarea name="reason" rows="3"
                                      class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                      placeholder="توضیحاتی درباره درخواست مرخصی..."></textarea>
                        </div>
                    </div>

                    <div class="p-5 border-t border-gray-100 flex justify-end space-x-4 space-x-reverse">
                        <button type="button"
                                onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                            انصراف
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                            ثبت مرخصی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('new-leave-modal');
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
@endpush
