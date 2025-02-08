import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const SatisfactionReport = () => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/admin/reports/customer-satisfaction')
            .then(res => res.json())
            .then(setData)
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    const overallSatisfaction = data.reduce((acc, curr) =>
        acc + curr.satisfaction_rate, 0) / data.length;

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-xl font-bold mb-4">میانگین رضایت مشتریان</h2>
                <div className="text-center">
                    <div className="text-3xl font-bold mb-4">
                        {Math.round(overallSatisfaction)}%
                    </div>
                    <div className="relative w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            className="absolute top-0 left-0 h-full bg-blue-500 transition-all duration-500"
                            style={{ width: `${overallSatisfaction}%` }}
                        />
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">رضایت به تفکیک متخصصین</h2>
                </div>
                <div className="p-6">
                    <div className="grid gap-4">
                        {data.map((item, index) => (
                            <div key={index} className="p-4 border rounded-lg">
                                <div className="flex justify-between items-center mb-2">
                                    <h3 className="font-bold">{item.specialist_name}</h3>
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
            </div>
        </div>
    );
};

export default SatisfactionReport;
