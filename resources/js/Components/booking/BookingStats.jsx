import React, { useState, useEffect } from 'react';

const BookingStatsCard = ({ title, count, color }) => (
    <div className={`bg-${color}-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300`}>
        <span className={`text-xs text-${color}-500`}>{title}</span>
        <div className={`text-xl font-bold text-${color}-700 persian-number`}>{count}</div>
    </div>
);

const BookingStats = ({ initialStats = null, date = null }) => {
    const [stats, setStats] = useState(initialStats || {
        total: 0,
        confirmed: 0,
        cancelled: 0
    });
    const [loading, setLoading] = useState(!initialStats);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (initialStats) return;

        const fetchStats = async () => {
            try {
                setLoading(true);
                const url = `/api/admin/bookings/stats${date ? `?date=${date}` : ''}`;
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('خطا در دریافت اطلاعات از سرور');
                }

                const data = await response.json();
                setStats(data);
                setError(null);
            } catch (err) {
                console.error('خطا در دریافت آمار نوبت‌ها:', err);
                setError('خطا در دریافت اطلاعات آماری');
            } finally {
                setLoading(false);
            }
        };

        fetchStats();
    }, [date]);

    if (loading) {
        return (
            <div className="flex justify-center items-center p-4">
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="text-red-500 text-sm p-2">
                {error}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-3 gap-3">
            <BookingStatsCard
                title="کل نوبت‌ها"
                count={stats.total}
                color="blue"
            />
            <BookingStatsCard
                title="تایید شده"
                count={stats.confirmed}
                color="green"
            />
            <BookingStatsCard
                title="لغو شده"
                count={stats.cancelled}
                color="red"
            />
        </div>
    );
};

export default BookingStats;
