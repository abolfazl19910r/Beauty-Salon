//resources/js/Components/booking/TimeSlotGrid.jsx
import React from 'react';
import { Clock } from 'lucide-react';

const TimeSlotGrid = ({ slots = [], selectedTime, onSelect }) => {
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
        <div className="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
            {slots.map(time => (
                <button
                    key={time}
                    onClick={() => onSelect(time)}
                    className={`
            p-3 rounded-lg text-center transition-colors
            ${selectedTime === time
                        ? 'bg-blue-500 text-white'
                        : 'border hover:bg-blue-50'
                    }
          `}
                >
                    {time}
                </button>
            ))}
        </div>
    );
};

export default TimeSlotGrid;
