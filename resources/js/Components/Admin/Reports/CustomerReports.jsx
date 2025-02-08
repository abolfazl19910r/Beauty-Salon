import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const CustomerReports = () => {
    const [satisfactionData, setSatisfactionData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/api/admin/reports/customer-satisfaction');
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

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    // محاسبه میانگین کل رضایت
    const overallSatisfaction = satisfactionData.reduce((acc, curr) =>
        acc + curr.satisfaction_rate, 0) / satisfactionData.length;

    return (
        <div className="space-y-6 p-6">
            {/* نمایش میانگین کلی رضایت */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">میانگین رضایت مشتریان</h2>
                </div>
                <div className="p-6">
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
            </div>

            {/* نمودار رضایت به تفکیک متخصصین */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">رضایت مشتریان به تفکیک متخصصین</h2>
                </div>
                <div className="p-6">
                    <div className="h-96">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart data={satisfactionData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="specialist_name" />
                                <YAxis domain={[0, 100]} />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="satisfaction_rate" name="درصد رضایت" stroke="#8884d8" />
                                <Line type="monotone" dataKey="average_rating" name="میانگین امتیاز" stroke="#82ca9d" />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            {/* جزئیات به تفکیک متخصصین */}
            <div className="bg-white rounded-lg shadow">
                <div className="border-b p-6">
                    <h2 className="text-xl font-bold">جزئیات رضایت مشتریان</h2>
                </div>
                <div className="p-6">
                    <div className="grid gap-4">
                        {satisfactionData.map((item, index) => (
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

export default CustomerReports;
