import React, { useState } from 'react';
import axios from 'axios';
import { AlertCircle } from 'lucide-react';

const BookingSummary = ({
                            service,
                            specialist,
                            date,
                            time,
                            discountCode,
                            setDiscountCode,
                            discountInfo,
                            setDiscountInfo,
                            onSubmit,
                            loading
                        }) => {
    const [applyingDiscount, setApplyingDiscount] = useState(false);
    const [error, setError] = useState(null);

    const formatPrice = (price) => {
        return new Intl.NumberFormat('fa-IR').format(price);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('fa-IR');
    };

    const handleApplyDiscount = async () => {
        if (!discountCode) return;

        setApplyingDiscount(true);
        setError(null);

        try {
            const response = await axios.post('/api/bookings/check-discount', {
                code: discountCode,
                service_id: service.id
            });

            if (response.data.valid) {
                setDiscountInfo({
                    amount: response.data.discount_amount,
                    finalPrice: response.data.final_price,
                    message: response.data.message
                });
                setError(null);
            } else {
                setError(response.data.message || 'کد تخفیف نامعتبر است.');
                setDiscountInfo(null);
            }
        } catch (err) {
            setError('خطا در بررسی کد تخفیف. لطفا دوباره تلاش کنید.');
            setDiscountInfo(null);
        } finally {
            setApplyingDiscount(false);
        }
    };

    if (!service || !specialist || !date || !time) {
        return null;
    }

    return (
        <div className="bg-white rounded-lg shadow-sm border p-6">
            <h3 className="font-bold text-xl mb-6">خلاصه رزرو</h3>

            <div className="space-y-4">
                <div className="flex justify-between">
                    <span className="text-gray-600">خدمت:</span>
                    <span className="font-medium">{service.name}</span>
                </div>

                <div className="flex justify-between">
                    <span className="text-gray-600">متخصص:</span>
                    <span className="font-medium">{specialist.name}</span>
                </div>

                <div className="flex justify-between">
                    <span className="text-gray-600">تاریخ:</span>
                    <span className="font-medium">{formatDate(date)}</span>
                </div>

                <div className="flex justify-between">
                    <span className="text-gray-600">ساعت:</span>
                    <span className="font-medium" dir="ltr">{time}</span>
                </div>

                {service.duration && (
                    <div className="flex justify-between">
                        <span className="text-gray-600">مدت زمان:</span>
                        <span className="font-medium">{service.duration} دقیقه</span>
                    </div>
                )}

                <hr className="my-3" />

                <div className="flex justify-between font-bold">
                    <span>قیمت:</span>
                    <span>{formatPrice(service.price)} تومان</span>
                </div>

                {discountInfo && (
                    <div className="flex justify-between text-green-600">
                        <span>تخفیف:</span>
                        <span>- {formatPrice(discountInfo.amount)} تومان</span>
                    </div>
                )}

                <div className="flex justify-between text-pink-600 font-bold">
                    <span>مبلغ پیش پرداخت:</span>
                    <span>{formatPrice(50000)} تومان</span>
                </div>

                {discountInfo && (
                    <div className="flex justify-between font-bold mt-2 pt-2 border-t">
                        <span>مبلغ نهایی:</span>
                        <span>{formatPrice(discountInfo.finalPrice)} تومان</span>
                    </div>
                )}

                {error && (
                    <div className="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg flex items-center text-sm mt-4">
                        <AlertCircle className="w-4 h-4 ml-2 flex-shrink-0" />
                        <span>{error}</span>
                    </div>
                )}

                <div className="mt-6">
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={discountCode}
                            onChange={(e) => setDiscountCode(e.target.value)}
                            placeholder="کد تخفیف"
                            className="flex-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-pink-300"
                        />
                        <button
                            onClick={handleApplyDiscount}
                            disabled={!discountCode || applyingDiscount}
                            className="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                        >
                            {applyingDiscount ? (
                                <div className="flex items-center">
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2"></div>
                                    <span>بررسی...</span>
                                </div>
                            ) : 'اعمال'}
                        </button>
                    </div>
                </div>

                <button
                    onClick={onSubmit}
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center mt-6"
                >
                    {loading ? (
                        <>
                            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white ml-2"></div>
                            در حال پردازش...
                        </>
                    ) : 'تأیید و پرداخت'}
                </button>
            </div>
        </div>
    );
};

export default BookingSummary;
