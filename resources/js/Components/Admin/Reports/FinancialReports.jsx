import React, { useState, useEffect } from 'react';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

const FinancialReports = () => {
    const [financialData, setFinancialData] = useState(null);
    const [activeTab, setActiveTab] = useState('summary');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/api/admin/reports/financial');
                const data = await response.json();
                setFinancialData(data);
            } catch (error) {
                console.error('Error fetching financial data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    const renderSummary = () => (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="bg-blue-50 rounded-lg p-4">
                <div className="text-sm text-gray-500">درآمد کل</div>
                <div className="text-2xl font-bold text-blue-600">
                    {new Intl.NumberFormat('fa-IR').format(financialData?.summary?.total_revenue)} تومان
                </div>
            </div>
            <div className="bg-green-50 rounded-lg p-4">
                <div className="text-sm text-gray-500">میانگین هر نوبت</div>
                <div className="text-2xl font-bold text-green-600">
                    {new Intl.NumberFormat('fa-IR').format(financialData?.summary?.average_booking_value)} تومان
                </div>
            </div>
            <div className="bg-yellow-50 rounded-lg p-4">
                <div className="text-sm text-gray-500">در انتظار پرداخت</div>
                <div className="text-2xl font-bold text-yellow-600">
                    {new Intl.NumberFormat('fa-IR').format(financialData?.summary?.pending_payments)} تومان
                </div>
            </div>
            <div className="bg-red-50 rounded-lg p-4">
                <div className="text-sm text-gray-500">مبلغ بازگشتی</div>
                <div className="text-2xl font-bold text-red-600">
                    {new Intl.NumberFormat('fa-IR').format(financialData?.summary?.refunded_amount)} تومان
                </div>
            </div>
        </div>
    );

    const renderMonthlyChart = () => (
        <div className="h-96">
            <ResponsiveContainer width="100%" height="100%">
                <LineChart data={financialData?.monthly_breakdown}>
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
    );

    const renderServicesChart = () => (
        <div className="h-96">
            <ResponsiveContainer width="100%" height="100%">
                <BarChart data={financialData?.service_revenue}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="revenue" name="درآمد" fill="#8884d8" />
                    <Bar dataKey="bookings" name="تعداد نوبت" fill="#82ca9d" />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );

    const renderPaymentsChart = () => (
        <div className="h-96">
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie
                        data={financialData?.payment_methods}
                        dataKey="total_amount"
                        nameKey="payment_method"
                        cx="50%"
                        cy="50%"
                        outerRadius={80}
                        label
                    >
                        {financialData?.payment_methods.map((entry, index) => (
                            <Cell key={index} fill={`#${Math.floor(Math.random()*16777215).toString(16)}`} />
                        ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                </PieChart>
            </ResponsiveContainer>
        </div>
    );

    return (
        <div className="p-6 space-y-6">
            {/* Tabs */}
            <div className="border-b">
                <nav className="flex space-x-4 space-x-reverse" aria-label="Tabs">
                    {[
                        { id: 'summary', name: 'خلاصه مالی' },
                        { id: 'monthly', name: 'گزارش ماهانه' },
                        { id: 'services', name: 'درآمد خدمات' },
                        { id: 'payments', name: 'روش‌های پرداخت' }
                    ].map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`${
                                activeTab === tab.id
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            } py-4 px-1 border-b-2 font-medium text-sm`}
                        >
                            {tab.name}
                        </button>
                    ))}
                </nav>
            </div>

            {/* Content */}
            <div className="bg-white rounded-lg shadow p-6">
                {activeTab === 'summary' && renderSummary()}
                {activeTab === 'monthly' && renderMonthlyChart()}
                {activeTab === 'services' && renderServicesChart()}
                {activeTab === 'payments' && renderPaymentsChart()}
            </div>
        </div>
    );
};

export default FinancialReports;
