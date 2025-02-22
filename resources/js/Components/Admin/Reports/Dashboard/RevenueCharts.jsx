// resources/js/Components/Admin/Reports/Dashboard/RevenueCharts.jsx
import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import DateFilter from '../Common/DateFilter';
import ExportButtons from '../Common/ExportButtons';

const RevenueCharts = ({ reportType, startDate, endDate }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, [reportType, startDate, endDate]);

    const fetchData = async () => {
        try {
            const params = new URLSearchParams({
                type: reportType,
                start_date: startDate,
                end_date: endDate
            });

            const response = await fetch(`/admin/reports/daily-revenue?${params}`);
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
        <div className="bg-white rounded-lg shadow p-4">
            <div className="h-[400px]">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="date" />
                        <YAxis />
                        <Tooltip />
                        <Legend />
                        <Line
                            type="monotone"
                            dataKey="revenue"
                            name="درآمد"
                            stroke="#8884d8"
                            strokeWidth={2}
                        />
                        <Line
                            type="monotone"
                            dataKey="total_bookings"
                            name="تعداد نوبت"
                            stroke="#82ca9d"
                            strokeWidth={2}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
};

export default RevenueCharts;
