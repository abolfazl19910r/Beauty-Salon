@extends('layouts.admin')

@section('title', 'جزئیات نوبت')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
                جزئیات نوبت #{{ $booking->id }}
            </h1>

            <div class="flex gap-2">
                <a href="{{ route('admin.bookings.edit', $booking) }}" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    ویرایش نوبت
                </a>

                <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <h2 class="text-xl font-semibold">اطلاعات مشتری</h2>
                </div>
                <div class="space-y-3 mr-8">
                    @if($booking->user)
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                                {{ mb_substr($booking->user->name, 0, 1) }}
                            </div>
                            <div class="mr-4">
                                <div class="font-medium text-lg">{{ $booking->user->name }}</div>
                                <div class="text-gray-500 text-sm" dir="ltr">{{ $booking->user->phone }}</div>
                            </div>
                        </div>
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">ایمیل:</span>
                            <span class="font-medium">{{ $booking->user->email ?? 'ثبت نشده' }}</span>
                        </p>
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">تاریخ عضویت:</span>
                            <span class="font-medium">{{ verta($booking->user->created_at)->format('Y/m/d') }}</span>
                        </p>
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">تعداد نوبت‌ها:</span>
                            <span class="font-medium persian-number">{{ $booking->user->bookings_count ?? $booking->user->bookings()->count() }}</span>
                        </p>
                    @else
                        <div class="flex items-center justify-center h-32 text-red-600 bg-red-50 rounded-lg">
                            <svg class="w-6 h-6 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            اطلاعات کاربر در دسترس نیست
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h2 class="text-xl font-semibold">جزئیات نوبت</h2>
                </div>
                <div class="space-y-3 mr-8">
                    <p class="flex items-center">
                        <span class="inline-block w-24 text-gray-600">تاریخ:</span>
                        <span class="font-medium">{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                    </p>
                    <p class="flex items-center">
                        <span class="inline-block w-24 text-gray-600">ساعت:</span>
                        <span class="font-medium">{{ verta($booking->booking_time)->format('H:i') }}</span>
                    </p>

                    @if($booking->service)
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">خدمت:</span>
                            <span class="font-medium">{{ $booking->service->name }}</span>
                        </p>
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">قیمت:</span>
                            <span class="font-medium">{{ number_format($booking->service->price) }} تومان</span>
                        </p>
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">مدت زمان:</span>
                            <span class="font-medium">{{ $booking->service->duration ?? 60 }} دقیقه</span>
                        </p>
                    @else
                        <div class="text-red-600">اطلاعات خدمت در دسترس نیست</div>
                    @endif

                    @if($booking->specialist)
                        <p class="flex items-center">
                            <span class="inline-block w-24 text-gray-600">متخصص:</span>
                            <span class="font-medium">{{ $booking->specialist->name }}</span>
                        </p>
                    @else
                        <div class="text-red-600">اطلاعات متخصص در دسترس نیست</div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <h2 class="text-xl font-semibold">وضعیت</h2>
                </div>
                <div class="space-y-3 mr-8">
                    <p class="flex items-center">
                        <span class="inline-block w-32 text-gray-600">وضعیت نوبت:</span>
                        <span class="px-3 py-1 rounded text-sm {{ $booking->getStatusBadgeAttribute() }}">
                            {{ $booking->getStatusTextAttribute() }}
                        </span>
                    </p>
                    <p class="flex items-center">
                        <span class="inline-block w-32 text-gray-600">وضعیت پرداخت:</span>
                        <span class="px-3 py-1 rounded text-sm
                            @if($booking->payment_status == 'paid') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $booking->payment_status == 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                        </span>
                    </p>
                    <p class="flex items-center">
                        <span class="inline-block w-32 text-gray-600">تاریخ ثبت:</span>
                        <span class="font-medium">{{ verta($booking->created_at)->format('Y/m/d H:i') }}</span>
                    </p>
                    @if($booking->notes)
                        <div class="mt-4">
                            <p class="text-gray-600 mb-2">یادداشت:</p>
                            <p class="bg-gray-50 p-3 rounded-lg text-gray-700">{{ $booking->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold">عملیات</h2>
                </div>
                <div class="space-y-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($booking->status == 'pending')
                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                تایید نوبت
                            </button>
                        </form>
                    @endif

                    @if($booking->status != 'cancelled')
                        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center"
                                    data-confirm-delete data-confirm-message="آیا از لغو این نوبت اطمینان دارید؟">
                                <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                لغو نوبت
                            </button>
                        </form>
                    @endif

                    @if($booking->payment_status == 'unpaid')
                        <a href="{{ route('admin.payments.create', ['booking_id' => $booking->id]) }}"
                           class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            ثبت پرداخت
                        </a>
                    @endif

                    <a href="{{ route('admin.bookings.edit', $booking) }}"
                       class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        ویرایش نوبت
                    </a>
                </div>
            </div>

            @if($booking->payment_status == 'paid')
                <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <h2 class="text-xl font-semibold">اطلاعات مالی</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3 mr-8">
                            <p class="flex items-center">
                                <span class="inline-block w-32 text-gray-600">مبلغ پرداختی:</span>
                                <span class="font-medium">{{ number_format($booking->prepayment_amount) }} تومان</span>
                            </p>
                            <p class="flex items-center">
                                <span class="inline-block w-32 text-gray-600">نوع پرداخت:</span>
                                <span class="font-medium">{{ $booking->payment_method ?? 'آنلاین' }}</span>
                            </p>
                            <p class="flex items-center">
                                <span class="inline-block w-32 text-gray-600">تاریخ پرداخت:</span>
                                <span class="font-medium">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</span>
                            </p>
                        </div>

                        <div class="space-y-3 mr-8">
                            <p class="flex items-center">
                                <span class="inline-block w-32 text-gray-600">شماره پیگیری:</span>
                                <span class="font-medium">{{ $booking->payment_reference ?? 'ندارد' }}</span>
                            </p>
                            @if($booking->discount_amount)
                                <p class="flex items-center">
                                    <span class="inline-block w-32 text-gray-600">کد تخفیف:</span>
                                    <span class="font-medium">{{ $booking->discount_code }}</span>
                                </p>
                                <p class="flex items-center">
                                    <span class="inline-block w-32 text-gray-600">مبلغ تخفیف:</span>
                                    <span class="font-medium">{{ number_format($booking->discount_amount) }} تومان</span>
                                </p>
                            @endif
                            @if($booking->refund_status)
                                <p class="flex items-center">
                                    <span class="inline-block w-32 text-gray-600">وضعیت استرداد:</span>
                                    <span class="px-3 py-1 rounded text-sm" style="background-color: {{ $booking->getRefundStatusColorAttribute() }}">
                                        {{ $booking->getRefundStatusTextAttribute() }}
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
