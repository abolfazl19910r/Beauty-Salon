// resources/js/Components/Admin/Reports/ReportDashboard.jsx
import React, { useState, useEffect } from 'react';
import RevenueCharts from '../Dashboard/RevenueCharts';
import PopularServices from '../Dashboard/PopularServices';
import CustomerReports from '../Dashboard/CustomerReports';
import FinancialReports from './FinancialReports';
import SpecialistReports from './SpecialistReports';
import PersianDatePicker from '../../Common/PersianDatePicker.jsx';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Download, AlertCircle } from 'lucide-react';
import { toGregorian, comparePersianDates } from '../../../Utils/DateUtils';

const ReportDashboard = ({ baseUrl, routes }) => {
    const [activeTab, setActiveTab] = useState('overview');
    const [reportType, setReportType] = useState('daily');
    const [dateRange, setDateRange] = useState({
        start: new Date().toISOString().split('T')[0],
        end: new Date().toISOString().split('T')[0]
    });
    const [persianDates, setPersianDates] = useState({
        start: '',
        end: ''
    });
    const [dateErrors, setDateErrors] = useState({
        start: '',
        end: ''
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [reportData, setReportData] = useState(null);

    const tabs = [
        { id: 'overview', name: 'نمای کلی', icon: '📊' },
        { id: 'financial', name: 'گزارش مالی', icon: '💰' },
        { id: 'specialists', name: 'عملکرد متخصصین', icon: '👥' },
        { id: 'customers', name: 'رضایت مشتریان', icon: '⭐' }
    ];

    const reportTypes = [
        { id: 'daily', label: 'روزانه' },
        { id: 'weekly', label: 'هفتگی' },
        { id: 'monthly', label: 'ماهانه' }
    ];

    const ROUTE_MAP = {
        'overview': reportType,
        'financial': 'financial',
        'specialists': 'specialist-performance',
        'customers': 'customer-satisfaction'
    };

    useEffect(() => {
        if (dateRange.start && dateRange.end) {
            fetchData();
        }
    }, [reportType, dateRange, activeTab]);

    const handleDateChange = (field, persianDate) => {
        setDateErrors(prev => ({ ...prev, [field]: '' }));

        setPersianDates(prev => ({
            ...prev,
            [field]: persianDate
        }));

        if (field === 'start' && persianDates.end) {
            if (comparePersianDates(persianDate, persianDates.end) > 0) {
                setDateErrors(prev => ({
                    ...prev,
                    start: 'تاریخ شروع نمی‌تواند بزرگتر از تاریخ پایان باشد'
                }));
                return;
            }
        }

        if (field === 'end' && persianDates.start) {
            if (comparePersianDates(persianDates.start, persianDate) > 0) {
                setDateErrors(prev => ({
                    ...prev,
                    end: 'تاریخ پایان نمی‌تواند کوچکتر از تاریخ شروع باشد'
                }));
                return;
            }
        }

        const gregorianDate = toGregorian(persianDate);
        setDateRange(prev => ({
            ...prev,
            [field]: gregorianDate
        }));
    };

    const fetchData = async () => {
        if (!baseUrl) {
            console.error("Base URL is missing");
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const endpointName = ROUTE_MAP[activeTab] || reportType;

            const params = new URLSearchParams({
                type: reportType,
                start_date: dateRange.start,
                end_date: dateRange.end
            });

            const url = `${baseUrl}/admin/reports/${endpointName}?${params}`;
            console.log('Fetching data from:', url);

            const response = await fetchWithErrorHandling(url);
            setReportData(response);

            console.log('Data received:', response);
        } catch (err) {
            setError('خطا در دریافت اطلاعات: ' + err.message);
            console.error('Error fetching data:', err);
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
                throw new Error(errorData.error || 'داده‌های ارسالی معتبر نیستند');
            } else if (response.status === 404) {
                throw new Error('مسیر مورد نظر یافت نشد');
            }

            throw new Error('خطا در دریافت اطلاعات');
        }

        return await response.json();
    };

    const handleExport = async (format) => {
        try {
            setLoading(true);

            const endpointName = ROUTE_MAP[activeTab] || reportType;

            const params = new URLSearchParams({
                type: reportType,
                start_date: dateRange.start,
                end_date: dateRange.end,
                format
            });

            const url = `${baseUrl}/admin/reports/export?${params}`;
            console.log('Exporting from:', url);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('خطا در دریافت فایل خروجی');
            }

            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `report-${reportType}-${format}.${format}`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);
        } catch (err) {
            setError('خطا در خروجی گرفتن از گزارش: ' + err.message);
            console.error('Export error:', err);
        } finally {
            setLoading(false);
        }
    };

    if (!baseUrl) {
        console.error('Required props missing: baseUrl is undefined');
        return (
            <div className="p-4 text-red-600 text-center">
                خطا در بارگذاری: اطلاعات مورد نیاز در دسترس نیست
            </div>
        );
    }

    return (
        <div className="p-6 space-y-6 rtl">
            {/* Header Section */}
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold text-gray-800">گزارش‌های مدیریتی</h1>
                <div className="flex gap-2">
                    <button
                        onClick={() => handleExport('pdf')}
                        className="px-4 py-2 bg-red-500 text-white rounded-lg flex items-center gap-2 hover:bg-red-600 transition-colors"
                        disabled={loading}
                    >
                        <Download className="w-5 h-5" />
                        <span>خروجی PDF</span>
                    </button>
                    <button
                        onClick={() => handleExport('excel')}
                        className="px-4 py-2 bg-green-500 text-white rounded-lg flex items-center gap-2 hover:bg-green-600 transition-colors"
                        disabled={loading}
                    >
                        <Download className="w-5 h-5" />
                        <span>خروجی Excel</span>
                    </button>
                </div>
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {/* Tabs Navigation */}
            <Card className="bg-white">
                <nav className="flex border-b" aria-label="Tabs">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`
                                relative min-w-0 flex-1 overflow-hidden py-4 px-4
                                ${activeTab === tab.id
                                ? 'text-blue-600 border-b-2 border-blue-500 font-medium'
                                : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            }
                                text-sm font-medium text-center
                                focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                            `}
                        >
                            <span className="ml-2">{tab.icon}</span>
                            <span>{tab.name}</span>
                        </button>
                    ))}
                </nav>
            </Card>

            {/* Filters Section */}
            <Card className="bg-white p-4">
                <div className="flex justify-between items-center">
                    <div className="flex gap-4">
                        {reportTypes.map((type) => (
                            <button
                                key={type.id}
                                onClick={() => setReportType(type.id)}
                                className={`
                                    px-4 py-2 rounded-lg text-sm font-medium
                                    ${reportType === type.id
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }
                                    transition-colors
                                `}
                            >
                                {type.label}
                            </button>
                        ))}
                    </div>

                    <div className="flex items-center gap-4">
                        <PersianDatePicker
                            label="از تاریخ"
                            value={persianDates.start}
                            onChange={(date) => handleDateChange('start', date)}
                            error={dateErrors.start}
                            maxDate={persianDates.end}
                        />
                        <PersianDatePicker
                            label="تا تاریخ"
                            value={persianDates.end}
                            onChange={(date) => handleDateChange('end', date)}
                            error={dateErrors.end}
                            minDate={persianDates.start}
                        />
                    </div>
                </div>
            </Card>

            {/* Content Section */}
            <div className="space-y-6">
                {loading ? (
                    <div className="text-center py-12">
                        <div className="text-gray-500">در حال بارگذاری...</div>
                    </div>
                ) : (
                    <>
                        {activeTab === 'overview' && (
                            <>
                                <Card className="bg-white p-6">
                                    <h3 className="text-lg font-medium mb-4">نمودار درآمد</h3>
                                    <RevenueCharts
                                        reportType={reportType}
                                        startDate={dateRange.start}
                                        endDate={dateRange.end}
                                        data={reportData}
                                        baseUrl={baseUrl}
                                    />
                                </Card>

                                <Card className="bg-white p-6">
                                    <h3 className="text-lg font-medium mb-4">خدمات محبوب</h3>
                                    <PopularServices baseUrl={baseUrl} />
                                </Card>
                            </>
                        )}

                        {activeTab === 'financial' && (
                            <Card className="bg-white p-6">
                                <FinancialReports
                                    reportType={reportType}
                                    startDate={dateRange.start}
                                    endDate={dateRange.end}
                                    data={reportData}
                                    baseUrl={baseUrl}
                                />
                            </Card>
                        )}

                        {activeTab === 'specialists' && (
                            <Card className="bg-white p-6">
                                <SpecialistReports
                                    reportType={reportType}
                                    startDate={dateRange.start}
                                    endDate={dateRange.end}
                                    data={reportData}
                                    baseUrl={baseUrl}
                                />
                            </Card>
                        )}

                        {activeTab === 'customers' && (
                            <Card className="bg-white p-6">
                                <CustomerReports
                                    reportType={reportType}
                                    startDate={dateRange.start}
                                    endDate={dateRange.end}
                                    data={reportData}
                                    baseUrl={baseUrl}
                                />
                            </Card>
                        )}
                    </>
                )}
            </div>
        </div>
    );
};

export default ReportDashboard;
