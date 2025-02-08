// resources/js/Components/Admin/Reports/Dashboard/RevenueCharts.jsx
import React, { useState, useEffect } from 'react';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import ExportButtons from '../Common/ExportButtons';

const RevenueCharts = () => {
    const [timeframe, setTimeframe] = useState('daily');
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, [timeframe]);

    const fetchData = async () => {
        try {
            const response = await fetch(`/admin/reports/${timeframe}`);
            const result = await response.json();
            setData(result);
        } catch (error) {
            console.error('Error fetching revenue data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="p-4">در حال بارگذاری...</div>;

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div className="flex space-x-4 space-x-reverse">
                    <button
                        onClick={() => setTimeframe('daily')}
                        className={`px-4 py-2 rounded ${
                            timeframe === 'daily' ? 'bg-blue-500 text-white' : 'bg-gray-100'
                        }`}
                    >
                        روزانه
                    </button>
                    <button
                        onClick={() => setTimeframe('weekly')}
                        className={`px-4 py-2 rounded ${
                            timeframe === 'weekly' ? 'bg-blue-500 text-white' : 'bg-gray-100'
                        }`}
                    >
                        هفتگی
                    </button>
                    <button
                        onClick={() => setTimeframe('monthly')}
                        className={`px-4 py-2 rounded ${
                            timeframe === 'monthly' ? 'bg-blue-500 text-white' : 'bg-gray-100'
                        }`}
                    >
                        ماهانه
                    </button>
                </div>
                <ExportButtons reportType={timeframe} />
            </div>

            <div className="h-96 bg-white rounded-lg p-4">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey={timeframe === 'monthly' ? 'month' : 'date'} />
                        <YAxis />
                        <Tooltip />
                        <Legend />
                        <Line type="monotone" dataKey="revenue" name="درآمد" stroke="#8884d8" />
                        <Line type="monotone" dataKey="total_bookings" name="تعداد نوبت" stroke="#82ca9d" />
                    </LineChart>
                </ResponsiveContainer>
            </div>

            {timeframe !== 'daily' && (
                <div className="h-72 bg-white rounded-lg p-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={data}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey={timeframe === 'monthly' ? 'month' : 'week_start'} />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Bar dataKey="average_booking_value" name="میانگین نوبت" fill="#8884d8" />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
};

export default RevenueCharts;
