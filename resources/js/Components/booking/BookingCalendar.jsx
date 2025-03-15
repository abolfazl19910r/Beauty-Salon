import React, { useState, useEffect } from 'react';
import { ChevronRight, ChevronLeft } from 'lucide-react';

const BookingCalendar = ({
                             availableDates = [],
                             selectedDate,
                             onSelectDate,
                             loading = false
                         }) => {
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [calendarDays, setCalendarDays] = useState([]);

    useEffect(() => {
        generateCalendarDays(currentMonth);
    }, [currentMonth, availableDates]);

    const generateCalendarDays = (date) => {
        const year = date.getFullYear();
        const month = date.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);

        const firstDayOfWeek = firstDay.getDay();

        const days = [];

        const adjustedFirstDay = (firstDayOfWeek + 1) % 7;
        for (let i = 0; i < adjustedFirstDay; i++) {
            days.push({ type: 'empty', date: null });
        }

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const date = new Date(year, month, day);
            const dateString = date.toISOString().split('T')[0];

            const isAvailable = availableDates.includes(dateString);
            const isPast = date < new Date(new Date().setHours(0, 0, 0, 0));

            days.push({
                type: 'day',
                date: dateString,
                day,
                isAvailable: isAvailable && !isPast,
                isPast
            });
        }

        setCalendarDays(days);
    };

    const changeMonth = (increment) => {
        const newMonth = new Date(currentMonth);
        newMonth.setMonth(newMonth.getMonth() + increment);
        setCurrentMonth(newMonth);
    };

    const weekDays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    const getPersianMonthName = (date) => {
        return date.toLocaleDateString('fa-IR', { month: 'long', year: 'numeric' });
    };

    if (loading) {
        return (
            <div className="py-8 flex justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
            </div>
        );
    }

    return (
        <div className="w-full max-w-md mx-auto">
            {/* Calendar Header */}
            <div className="flex justify-between items-center mb-4">
                <button
                    onClick={() => changeMonth(-1)}
                    className="p-2 rounded-full hover:bg-gray-100"
                    disabled={currentMonth <= new Date()}
                >
                    <ChevronRight className="w-5 h-5" />
                </button>

                <h3 className="text-lg font-bold">
                    {getPersianMonthName(currentMonth)}
                </h3>

                <button
                    onClick={() => changeMonth(1)}
                    className="p-2 rounded-full hover:bg-gray-100"
                >
                    <ChevronLeft className="w-5 h-5" />
                </button>
            </div>

            {/* Days of Week */}
            <div className="grid grid-cols-7 mb-2">
                {weekDays.map(day => (
                    <div key={day} className="text-center text-gray-500 text-sm py-2">
                        {day}
                    </div>
                ))}
            </div>

            {/* Calendar Grid */}
            <div className="grid grid-cols-7 gap-1">
                {calendarDays.map((day, index) => {
                    if (day.type === 'empty') {
                        return <div key={`empty-${index}`} className="aspect-square" />;
                    }

                    return (
                        <div
                            key={day.date}
                            onClick={() => day.isAvailable && onSelectDate(day.date)}
                            className={`
                aspect-square flex items-center justify-center rounded-full text-sm
                ${day.isAvailable
                                ? selectedDate === day.date
                                    ? 'bg-pink-500 text-white'
                                    : 'hover:bg-pink-100 cursor-pointer'
                                : day.isPast
                                    ? 'text-gray-300 bg-gray-50'
                                    : 'text-gray-400 bg-gray-50'
                            }
              `}
                        >
                            {day.day}
                        </div>
                    );
                })}
            </div>

            {availableDates.length === 0 && !loading && (
                <div className="text-center mt-4 text-gray-500">
                    تاریخی در دسترس نیست
                </div>
            )}
        </div>
    );
};

export default BookingCalendar;
