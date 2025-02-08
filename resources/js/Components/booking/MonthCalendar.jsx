//MonthCalendar.jsx
import React, { useState, useEffect } from 'react';
import { ChevronRight, ChevronLeft } from 'lucide-react';

const MonthCalendar = ({ specialist, initialDate = new Date(), onSelectDate }) => {
    const [currentDate, setCurrentDate] = useState(initialDate);
    const [availability, setAvailability] = useState(null);

    useEffect(() => {
        fetchAvailability();
    }, [currentDate]);

    const fetchAvailability = async () => {
        const yearMonth = currentDate.toISOString().slice(0, 7);
        const response = await fetch(`/api/specialists/${specialist.id}/availability/${yearMonth}`);
        const data = await response.json();
        setAvailability(data);
    };

    const changeMonth = (delta) => {
        setCurrentDate(prev => {
            const newDate = new Date(prev);
            newDate.setMonth(newDate.getMonth() + delta);
            return newDate;
        });
    };

    const generateDays = () => {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const days = [];

        // Add empty cells for days before first of month
        const firstDayOfWeek = firstDay.getDay();
        for (let i = 0; i < firstDayOfWeek; i++) {
            days.push({ type: 'empty' });
        }

        // Add actual days
        for (let date = 1; date <= lastDay.getDate(); date++) {
            const fullDate = new Date(year, month, date).toISOString().split('T')[0];
            const status = getDayStatus(fullDate);
            days.push({ type: 'day', date: fullDate, dayNumber: date, status });
        }

        return days;
    };

    const getDayStatus = (date) => {
        if (!availability) return 'disabled';
        if (availability.holiday_days.includes(date)) return 'holiday';
        if (availability.fully_booked_days.includes(date)) return 'booked';
        if (availability.available_days.some(d => d.date === date)) return 'available';
        return 'disabled';
    };

    return (
        <div className="w-full max-w-md mx-auto">
            {/* Header */}
            <div className="flex justify-between items-center mb-4">
                <button onClick={() => changeMonth(-1)} className="p-1 hover:bg-gray-100 rounded">
                    <ChevronRight className="w-5 h-5" />
                </button>
                <h3 className="text-lg font-bold">
                    {currentDate.toLocaleDateString('fa-IR', { year: 'numeric', month: 'long' })}
                </h3>
                <button onClick={() => changeMonth(1)} className="p-1 hover:bg-gray-100 rounded">
                    <ChevronLeft className="w-5 h-5" />
                </button>
            </div>

            {/* Calendar Grid */}
            <div className="grid grid-cols-7 gap-1">
                {/* Weekday headers */}
                {['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map(day => (
                    <div key={day} className="text-center text-gray-500 text-sm py-2">{day}</div>
                ))}

                {/* Calendar days */}
                {generateDays().map((day, index) => {
                    if (day.type === 'empty') {
                        return <div key={`empty-${index}`} className="aspect-square" />;
                    }

                    const statusClasses = {
                        available: 'hover:bg-blue-50 cursor-pointer',
                        holiday: 'bg-red-50 text-red-600',
                        booked: 'bg-gray-50 text-gray-400',
                        disabled: 'bg-gray-50 text-gray-300'
                    }[day.status];

                    return (
                        <div
                            key={day.date}
                            onClick={() => day.status === 'available' && onSelectDate(day.date)}
                            className={`
                aspect-square flex items-center justify-center text-sm rounded
                ${statusClasses}
              `}
                        >
                            {day.dayNumber}
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default MonthCalendar;
