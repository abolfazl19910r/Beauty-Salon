// resources/js/Components/Admin/Reports/FinancialReports.jsx
import React, { useState, useEffect } from 'react';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, TrendingUp, TrendingDown } from 'lucide-react';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';

const FinancialReports = ({ reportType, startDate, endDate, baseUrl, data }) => {
    const [financialData, setFinancialData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const colors = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

    useEffect(() => {
        if (data) {
            handleReceivedData(data);
            return;
        }

        fetchData();
    }, [reportType, startDate, endDate, data]);

    const handleReceivedData = (data) => {
        if (data && (data.summary || data.monthly_breakdown)) {
            setFinancialData(data);
        } else if (data && data.data) {
            setFinancialData(data.data);
        } else {
            console.error('Invalid data format received:', data);
            setError('ساختار داده‌های دریافتی معتبر نیست');
            setFinancialData(null);
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

            const url = `${baseUrl}/admin/reports/financial?${params}`;
            console.log('Fetching financial data from:', url);

            const response = await fetchWithErrorHandling(url);
            handleReceivedData(response);
        } catch (error) {
            console.error('Error fetching financial data:', error);
            setError('خطا در دریافت اطلاعات: ' + error.message);
            setFinancialData(null);
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

    const formatCurrency = (value) => {
        if (value == null) return '0';
        return new Intl.NumberFormat('fa-IR').format(value) + ' تومان';
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

    if (!financialData) {
        return <div className="p-4 text-center">اطلاعاتی برای نمایش وجود ندارد</div>;
    }

    const { summary, monthly_breakdown, service_revenue, payment_breakdown, trends } = financialData;

    return (
        <div className="space-y-6">
            {/* خلاصه آمار مالی */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className="p-4 bg-blue-50">
                    <div className="text-sm text-gray-500 mb-1">کل درآمد</div>
                    <div className="text-xl font-bold">{formatCurrency(summary?.total_revenue)}</div>
                    {trends && (
                        <div className="flex items-center mt-2 text-sm">
                            {trends.revenue_change >= 0 ? (
                                <TrendingUp className="w-4 h-4 text-green-500 mr-1" />
                            ) : (
                                <TrendingDown className="w-4 h-4 text-red-500 mr-1" />
                            )}
                            <span className={trends.revenue_change >= 0 ? 'text-green-500' : 'text-red-500'}>
                                {Math.abs(trends.revenue_change)}%
                            </span>
                            <span className="text-gray-500 mr-1">نسبت به دوره قبل</span>
                        </div>
                    )}
                </Card>

                <Card className="p-4 bg-green-50">
                    <div className="text-sm text-gray-500 mb-1">میانگین هر نوبت</div>
                    <div className="text-xl font-bold">{formatCurrency(summary?.average_booking_value)}</div>
                </Card>

                <Card className="p-4 bg-yellow-50">
                    <div className="text-sm text-gray-500 mb-1">درآمد معلق</div>
                    <div className="text-xl font-bold">{formatCurrency(summary?.pending_payments)}</div>
                </Card>

                <Card className="p-4 bg-red-50">
                    <div className="text-sm text-gray-500 mb-1">مبلغ استرداد شده</div>
                    <div className="text-xl font-bold">{formatCurrency(summary?.refunded_amount)}</div>
                </Card>
            </div>

            {/* نمودار گردش مالی ماهانه */}
            {monthly_breakdown && monthly_breakdown.length > 0 && (
                <Card className="p-4">
                    <h3 className="text-lg font-medium mb-4">گردش مالی ماهانه</h3>
                    <div className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={monthly_breakdown}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" tickFormatter={(value) => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'][value - 1]} />
                                <YAxis yAxisId="left" orientation="left" />
                                <YAxis yAxisId="right" orientation="right" />
                                <Tooltip formatter={(value) => formatCurrency(value)} />
                                <Legend />
                                <Bar yAxisId="left" dataKey="revenue" name="درآمد" fill="#8884d8" />
                                <Bar yAxisId="right" dataKey="bookings" name="تعداد نوبت" fill="#82ca9d" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </Card>
            )}

            {/* نمودار درآمد خدمات */}
            {service_revenue && service_revenue.length > 0 && (
                <Card className="p-4">
                    <h3 className="text-lg font-medium mb-4">درآمد به تفکیک خدمات</h3>
                    <div className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={service_revenue.slice(0, 5)}
                                    cx="50%"
                                    cy="50%"
                                    labelLine={false}
                                    outerRadius={80}
                                    fill="#8884d8"
                                    dataKey="revenue"
                                    nameKey="name"
                                    label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                >
                                    {service_revenue.slice(0, 5).map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
                                    ))}
                                </Pie>
                                <Tooltip formatter={(value) => formatCurrency(value)} />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </Card>
            )}

            {/* جدول درآمد خدمات */}
            {service_revenue && service_revenue.length > 0 && (
                <Card className="p-4">
                    <h3 className="text-lg font-medium mb-4">خدمات برتر از نظر درآمد</h3>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام خدمت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد نوبت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">درآمد کل</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">میانگین هر نوبت</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {service_revenue.map((service, index) => (
                                <tr key={index} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap">{service.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{service.bookings}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{formatCurrency(service.revenue)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{formatCurrency(service.average_revenue)}</td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}

            {/* نمودار روش‌های پرداخت */}
            {payment_breakdown && payment_breakdown.length > 0 && (
                <Card className="p-4">
                    <h3 className="text-lg font-medium mb-4">روش‌های پرداخت</h3>
                    <div className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={payment_breakdown}
                                    cx="50%"
                                    cy="50%"
                                    labelLine={false}
                                    outerRadius={80}
                                    fill="#8884d8"
                                    dataKey="total_amount"
                                    nameKey="payment_method"
                                    label={({ payment_method, percent }) => `${payment_method}: ${(percent * 100).toFixed(0)}%`}
                                >
                                    {payment_breakdown.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
                                    ))}
                                </Pie>
                                <Tooltip formatter={(value) => formatCurrency(value)} />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </Card>
            )}
        </div>
    );
};

export default FinancialReports;
