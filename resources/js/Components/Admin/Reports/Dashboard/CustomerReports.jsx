// resources/js/Components/Admin/Reports/Dashboard/CustomerReports.jsx
import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import ExportButtons from '../Common/ExportButtons';

const CustomerReports = () => {
    const [satisfactionData, setSatisfactionData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/admin/reports/customer-satisfaction');
                const data = await response.json();
                setSatisfactionData(data);
            } catch (error) {
                console.error('Error fetching satisfaction data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) return <div className="p-4">در حال بارگذاری...</div>;

    const overallSatisfaction = satisfactionData.reduce((acc, curr) =>
        acc + curr.satisfaction_rate, 0) / satisfactionData.length;

    return (
        <div className="bg-white rounded-lg shadow p-4">
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">رضایت مشتریان</h2>
                <ExportButtons reportType="satisfaction" />
            </div>

            <div className="bg-blue-50 rounded-lg p-6 mb-6">
                <div className="text-center">
                    <div className="text-3xl font-bold text-blue-600 mb-2">
                        {Math.round(overallSatisfaction)}%
                    </div>
                    <div className="text-sm text-gray-600">میانگین رضایت کلی</div>
                </div>
                <div className="mt-4">
                    <div className="relative w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            className="absolute top-0 left-0 h-full bg-blue-500 transition-all duration-500"
                            style={{ width: `${overallSatisfaction}%` }}
                        />
                    </div>
                </div>
            </div>

            <div className="h-96">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={satisfactionData}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="specialist_name" />
                        <YAxis domain={[0, 100]} />
                        <Tooltip />
                        <Legend />
                        <Line
                            type="monotone"
                            dataKey="satisfaction_rate"
                            name="درصد رضایت"
                            stroke="#8884d8"
                            strokeWidth={2}
                        />
                        <Line
                            type="monotone"
                            dataKey="average_rating"
                            name="میانگین امتیاز"
                            stroke="#82ca9d"
                            strokeWidth={2}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>

            <div className="mt-6 grid gap-4">
                {satisfactionData.map((item, index) => (
                    <div key={index} className="border rounded-lg p-4">
                        <div className="flex justify-between items-center mb-2">
                            <h4 className="font-bold">{item.specialist_name}</h4>
                            <span className="text-sm text-gray-500">
                {item.total_ratings} نظر
              </span>
                        </div>
                        <div className="space-y-2">
                            <div className="flex justify-between items-center">
                                <span>درصد رضایت:</span>
                                <span className="font-bold">{item.satisfaction_rate}%</span>
                            </div>
                            <div className="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    className="absolute top-0 left-0 h-full bg-blue-500 transition-all duration-500"
                                    style={{ width: `${item.satisfaction_rate}%` }}
                                />
                            </div>
                            <div className="flex justify-between items-center text-sm text-gray-500">
                                <span>میانگین امتیاز: {item.average_rating} از 5</span>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default CustomerReports;
