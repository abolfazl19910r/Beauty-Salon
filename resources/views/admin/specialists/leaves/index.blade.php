@extends('layouts.app')

@section('title', 'مدیریت مرخصی‌ها')

@section('content')
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">مدیریت مرخصی‌های {{ $specialist->name }}</h1>
                <button type="button"
                        onclick="document.getElementById('new-leave-modal').classList.remove('hidden')"
                        class="bg-green-500 text-white px-4 py-2 rounded">
                    ثبت مرخصی جدید
                </button>
            </div>

            <!-- جدول مرخصی‌ها -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-right">تاریخ شروع</th>
                        <th class="px-4 py-2 text-right">تاریخ پایان</th>
                        <th class="px-4 py-2 text-right">دلیل</th>
                        <th class="px-4 py-2 text-right">وضعیت</th>
                        <th class="px-4 py-2">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($leaves as $leave)
                        <tr>
                            <td class="px-4 py-2">
                                {{ verta($leave->start_date)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-2">
                                {{ verta($leave->end_date)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-2">{{ $leave->reason }}</td>
                            <td class="px-4 py-2">
                                @switch($leave->status)
                                    @case('pending')
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                                                در انتظار تایید
                                            </span>
                                        @break
                                    @case('approved')
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded">
                                                تایید شده
                                            </span>
                                        @break
                                    @case('rejected')
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded">
                                                رد شده
                                            </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-2">
                                @if($leave->status === 'pending')
                                    <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="text-green-500">تایید</button>
                                    </form>
                                    <form action="{{ route('admin.specialists.leaves.update', [$specialist, $leave]) }}"
                                          method="POST" class="inline mr-4">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="text-red-500">رد</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leaves->links() }}
            </div>
        </div>
    </div>

    <!-- مودال ثبت مرخصی جدید -->
    <div id="new-leave-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">ثبت مرخصی جدید</h2>
                    <button type="button"
                            onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                            class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.specialists.leaves.store', $specialist) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1">تاریخ شروع</label>
                            <input type="date" name="start_date" required
                                   class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block mb-1">تاریخ پایان</label>
                            <input type="date" name="end_date" required
                                   class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block mb-1">دلیل</label>
                            <textarea name="reason" rows="3"
                                      class="w-full border rounded px-3 py-2"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-4 space-x-reverse">
                        <button type="button"
                                onclick="document.getElementById('new-leave-modal').classList.add('hidden')"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                            انصراف
                        </button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                            ثبت مرخصی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
