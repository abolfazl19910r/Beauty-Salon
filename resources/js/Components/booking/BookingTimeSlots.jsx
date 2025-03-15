import React from 'react';
import { Clock } from 'lucide-react';

const BookingTimeSlots = ({ slots = [], selectedTime, onSelect, loading = false }) => {
    if (loading) {
        return (
            <div className="py-8 flex justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
            </div>
        );
    }

    if (!slots.length) {
        return (
            <div className="text-center p-8 bg-gray-50 rounded-lg">
                <Clock className="mx-auto w-8 h-8 text-gray-400 mb-2" />
                <p className="text-gray-600">هیچ زمان خالی برای این روز وجود ندارد</p>
                <p className="text-sm text-gray-500 mt-1">لطفاً روز دیگری را انتخاب کنید</p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {slots.map((time) => (
                <div
                    key={time}
                    onClick={() => onSelect(time)}
                    className={`
            p-3 rounded-lg text-center cursor-pointer transition-all duration-200
            ${selectedTime === time
                        ? 'bg-pink-500 text-white'
                        : 'bg-gray-100 hover:bg-pink-100'}
          `}
                >
                    <Clock className="w-4 h-4 mx-auto mb-1" />
                    <span dir="ltr">{time}</span>
                </div>
            ))}
        </div>
    );
};

export default BookingTimeSlots;
