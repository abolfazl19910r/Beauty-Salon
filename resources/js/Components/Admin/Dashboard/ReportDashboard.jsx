//ReportDashboard.jsx
import React, { useState } from 'react';
import RevenueCharts from './Dashboard/RevenueCharts';
import PopularServices from './Dashboard/PopularServices';
import FinancialReports from './Reports/FinancialReports';
import SpecialistReports from './Reports/SpecialistReports';
import CustomerReports from './Reports/CustomerReports';

const ReportDashboard = () => {
    const [activeTab, setActiveTab] = useState('overview');

    const tabs = [
        { id: 'overview', name: 'نمای کلی' },
        { id: 'financial', name: 'گزارش مالی' },
        { id: 'specialists', name: 'عملکرد متخصصین' },
        { id: 'customers', name: 'رضایت مشتریان' }
    ];

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">گزارش‌های مدیریتی</h1>
            </div>

            <div className="border-b">
                <nav className="flex space-x-8 space-x-reverse" aria-label="Tabs">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`
                py-4 px-1 border-b-2 font-medium text-sm
                ${activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }
              `}
                        >
                            {tab.name}
                        </button>
                    ))}
                </nav>
            </div>

            <div>
                {activeTab === 'overview' && (
                    <div className="space-y-6">
                        <RevenueCharts />
                        <PopularServices />
                    </div>
                )}
                {activeTab === 'financial' && <FinancialReports />}
                {activeTab === 'specialists' && <SpecialistReports />}
                {activeTab === 'customers' && <CustomerReports />}
            </div>
        </div>
    );
};

export default ReportDashboard;
