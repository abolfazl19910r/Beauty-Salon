//resources/js/Components/booking/BookingSummary.jsx
import React from 'react';

const BookingSummary = ({
                            service = {},
                            specialist = {},
                            date = new Date(),
                            time = '',
                            discountCode = '',
                            onApplyDiscount = () => {},
                            onConfirm = () => {}
                        }) => {
    const prepaymentAmount = 50000;

    return (
        <div className="border rounded-lg p-6 bg-gray-50">
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
                    <span className="font-medium" dir="ltr">
            {new Intl.DateTimeFormat('fa-IR').format(new Date(date))}
          </span>
                </div>

                <div className="flex justify-between">
                    <span className="text-gray-600">ساعت:</span>
                    <span className="font-medium" dir="ltr">{time}</span>
                </div>

                <div className="border-t my-4"></div>

                <div className="flex justify-between text-lg">
                    <span className="text-gray-600">قیمت کل:</span>
                    <span className="font-bold">
            {new Intl.NumberFormat('fa-IR').format(service.price)} تومان
          </span>
                </div>

                <div className="flex justify-between text-lg text-blue-600">
                    <span>مبلغ پیش پرداخت:</span>
                    <span className="font-bold">
            {new Intl.NumberFormat('fa-IR').format(prepaymentAmount)} تومان
          </span>
                </div>

                <div className="mt-6">
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={discountCode}
                            onChange={(e) => onApplyDiscount(e.target.value)}
                            placeholder="کد تخفیف"
                            className="flex-1 border rounded px-3 py-2"
                        />
                        <button
                            className="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                            onClick={() => onApplyDiscount(discountCode)}
                        >
                            اعمال
                        </button>
                    </div>
                </div>

                <button
                    onClick={onConfirm}
                    className="w-full bg-blue-600 text-white py-3 rounded-lg mt-6 hover:bg-blue-700"
                >
                    تایید و پرداخت
                </button>
            </div>
        </div>
    );
};

export default BookingSummary;
