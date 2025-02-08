// resources/js/Components/Admin/Reports/Dashboard/SpecialistReports.jsx

import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import ExportButtons from '../Common/ExportButtons';

const SpecialistReports = () => {
    const [specialists, setSpecialists] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/admin/reports/specialists');
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

    if (loading) return <div className="p-4">در حال بارگذاری...</div>;

    return (
        <div className="bg-white rounded-lg shadow p-4">
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">عملکرد متخصصین</h2>
                <ExportButtons reportType="specialists" />
            </div>

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

            <div className="mt-4 overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام متخصص</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد نوبت</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">درآمد کل</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">میانگین نوبت</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نرخ تکمیل</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نرخ بازگشت</th>
                    </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                    {specialists.map((specialist) => (
                        <tr key={specialist.id}>
                            <td className="px-6 py-4 whitespace-nowrap">{specialist.name}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{specialist.total_bookings}</td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                {new Intl.NumberFormat('fa-IR').format(specialist.total_revenue)} تومان
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                {new Intl.NumberFormat('fa-IR').format(specialist.average_booking_value)} تومان
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">{specialist.booking_completion_rate}%</td>
                            <td className="px-6 py-4 whitespace-nowrap">{specialist.customer_return_rate}%</td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default SpecialistReports;
