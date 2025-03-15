import React, { useState, useEffect } from 'react';
import { Check, Calendar, Clock, User, ArrowRight, Loader } from 'lucide-react';
import axios from 'axios';

const BookingSuccessPage = () => {
    const [booking, setBooking] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchBookingData = async () => {
            try {
                setLoading(true);

                const urlParams = new URLSearchParams(window.location.search);
                const bookingId = urlParams.get('id');

                let endpoint = '';
                if (bookingId) {
                    endpoint = `/api/bookings/${bookingId}`;
                } else {
                    endpoint = '/api/bookings/latest-successful';
                }

                const response = await axios.get(endpoint);
                setBooking(response.data);
                setError(null);
            } catch (err) {
                setError('خطا در دریافت اطلاعات رزرو. لطفاً مجدداً تلاش کنید.');
            } finally {
                setLoading(false);
            }
        };

        fetchBookingData();
    }, []);

    if (loading) {
        return (
            <div className="max-w-md mx-auto p-6 flex flex-col items-center justify-center min-h-64">
                <Loader className="w-12 h-12 text-blue-500 animate-spin mb-4" />
                <p className="text-gray-600">در حال دریافت اطلاعات...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="max-w-md mx-auto bg-red-50 p-6 rounded-lg text-center">
                <p className="text-red-600 font-bold mb-4">خطا</p>
                <p className="text-gray-700">{error}</p>
                <button
                    onClick={() => window.location.reload()}
                    className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                >
                    تلاش مجدد
                </button>
            </div>
        );
    }

    if (!booking) {
        return (
            <div className="max-w-md mx-auto bg-yellow-50 p-6 rounded-lg text-center">
                <p className="text-yellow-600 font-bold mb-4">اطلاعات رزرو یافت نشد</p>
                <p className="text-gray-700">متأسفانه اطلاعات رزرو در دسترس نیست.</p>
                <a
                    href="/"
                    className="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                >
                    بازگشت به صفحه اصلی
                </a>
            </div>
        );
    }

    return (
        <div className="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center fade-in">
            {/* Success Icon */}
            <div className="text-green-500 mb-6">
                <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <Check className="w-8 h-8" />
                </div>
            </div>

            {/* Main Heading */}
            <h1 className="text-2xl font-bold mb-4 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">
                پرداخت با موفقیت انجام شد
            </h1>

            <p className="text-gray-600 mb-2">رزرو شما با موفقیت ثبت شد.</p>
            <p className="text-gray-600 mb-6">پیامک تاییدیه برای شما ارسال خواهد شد.</p>

            {/* Payment Information Box */}
            <div className="bg-gray-50 p-5 rounded-lg mb-6 text-right">
                <h2 className="font-bold mb-4 text-center">اطلاعات پرداخت</h2>
                <div className="space-y-2">
                    <div className="flex justify-between items-center">
                        <span className="text-gray-600">شماره پیگیری:</span>
                        <span className="font-medium text-pink-700" dir="ltr">{booking.payment_ref}</span>
                    </div>
                    <div className="flex justify-between items-center">
                        <span className="text-gray-600">مبلغ پرداختی:</span>
                        <span className="font-medium">{booking.prepayment_amount.toLocaleString()} تومان</span>
                    </div>
                    <div className="flex justify-between items-center">
                        <span className="text-gray-600">تاریخ پرداخت:</span>
                        <span className="font-medium" dir="ltr">
              {new Date(booking.paid_at).toLocaleString('fa-IR')}
            </span>
                    </div>
                </div>
            </div>

            {/* Booking Details */}
            <div className="bg-blue-50 p-5 rounded-lg mb-6 text-right">
                <h2 className="font-bold mb-4 text-center">جزئیات نوبت</h2>
                <div className="space-y-3">
                    <div className="flex items-center">
                        <User className="w-5 h-5 text-blue-500 ml-2" />
                        <span className="text-gray-600 ml-1">متخصص:</span>
                        <span className="font-medium mr-2">{booking.specialist.name}</span>
                    </div>

                    <div className="flex items-center">
                        <Calendar className="w-5 h-5 text-blue-500 ml-2" />
                        <span className="text-gray-600 ml-1">تاریخ:</span>
                        <span className="font-medium mr-2">
              {new Date(booking.booking_time).toLocaleDateString('fa-IR')}
            </span>
                    </div>

                    <div className="flex items-center">
                        <Clock className="w-5 h-5 text-blue-500 ml-2" />
                        <span className="text-gray-600 ml-1">ساعت:</span>
                        <span className="font-medium mr-2">
              {new Date(booking.booking_time).toLocaleTimeString('fa-IR', {
                  hour: '2-digit',
                  minute: '2-digit'
              })}
            </span>
                    </div>
                </div>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                <a
                    href={`/bookings/${booking.id}`}
                    className="flex-1 bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center"
                >
                    مشاهده جزئیات نوبت
                    <ArrowRight className="mr-2 w-4 h-4" />
                </a>

                <a
                    href="/"
                    className="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors"
                >
                    بازگشت به صفحه اصلی
                </a>
            </div>
        </div>
    );
};

export default BookingSuccessPage;
