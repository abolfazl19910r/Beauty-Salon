import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const SpecialistReports = () => {
    const [specialists, setSpecialists] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/api/admin/reports/specialist-performance');
                const data = await response.json();
                setSpecialists(data);
            } catch (error) {
                console.error('Error fetching specialist data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    return (
        <div className="space-y-6 p-6">
            {/* نمودار مقایسه‌ای */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">مقایسه عملکرد متخصصین</h2>
                </div>
                <div className="p-6">
                    <div className="h-96">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={specialists}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="name" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Bar dataKey="total_bookings" name="تعداد نوبت" fill="#8884d8" />
                                <Bar dataKey="booking_completion_rate" name="نرخ تکمیل" fill="#82ca9d" />
                                <Bar dataKey="customer_return_rate" name="نرخ بازگشت مشتری" fill="#ffc658" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            {/* جدول جزئیات */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">جزئیات عملکرد متخصصین</h2>
                </div>
                <div className="p-6 overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                نام متخصص
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                تعداد نوبت
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                درآمد کل
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                میانگین هر نوبت
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                نرخ تکمیل
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                نرخ بازگشت مشتری
                            </th>
                        </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                        {specialists.map((specialist) => (
                            <tr key={specialist.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {specialist.name}
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {specialist.total_bookings}
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {new Intl.NumberFormat('fa-IR').format(specialist.total_revenue)} تومان
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {new Intl.NumberFormat('fa-IR').format(specialist.average_booking_value)} تومان
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {specialist.booking_completion_rate}%
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-900">
                                    {specialist.customer_return_rate}%
                                </td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default SpecialistReports;
