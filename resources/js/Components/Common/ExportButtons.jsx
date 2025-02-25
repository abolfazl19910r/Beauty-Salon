// resources/js/Components/Admin/Reports/Common/ExportButtons.jsx
import React from 'react';
import { Download } from 'lucide-react';

const ExportButtons = ({ reportType }) => {
    const handleExport = (format) => {
        const params = new URLSearchParams({
            type: reportType,
            format
        });

        window.location.href = `/admin/reports/export?${params}`;
    };

    return (
        <div className="flex space-x-2 space-x-reverse">
            <button
                onClick={() => handleExport('excel')}
                className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center gap-2"
            >
                <Download className="w-5 h-5" />
                خروجی Excel
            </button>
            <button
                onClick={() => handleExport('pdf')}
                className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2"
            >
                <Download className="w-5 h-5" />
                خروجی PDF
            </button>
        </div>
    );
};

export default ExportButtons;
