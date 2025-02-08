// resources/js/Components/Admin/Reports/Common/ExportButtons.jsx
import React from 'react';
import { DocumentArrowDownIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

const ExportButtons = ({ reportType }) => {
    const handleExport = (format) => {
        window.location.href = `/admin/reports/export?type=${reportType}&format=${format}`;
    };

    return (
        <div className="flex space-x-2 space-x-reverse">
            <button
                onClick={() => handleExport('excel')}
                className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center"
            >
                <DocumentArrowDownIcon className="w-5 h-5 ml-2" />
                خروجی Excel
            </button>
            <button
                onClick={() => handleExport('pdf')}
                className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 flex items-center"
            >
                <DocumentTextIcon className="w-5 h-5 ml-2" />
                خروجی PDF
            </button>
        </div>
    );
};

export default ExportButtons;
