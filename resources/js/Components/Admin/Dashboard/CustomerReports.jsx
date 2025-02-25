// resources/js/Components/Admin/Dashboard/CustomerReports.jsx
import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';

const CustomerReports = ({ reportType, startDate, endDate, baseUrl, data }) => {
    const [satisfaction, setSatisfaction] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (data) {
            handleReceivedData(data);
            return;
        }

        fetchData();
    }, [reportType, startDate, endDate, data]);

    const handleReceivedData = (data) => {
        if (data && data.satisfaction) {
            setSatisfaction(data.satisfaction);
        } else if (data && data.data && data.data.satisfaction) {
            setSatisfaction(data.data.satisfaction);
        } else if (Array.isArray(data)) {
            setSatisfaction(data);
        } else {
            console.error('Invalid data format received:', data);
            setError('ساختار داده‌های دریافتی معتبر نیست');
            setSatisfaction([]);
        }
        setLoading(false);
    };

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);

            const params = new URLSearchParams({
                type: reportType,
                start_date: startDate,
                end_date: endDate
            });

            const url = `${baseUrl}/admin/reports/customer-satisfaction?${params}`;
            console.log('Fetching customer satisfaction data from:', url);

            const response = await fetchWithErrorHandling(url);
            handleReceivedData(response);
        } catch (error) {
            console.error('Error fetching satisfaction data:', error);
            setError('خطا در دریافت اطلاعات: ' + error.message);
            setSatisfaction([]);
        } finally {
            setLoading(false);
        }
    };

    const fetchWithErrorHandling = async (url) => {
        const response = await fetch(url);

        if (!response.ok) {
            if (response.status === 500) {
                throw new Error('خطای داخلی سرور');
            } else if (response.status === 400) {
                const errorData = await response.json();
                throw new Error(errorData.message || errorData.error || 'داده‌های ارسالی معتبر نیستند');
            } else if (response.status === 404) {
                throw new Error('مسیر مورد نظر یافت نشد');
            }

            throw new Error('خطا در دریافت اطلاعات');
        }

        return await response.json();
    };

    if (loading) return <div className="p-4 text-center">در حال بارگذاری...</div>;

    if (error) {
        return (
            <Alert variant="destructive" className="mb-4">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    if (!satisfaction || satisfaction.length === 0) {
        return <div className="p-4 text-center">اطلاعاتی برای نمایش وجود ندارد</div>;
    }

    return (
        <div className="space-y-6">
            <div className="h-[400px]">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={satisfaction}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="specialist_name" />
                        <YAxis yAxisId="left" orientation="left" stroke="#8884d8" />
                        <YAxis yAxisId="right" orientation="right" stroke="#82ca9d" />
                        <Tooltip />
                        <Legend />
                        <Bar yAxisId="left" dataKey="average_rating" name="میانگین رتبه" fill="#8884d8" />
                        <Bar yAxisId="right" dataKey="satisfaction_rate" name="درصد رضایت" fill="#82ca9d" />
                    </BarChart>
                </ResponsiveContainer>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام متخصص</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">میانگین رتبه</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد نظرات</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">درصد رضایت</th>
                    </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                    {satisfaction.map((item, index) => (
                        <tr key={index} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap">{item.specialist_name}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{item.average_rating}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{item.total_ratings}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{item.satisfaction_rate}%</td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default CustomerReports;
