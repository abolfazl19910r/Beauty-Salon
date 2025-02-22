// resources/js/Components/Admin/Reports/FinancialReports.jsx
import React, { useState, useEffect } from 'react';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import DateFilter from './Common/DateFilter';

const FinancialReports = ({ reportType, startDate, endDate }) => {
    const [data, setData] = useState(null);
    const [activeTab, setActiveTab] = useState('summary');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const params = new URLSearchParams({
                    type: reportType,
                    start_date: startDate,
                    end_date: endDate
                });

                const response = await fetch(`/admin/reports/financial?${params}`);
                const result = await response.json();
                setData(result);
            } catch (error) {
                console.error('Error fetching financial data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, [reportType, startDate, endDate]);

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    const tabs = [
        { id: 'summary', name: 'خلاصه مالی' },
        { id: 'monthly', name: 'گزارش ماهانه' },
        { id: 'services', name: 'درآمد خدمات' },
        { id: 'payments', name: 'روش‌های پرداخت' }
    ];

    return (
        <div className="space-y-6">
            <div className="flex space-x-4 space-x-reverse">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        className={`
                            px-4 py-2 rounded-lg text-sm font-medium
                            ${activeTab === tab.id
                            ? 'bg-blue-100 text-blue-700'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }
                        `}
                    >
                        {tab.name}
                    </button>
                ))}
            </div>

            {activeTab === 'summary' && (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="bg-blue-50 rounded-lg p-4">
                        <div className="text-sm text-gray-500">درآمد کل</div>
                        <div className="text-2xl font-bold text-blue-600">
                            {new Intl.NumberFormat('fa-IR').format(data?.summary?.total_revenue)} تومان
                        </div>
                    </div>
                    <div className="bg-green-50 rounded-lg p-4">
                        <div className="text-sm text-gray-500">میانگین هر نوبت</div>
                        <div className="text-2xl font-bold text-green-600">
                            {new Intl.NumberFormat('fa-IR').format(data?.summary?.average_booking_value)} تومان
                        </div>
                    </div>
                    <div className="bg-yellow-50 rounded-lg p-4">
                        <div className="text-sm text-gray-500">در انتظار پرداخت</div>
                        <div className="text-2xl font-bold text-yellow-600">
                            {new Intl.NumberFormat('fa-IR').format(data?.summary?.pending_payments)} تومان
                        </div>
                    </div>
                    <div className="bg-red-50 rounded-lg p-4">
                        <div className="text-sm text-gray-500">مبلغ بازگشتی</div>
                        <div className="text-2xl font-bold text-red-600">
                            {new Intl.NumberFormat('fa-IR').format(data?.summary?.refunded_amount)} تومان
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'monthly' && (
                <div className="h-[400px]">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data?.monthly_breakdown}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="month" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Line type="monotone" dataKey="revenue" name="درآمد" stroke="#8884d8" />
                            <Line type="monotone" dataKey="average_booking_value" name="میانگین نوبت" stroke="#82ca9d" />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* دیگر تب‌ها را هم به همین صورت می‌توانید اضافه کنید */}
        </div>
    );
};

export default FinancialReports;
