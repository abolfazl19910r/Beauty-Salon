import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const MonthlyReport = () => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/admin/reports/monthly-revenue')
            .then(res => res.json())
            .then(setData)
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">درآمد ماهانه</h2>
                </div>
                <div className="p-6">
                    <div className="h-96">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={data}>
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
                </div>
            </div>

            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">جزئیات ماهانه</h2>
                </div>
                <div className="p-6 overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-right">ماه</th>
                            <th className="px-6 py-3 text-right">درآمد</th>
                            <th className="px-6 py-3 text-right">تعداد نوبت</th>
                            <th className="px-6 py-3 text-right">میانگین هر نوبت</th>
                        </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                        {data.map((month, index) => (
                            <tr key={index}>
                                <td className="px-6 py-4">{month.month}</td>
                                <td className="px-6 py-4">{new Intl.NumberFormat('fa-IR').format(month.revenue)} تومان</td>
                                <td className="px-6 py-4">{month.bookings}</td>
                                <td className="px-6 py-4">{new Intl.NumberFormat('fa-IR').format(month.average_booking_value)} تومان</td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default MonthlyReport;
