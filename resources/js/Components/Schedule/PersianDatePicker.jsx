import React, { useState } from 'react';

const PersianDatePicker = ({
                               value,
                               onChange,
                               placeholder = 'انتخاب تاریخ',
                               minDate = null,
                               maxDate = null
                           }) => {
    const [showNative, setShowNative] = useState(false);

    const toPersianDigits = (num) => {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/[0-9]/g, w => persianNumbers[w]);
    };

    const toPersianDate = (dateStr) => {
        if (!dateStr) return '';

        const date = new Date(dateStr);
        return date.toLocaleDateString('fa-IR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const displayDate = toPersianDate(value);

    return (
        <div className="relative w-full">
            {/* نمایش تاریخ فارسی */}
            <input
                type="text"
                value={displayDate}
                onClick={() => setShowNative(true)}
                readOnly
                placeholder={placeholder}
                className="w-full border rounded-lg px-4 py-2 cursor-pointer bg-white"
            />

            {/* date picker اصلی */}
            {showNative && (
                <div className="absolute inset-0 opacity-0" style={{ zIndex: 1 }}>
                    <input
                        type="date"
                        value={value || ''}
                        onChange={(e) => {
                            onChange(e.target.value);
                            setShowNative(false);
                        }}
                        onBlur={() => setShowNative(false)}
                        min={minDate}
                        max={maxDate}
                        className="w-full h-full cursor-pointer"
                        autoFocus
                    />
                </div>
            )}
        </div>
    );
};

// کامپوننت انتخاب بازه تاریخ
export const PersianDateRangePicker = ({
                                           startDate,
                                           endDate,
                                           onStartDateChange,
                                           onEndDateChange,
                                           minDate = null,
                                           maxDate = null
                                       }) => {
    return (
        <div className="flex items-center gap-2">
            <PersianDatePicker
                value={startDate}
                onChange={onStartDateChange}
                placeholder="تاریخ شروع"
                minDate={minDate}
                maxDate={endDate || maxDate}
            />
            <span className="text-gray-500">تا</span>
            <PersianDatePicker
                value={endDate}
                onChange={onEndDateChange}
                placeholder="تاریخ پایان"
                minDate={startDate || minDate}
                maxDate={maxDate}
            />
        </div>
    );
};

export default PersianDatePicker;
