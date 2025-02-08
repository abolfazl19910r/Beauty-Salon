import React, { useState } from 'react';
import { Calendar, Clock, Star, MessageCircle } from 'lucide-react';

const BookingActions = () => {
    const [showReschedule, setShowReschedule] = useState(false);
    const [showRating, setShowRating] = useState(false);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [rating, setRating] = useState(0);
    const [review, setReview] = useState('');
    const [discountCode, setDiscountCode] = useState('');

    const handleReschedule = async () => {
        try {
            const response = await fetch(`/api/bookings/reschedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_time: `${selectedDate} ${selectedTime}`
                })
            });

            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Error rescheduling booking:', error);
        }
    };

    const handleCancel = async () => {
        if (!window.confirm('آیا از لغو نوبت اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/api/bookings/cancel`, {
                method: 'POST'
            });

            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Error cancelling booking:', error);
        }
    };

    const handleSubmitRating = async () => {
        try {
            const response = await fetch(`/api/bookings/rate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ rating, review })
            });

            if (response.ok) {
                setShowRating(false);
                window.location.reload();
            }
        } catch (error) {
            console.error('Error submitting rating:', error);
        }
    };

    const handleApplyDiscount = async () => {
        try {
            const response = await fetch(`/api/bookings/apply-discount`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: discountCode })
            });

            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            console.error('Error applying discount:', error);
        }
    };

    return (
        <div className="space-y-6">
            {/* Action Buttons */}
            <div className="flex gap-4">
                <button
                    onClick={() => setShowReschedule(true)}
                    className="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg"
                >
                    <Clock size={18} />
                    تغییر زمان
                </button>

                <button
                    onClick={handleCancel}
                    className="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg"
                >
                    <Calendar size={18} />
                    لغو نوبت
                </button>

                <button
                    onClick={() => setShowRating(true)}
                    className="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg"
                >
                    <Star size={18} />
                    ثبت نظر
                </button>
            </div>

            {/* Discount Code Section */}
            <div className="flex gap-2">
                <input
                    type="text"
                    value={discountCode}
                    onChange={(e) => setDiscountCode(e.target.value)}
                    placeholder="کد تخفیف"
                    className="flex-1 px-4 py-2 border rounded-lg"
                />
                <button
                    onClick={handleApplyDiscount}
                    className="px-4 py-2 bg-green-500 text-white rounded-lg"
                >
                    اعمال
                </button>
            </div>

            {/* Reschedule Modal */}
            {showReschedule && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full">
                        <h3 className="text-lg font-bold mb-4">تغییر زمان نوبت</h3>

                        <div className="space-y-4">
                            <div>
                                <label className="block mb-2">تاریخ جدید</label>
                                <input
                                    type="date"
                                    className="w-full px-4 py-2 border rounded-lg"
                                    onChange={(e) => setSelectedDate(e.target.value)}
                                />
                            </div>

                            <div>
                                <label className="block mb-2">ساعت جدید</label>
                                <select
                                    className="w-full px-4 py-2 border rounded-lg"
                                    onChange={(e) => setSelectedTime(e.target.value)}
                                >
                                    <option value="">انتخاب کنید</option>
                                    <option value="09:00">09:00</option>
                                    <option value="10:00">10:00</option>
                                    <option value="11:00">11:00</option>
                                    <option value="12:00">12:00</option>
                                </select>
                            </div>

                            <div className="flex justify-end gap-4">
                                <button
                                    onClick={() => setShowReschedule(false)}
                                    className="px-4 py-2 bg-gray-200 rounded-lg"
                                >
                                    انصراف
                                </button>
                                <button
                                    onClick={handleReschedule}
                                    className="px-4 py-2 bg-blue-500 text-white rounded-lg"
                                >
                                    تایید
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Rating Modal */}
            {showRating && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full">
                        <h3 className="text-lg font-bold mb-4">ثبت نظر و امتیاز</h3>

                        <div className="space-y-4">
                            <div>
                                <label className="block mb-2">امتیاز</label>
                                <div className="flex gap-2">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <Star
                                            key={star}
                                            size={24}
                                            className={`cursor-pointer ${
                                                star <= rating ? 'text-yellow-400' : 'text-gray-200'
                                            }`}
                                            onClick={() => setRating(star)}
                                        />
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="block mb-2">نظر شما</label>
                                <textarea
                                    value={review}
                                    onChange={(e) => setReview(e.target.value)}
                                    className="w-full px-4 py-2 border rounded-lg"
                                    rows={4}
                                />
                            </div>

                            <div className="flex justify-end gap-4">
                                <button
                                    onClick={() => setShowRating(false)}
                                    className="px-4 py-2 bg-gray-200 rounded-lg"
                                >
                                    انصراف
                                </button>
                                <button
                                    onClick={handleSubmitRating}
                                    className="px-4 py-2 bg-blue-500 text-white rounded-lg"
                                >
                                    ثبت
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default BookingActions;
