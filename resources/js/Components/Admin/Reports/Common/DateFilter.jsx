// resources/js/Components/Admin/Reports/Common/DateFilter.jsx
import React from 'react';
import { Calendar } from 'lucide-react';

const DateFilter = ({ startDate, endDate, onStartDateChange, onEndDateChange }) => {
    return (
        <div className="flex items-center space-x-4 space-x-reverse">
            <div className="relative">
                <label className="block text-sm text-gray-600 mb-1">از تاریخ</label>
                <div className="relative">
                    <input
                        type="date"
                        value={startDate}
                        onChange={(e) => onStartDateChange(e.target.value)}
                        className="pr-10 pl-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                    />
                    <Calendar className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                </div>
            </div>

            <div className="relative">
                <label className="block text-sm text-gray-600 mb-1">تا تاریخ</label>
                <div className="relative">
                    <input
                        type="date"
                        value={endDate}
                        onChange={(e) => onEndDateChange(e.target.value)}
                        className="pr-10 pl-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                    />
                    <Calendar className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                </div>
            </div>
        </div>
    );
};

export default DateFilter;
