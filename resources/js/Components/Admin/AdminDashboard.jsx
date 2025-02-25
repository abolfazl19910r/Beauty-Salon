import React, { useState, useEffect, useRef } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Users, Calendar, DollarSign, Scissors, ChevronUp, ChevronDown } from 'lucide-react';
import RevenueCharts from './Dashboard/RevenueCharts';
import PopularServices from './Dashboard/PopularServices';
import SpecialistStats from './Dashboard/SpecialistStats';
import DashboardService from '@/services/DashboardService';

const StatCard = ({ title, value, icon: Icon, change }) => (
    <Card>
        <CardContent className="p-6">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-500">{title}</p>
                    <h3 className="text-2xl font-bold mt-1">{value}</h3>
                    {change !== undefined && (
                        <div className="flex items-center mt-2">
                            {change >= 0 ? (
                                <ChevronUp className="w-4 h-4 text-green-500" />
                            ) : (
                                <ChevronDown className="w-4 h-4 text-red-500" />
                            )}
                            <span className={`text-sm ${change >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                                {Math.abs(change)}%
                            </span>
                        </div>
                    )}
                </div>
                <div className="p-4 bg-blue-50 rounded-full">
                    <Icon className="w-6 h-6 text-blue-500" />
                </div>
            </div>
        </CardContent>
    </Card>
);

const AdminDashboard = () => {
    const [stats, setStats] = useState({
        todayAppointments: 0,
        totalRevenue: 0,
        totalUsers: 0,
        totalSpecialists: 0,
        revenueChange: 0,
        usersChange: 0
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const revenueChartRef = useRef(null);
    const popularServicesRef = useRef(null);
    const specialistStatsRef = useRef(null);

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            console.log('Fetching dashboard data...');

            // در ابتدا به صورت مستقیم استفاده می‌کنیم تا مطمئن شویم API کار می‌کند
            const response = await fetch('/api/admin/dashboard');

            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }

            const data = await response.json();
            console.log('Dashboard data received:', data);

            setStats({
                todayAppointments: data.stats.todayBookings,
                totalRevenue: data.stats.totalRevenue,
                totalUsers: data.stats.totalUsers,
                totalSpecialists: data.stats.totalSpecialists,
                revenueChange: data.stats.revenueChange || 0,
                usersChange: data.stats.usersChange || 0
            });

            setError(null);
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            setError('خطا در بارگذاری اطلاعات داشبورد');
        } finally {
            setLoading(false);
        }
    };

    const handleRefresh = async () => {
        setLoading(true);
        try {
            await fetchDashboardData();

            if (revenueChartRef.current) revenueChartRef.current.refresh();
            if (popularServicesRef.current) popularServicesRef.current.refresh();
            if (specialistStatsRef.current) specialistStatsRef.current.refresh();
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
            setError('خطا در بروزرسانی اطلاعات');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="p-6 text-center text-red-600">
                <p>{error}</p>
                <button
                    onClick={handleRefresh}
                    className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                >
                    تلاش مجدد
                </button>
            </div>
        );
    }

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">داشبورد مدیریت</h1>
                <button
                    onClick={handleRefresh}
                    className="p-2 hover:bg-gray-100 rounded-full"
                    title="بروزرسانی"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    title="نوبت‌های امروز"
                    value={stats.todayAppointments}
                    icon={Calendar}
                />
                <StatCard
                    title="درآمد کل"
                    value={`${new Intl.NumberFormat('fa-IR').format(stats.totalRevenue)} تومان`}
                    icon={DollarSign}
                    change={stats.revenueChange}
                />
                <StatCard
                    title="کاربران"
                    value={stats.totalUsers}
                    icon={Users}
                    change={stats.usersChange}
                />
                <StatCard
                    title="متخصصین"
                    value={stats.totalSpecialists}
                    icon={Scissors}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>نمودار درآمد (۷ روز گذشته)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <RevenueCharts ref={revenueChartRef} className="h-80" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>خدمات محبوب</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <PopularServices ref={popularServicesRef} className="h-80" />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>آمار متخصصین</CardTitle>
                </CardHeader>
                <CardContent>
                    <SpecialistStats ref={specialistStatsRef} className="h-80" />
                </CardContent>
            </Card>
        </div>
    );
};

export default AdminDashboard;
