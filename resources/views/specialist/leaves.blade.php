@extends('layouts.specialist')

@section('title', ' مرخصی‌')

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
@endsection

@section('content')
    <div class="max-w-6xl mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">مدیریت مرخصی‌ها</h1>
                <p class="text-sm text-gray-500">درخواست و مشاهده مرخصی‌های خود</p>
            </div>
            <div class="flex gap-2">
                <button type="button"
                        onclick="document.getElementById('new-leave-modal').classList.remove('hidden')"
                        class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    ثبت مرخصی جدید
                </button>
                <a href="{{ route('specialist.profile.show') }}"
                   class="flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    بازگشت
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">لیست مرخصی‌ها</h2>
                <p class="text-sm text-gray-500 mt-1">مشاهده درخواست‌های مرخصی</p>
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
                                    <span class="bg-pink-100 text-pink-600 rounded-full w-8 h-8 flex items-center justify-center ml-2">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </span>
                                    {{ jdate($leave->start_date)->format('Y/m/d') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ jdate($leave->end_date)->format('Y/m/d') }}</td>
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
                                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-medium">
                                            در انتظار تایید
                                        </span>
                                        @break
                                    @case('approved')
                                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                            تایید شده
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-medium">
                                            رد شده
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @if($leave->status === 'pending')
                                    <form action="{{ route('specialist.leaves.destroy', $leave) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800 transition-colors text-sm"
                                                onclick="return confirm('آیا از حذف این درخواست اطمینان دارید؟')">
                                            <svg class="w-5 h-5 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
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

    <div id="new-leave-modal" class="hidden fixed inset-0 bg-black bg-opacity-30 backdrop-blur-sm z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">ثبت مرخصی جدید</h2>
                    <button type="button"
                            onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                            class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('specialist.leaves.store') }}" method="POST">
                    @csrf
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ شروع</label>
                            <input type="text" id="start_date_jalali" name="start_date_jalali" required
                                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                   placeholder="انتخاب تاریخ شروع">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ پایان</label>
                            <input type="text" id="end_date_jalali" name="end_date_jalali" required
                                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                   placeholder="انتخاب تاریخ پایان">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">دلیل</label>
                            <textarea name="reason" rows="3"
                                      class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                      placeholder="توضیحاتی درباره درخواست مرخصی..."></textarea>
                        </div>
                    </div>

                    <div class="p-5 border-t border-gray-100 flex justify-end space-x-4 space-x-reverse">
                        <button type="button"
                                onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                            انصراف
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-lg hover:opacity-90 transition-opacity">
                            ثبت مرخصی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('new-leave-modal');
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            $("#start_date_jalali").persianDatepicker({
                format: 'YYYY/MM/DD',
                minDate: new persianDate(),
                autoClose: true,
                initialValue: false,
                onSelect: function(unix) {
                    const pd = new persianDate(unix);
                    $("#end_date_jalali").persianDatepicker({
                        format: 'YYYY/MM/DD',
                        minDate: pd,
                        autoClose: true,
                        initialValue: false
                    });
                }
            });

            $("#end_date_jalali").persianDatepicker({
                format: 'YYYY/MM/DD',
                minDate: new persianDate(),
                autoClose: true,
                initialValue: false
            });
        });
    </script>
@endpush
