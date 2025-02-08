// resources/js/Components/Admin/Reports/ReportDashboard.jsx
import React, { useState } from 'react';
import RevenueCharts from './Dashboard/RevenueCharts';
import PopularServices from './Dashboard/PopularServices';
import SpecialistReports from './Dashboard/SpecialistReports';
import CustomerReports from './Dashboard/CustomerReports';
import { DocumentTextIcon, ChartBarIcon, UserGroupIcon, StarIcon } from '@heroicons/react/24/outline';

const ReportDashboard = () => {
    const [activeTab, setActiveTab] = useState('overview');

    const tabs = [
        {
            id: 'overview',
            name: 'نمای کلی',
            icon: ChartBarIcon,
            description: 'نمای کلی از وضعیت کسب و کار'
        },
        {
            id: 'revenue',
            name: 'گزارش مالی',
            icon: DocumentTextIcon,
            description: 'گزارش‌های مالی و درآمد'
        },
        {
            id: 'specialists',
            name: 'عملکرد متخصصین',
            icon: UserGroupIcon,
            description: 'آمار و عملکرد متخصصین'
        },
        {
            id: 'satisfaction',
            name: 'رضایت مشتریان',
            icon: StarIcon,
            description: 'نظرات و رضایت مشتریان'
        }
    ];

    const renderContent = () => {
        switch (activeTab) {
            case 'overview':
                return (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <RevenueCharts />
                        <PopularServices />
                    </div>
                );
            case 'revenue':
                return <RevenueCharts />;
            case 'specialists':
                return <SpecialistReports />;
            case 'satisfaction':
                return <CustomerReports />;
            default:
                return null;
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">گزارش‌های مدیریتی</h1>
            </div>

            <div className="bg-white rounded-lg shadow">
                <div className="border-b border-gray-200">
                    <nav className="flex space-x-8 space-x-reverse px-4" aria-label="Tabs">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`
                  group relative min-w-0 flex-1 overflow-hidden py-4 px-4 text-sm font-medium text-center
                  hover:bg-gray-50 focus:z-10 focus:outline-none
                  ${activeTab === tab.id
                                    ? 'text-blue-600 border-b-2 border-blue-600'
                                    : 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent'
                                }
                `}
                            >
                                <div className="flex items-center justify-center">
                                    <tab.icon className="w-5 h-5 ml-2" />
                                    <span>{tab.name}</span>
                                </div>

                                <span
                                    aria-hidden="true"
                                    className={`absolute inset-x-0 bottom-0 h-0.5 ${
                                        activeTab === tab.id ? 'bg-blue-600' : 'bg-transparent'
                                    }`}
                                />
                            </button>
                        ))}
                    </nav>
                </div>

                <div className="p-4">
                    <p className="text-sm text-gray-500 mb-4">
                        {tabs.find(tab => tab.id === activeTab)?.description}
                    </p>

                    {renderContent()}
                </div>
            </div>
        </div>
    );
};

export default ReportDashboard;
