@extends('layouts.admin')

@section('title', 'نمایش جزئیات کاربر')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">جزئیات کاربر</h1>
                <p class="text-sm text-gray-500">نمایش اطلاعات کامل کاربر {{ $user->name }}</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    ویرایش کاربر
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            <!-- اطلاعات کاربر -->
            <div class="md:col-span-8">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-14 h-14 rounded-full bg-blue-500 text-white text-xl flex items-center justify-center ml-4">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                                <div class="flex items-center mt-1">
                                    @if($user->is_admin)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-2">
                                            مدیر
                                        </span>
                                    @endif
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            فعال
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            غیرفعال
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">شماره موبایل:</div>
                                    <div class="font-medium text-gray-900" dir="ltr">{{ $user->phone }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">ایمیل:</div>
                                    <div class="font-medium text-gray-900" dir="ltr">{{ $user->email ?? '---' }}</div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">تاریخ ثبت‌نام:</div>
                                    <div class="font-medium text-gray-900" dir="ltr">{{ verta($user->created_at)->formatDatetime() }}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="text-sm text-gray-500 mb-1">آخرین بروزرسانی:</div>
                                    <div class="font-medium text-gray-900" dir="ltr">{{ verta($user->updated_at)->formatDatetime() }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-sm text-gray-500 mb-2">نقش‌های کاربر:</div>
                            <div class="flex flex-wrap gap-2">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $role->label }}
                                    </span>
                                @empty
                                    <span class="text-gray-400 text-sm">کاربر نقشی ندارد</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نوبت‌های کاربر -->
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 mt-6">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">آخرین نوبت‌های کاربر</h2>

                        @if($bookings->isEmpty())
                            <div class="py-4 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-gray-500">این کاربر هنوز نوبتی ثبت نکرده است</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                    <tr class="bg-gray-50 text-gray-600 text-sm">
                                        <th class="py-3 px-4 text-right font-medium">#</th>
                                        <th class="py-3 px-4 text-right font-medium">تاریخ نوبت</th>
                                        <th class="py-3 px-4 text-right font-medium">خدمت</th>
                                        <th class="py-3 px-4 text-right font-medium">متخصص</th>
                                        <th class="py-3 px-4 text-right font-medium">وضعیت</th>
                                        <th class="py-3 px-4 text-right font-medium">مبلغ (تومان)</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                    @foreach($bookings as $booking)
                                        <tr class="hover:bg-gray-50 text-sm transition-colors">
                                            <td class="py-3 px-4 text-gray-500">{{ $booking->id }}</td>
                                            <td class="py-3 px-4 text-gray-800" dir="ltr">
                                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-800">{{ $booking->service->name }}</td>
                                            <td class="py-3 px-4 text-gray-800">{{ $booking->specialist->name }}</td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $booking->status_badge }}">
                                                    {{ $booking->status_text }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-800 font-medium">
                                                {{ number_format($booking->prepayment_amount) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a href="#" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                                    <span>مشاهده همه نوبت‌ها</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- عملیات کاربر -->
            <div class="md:col-span-4">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">عملیات</h2>
                        <div class="space-y-3">
                            <a href="{{ route('admin.users.edit', $user) }}" class="flex items-center w-full p-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                ویرایش اطلاعات کاربر
                            </a>

                            <form action="{{ route('admin.users.status.update', $user) }}" method="POST" class="w-full">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                <button type="submit" class="flex items-center w-full p-2 {{ $user->is_active ? 'bg-red-50 hover:bg-red-100 text-red-700' : 'bg-green-50 hover:bg-green-100 text-green-700' }} rounded-lg transition-colors">
                                    @if($user->is_active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        غیرفعال کردن کاربر
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        فعال کردن کاربر
                                    @endif
                                </button>
                            </form>

                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex items-center w-full p-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors"
                                        data-confirm-delete data-confirm-message="آیا از حذف این کاربر اطمینان دارید؟">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    حذف کاربر
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- بخش تغییر رمز عبور -->
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 mt-6">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">تغییر رمز عبور</h2>
                        <form action="{{ route('admin.users.password.reset', $user) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور جدید</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           dir="ltr"
                                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="حداقل 8 کاراکتر">
                                </div>
                                @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تکرار رمز عبور جدید</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           dir="ltr"
                                           class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="تکرار رمز عبور">
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    تغییر رمز عبور
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- مدیریت نقش‌ها -->
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 mt-6">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">مدیریت نقش‌ها</h2>
                        <form action="{{ route('admin.users.roles.sync', $user) }}" method="POST">
                            @csrf
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @foreach($roles as $role)
                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               name="roles[]"
                                               id="role-{{ $role->id }}"
                                               value="{{ $role->id }}"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                                        <label for="role-{{ $role->id }}" class="mr-2 block text-sm font-medium text-gray-700">
                                            {{ $role->label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    بروزرسانی نقش‌ها
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
