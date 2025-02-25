// resources/js/Components/Admin/Reports/Common/PersianDatePicker.jsx
import React, { useState, useEffect, useRef } from 'react';
import { Calendar, ChevronRight, ChevronLeft } from 'lucide-react';
import { getMonthDays, isValidPersianDate, comparePersianDates } from '../../Utils/DateUtils.js';

const DAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
const MONTHS = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
];

const PersianDatePicker = ({
                               value,
                               onChange,
                               label,
                               placeholder = '۱۴۰۲/۰۱/۰۱',
                               minDate,
                               maxDate,
                               error
                           }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [selectedDate, setSelectedDate] = useState(value);
    const [currentYear, setCurrentYear] = useState(1402);
    const [currentMonth, setCurrentMonth] = useState(0);
    const wrapperRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (value && isValidPersianDate(value)) {
            const [year, month] = value.split('/').map(Number);
            setCurrentYear(year);
            setCurrentMonth(month - 1);
            setSelectedDate(value);
        }
    }, [value]);

    const handleDayClick = (day) => {
        const newDate = `${currentYear}/${String(currentMonth + 1).padStart(2, '0')}/${String(day).padStart(2, '0')}`;

        if (minDate && comparePersianDates(newDate, minDate) < 0) return;
        if (maxDate && comparePersianDates(newDate, maxDate) > 0) return;

        setSelectedDate(newDate);
        onChange(newDate);
        setIsOpen(false);
    };

    const generateDays = () => {
        const days = [];
        const daysInMonth = getMonthDays(currentYear, currentMonth + 1);

        for (let i = 1; i <= daysInMonth; i++) {
            const currentDate = `${currentYear}/${String(currentMonth + 1).padStart(2, '0')}/${String(i).padStart(2, '0')}`;
            const isDisabled = (minDate && comparePersianDates(currentDate, minDate) < 0) ||
                (maxDate && comparePersianDates(currentDate, maxDate) > 0);

            days.push(
                <button
                    key={i}
                    onClick={() => handleDayClick(i)}
                    disabled={isDisabled}
                    className={`
                        w-8 h-8 rounded-full text-sm
                        ${isDisabled ? 'text-gray-300 cursor-not-allowed' :
                        currentDate === selectedDate ? 'bg-blue-500 text-white' :
                            'hover:bg-blue-100 text-gray-700'}
                    `}
                >
                    {i}
                </button>
            );
        }
        return days;
    };

    const nextMonth = () => {
        if (currentMonth === 11) {
            setCurrentMonth(0);
            setCurrentYear(currentYear + 1);
        } else {
            setCurrentMonth(currentMonth + 1);
        }
    };

    const prevMonth = () => {
        if (currentMonth === 0) {
            setCurrentMonth(11);
            setCurrentYear(currentYear - 1);
        } else {
            setCurrentMonth(currentMonth - 1);
        }
    };

    return (
        <div className="relative" ref={wrapperRef}>
            <label className="block text-sm text-gray-600 mb-1">{label}</label>
            <div className="relative">
                <input
                    type="text"
                    readOnly
                    value={selectedDate || ''}
                    placeholder={placeholder}
                    onClick={() => setIsOpen(!isOpen)}
                    className={`
                        w-full pl-10 pr-4 py-2 border rounded-lg text-sm
                        focus:outline-none focus:ring-2 focus:ring-blue-500
                        ${error ? 'border-red-500' : 'border-gray-300'}
                        cursor-pointer
                    `}
                />
                <Calendar className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            </div>

            {error && (
                <span className="text-xs text-red-500 mt-1">{error}</span>
            )}

            {isOpen && (
                <div className="absolute z-50 mt-1 p-3 bg-white rounded-lg shadow-lg border w-64">
                    <div className="flex justify-between items-center mb-4">
                        <button
                            onClick={prevMonth}
                            className="p-1 hover:bg-gray-100 rounded-full"
                        >
                            <ChevronRight className="w-5 h-5" />
                        </button>
                        <div className="text-sm font-medium">
                            {MONTHS[currentMonth]} {currentYear}
                        </div>
                        <button
                            onClick={nextMonth}
                            className="p-1 hover:bg-gray-100 rounded-full"
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </button>
                    </div>

                    <div className="grid grid-cols-7 gap-1 mb-2">
                        {DAYS.map(day => (
                            <div key={day} className="w-8 h-8 flex items-center justify-center text-xs text-gray-500">
                                {day}
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-7 gap-1">
                        {generateDays()}
                    </div>
                </div>
            )}
        </div>
    );
};

export default PersianDatePicker;
