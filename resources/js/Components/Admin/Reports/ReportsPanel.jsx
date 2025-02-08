import React from 'react';
import { LineChart, BarChart, Bar, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend } from 'recharts';
import { Calendar, DollarSign, Users, Star } from 'lucide-react';
import MonthlyReport from "./MonthlyReport";
import SpecialistReports from "./SpecialistReports";
import SatisfactionReport from "./SatisfactionReport";
import FinancialReports from "./FinancialReports";

const ReportsPanel = () => {
    const [activeTab, setActiveTab] = React.useState('financial');

    return (
        <div className="p-6 space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div onClick={() => setActiveTab('financial')}
                     className="bg-white p-6 rounded-lg shadow cursor-pointer hover:shadow-lg transition-shadow">
                    <div className="flex items-center justify-between mb-4">
                        <DollarSign className="text-green-500" size={24} />
                        <h3 className="text-lg font-semibold">گزارش مالی</h3>
                    </div>
                    <p className="text-gray-600">آمار درآمد و تراکنش‌ها</p>
                </div>

                <div onClick={() => setActiveTab('monthly')}
                     className="bg-white p-6 rounded-lg shadow cursor-pointer hover:shadow-lg transition-shadow">
                    <div className="flex items-center justify-between mb-4">
                        <Calendar className="text-blue-500" size={24} />
                        <h3 className="text-lg font-semibold">درآمد ماهانه</h3>
                    </div>
                    <p className="text-gray-600">تحلیل درآمد به تفکیک ماه</p>
                </div>

                <div onClick={() => setActiveTab('specialists')}
                     className="bg-white p-6 rounded-lg shadow cursor-pointer hover:shadow-lg transition-shadow">
                    <div className="flex items-center justify-between mb-4">
                        <Users className="text-purple-500" size={24} />
                        <h3 className="text-lg font-semibold">عملکرد متخصصین</h3>
                    </div>
                    <p className="text-gray-600">بررسی عملکرد و آمار متخصصین</p>
                </div>

                <div onClick={() => setActiveTab('satisfaction')}
                     className="bg-white p-6 rounded-lg shadow cursor-pointer hover:shadow-lg transition-shadow">
                    <div className="flex items-center justify-between mb-4">
                        <Star className="text-yellow-500" size={24} />
                        <h3 className="text-lg font-semibold">رضایت مشتریان</h3>
                    </div>
                    <p className="text-gray-600">آمار و نظرات مشتریان</p>
                </div>
            </div>

            <div className="mt-8">
                {activeTab === 'financial' && <FinancialReports />}
                {activeTab === 'monthly' && <MonthlyReport />}
                {activeTab === 'specialists' && <SpecialistReports />}
                {activeTab === 'satisfaction' && <SatisfactionReport />}
            </div>
        </div>
    );
};

export default ReportsPanel;
