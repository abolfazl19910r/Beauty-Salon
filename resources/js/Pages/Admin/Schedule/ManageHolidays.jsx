import React, { useState, useEffect } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const ManageHolidays = ({ specialist }) => {
    const [holidays, setHolidays] = useState([]);
    const [newHoliday, setNewHoliday] = useState({
        date: '',
        description: ''
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchHolidays();
    }, [specialist]);

    const fetchHolidays = async () => {
        try {
            const response = await fetch(`/api/specialists/${specialist.id}/holidays`);
            const data = await response.json();
            setHolidays(data);
            setLoading(false);
        } catch (err) {
            setError('خطا در دریافت لیست تعطیلات');
            setLoading(false);
        }
    };

    const addHoliday = async () => {
        try {
            if (!newHoliday.date) {
                setError('لطفاً تاریخ را انتخاب کنید');
                return;
            }

            const response = await fetch(`/api/specialists/${specialist.id}/holidays`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(newHoliday)
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'خطا در ثبت تعطیلی');
            }

            const holiday = await response.json();
            setHolidays([...holidays, holiday]);
            setNewHoliday({ date: '', description: '' });
            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const deleteHoliday = async (holidayId) => {
        try {
            const response = await fetch(
                `/api/specialists/${specialist.id}/holidays/${holidayId}`,
                {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            );

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'خطا در حذف تعطیلی');
            }

            setHolidays(holidays.filter(h => h.id !== holidayId));
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
        );
    }

    return (
        <div className="space-y-6">
            {/* افزودن تعطیلی جدید */}
            <div className="bg-gray-50 p-4 rounded-lg">
                <h3 className="text-lg font-semibold mb-4">افزودن تعطیلی جدید</h3>
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium mb-1">تاریخ</label>
                        <input
                            type="date"
                            value={newHoliday.date}
                            onChange={e => setNewHoliday({ ...newHoliday, date: e.target.value })}
                            min={new Date().toISOString().split('T')[0]}
                            className="w-full border rounded-lg p-2"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">توضیحات</label>
                        <input
                            type="text"
                            value={newHoliday.description}
                            onChange={e => setNewHoliday({ ...newHoliday, description: e.target.value })}
                            placeholder="توضیحات (اختیاری)"
                            className="w-full border rounded-lg p-2"
                        />
                    </div>
                </div>
                <button
                    onClick={addHoliday}
                    className="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
                >
                    افزودن تعطیلی
                </button>
            </div>

            {/* پیام خطا */}
            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {/* لیست تعطیلات */}
            <div className="bg-white rounded-lg shadow divide-y">
                <h3 className="text-lg font-semibold p-4">لیست تعطیلات</h3>

                {holidays.length === 0 ? (
                    <div className="p-4 text-center text-gray-500">
                        تعطیلی ثبت شده‌ای وجود ندارد
                    </div>
                ) : (
                    holidays.map(holiday => (
                        <div
                            key={holiday.id}
                            className="p-4 flex items-center justify-between hover:bg-gray-50"
                        >
                            <div>
                                <div className="font-medium">
                                    {new Date(holiday.date).toLocaleDateString('fa-IR')}
                                </div>
                                {holiday.description && (
                                    <div className="text-sm text-gray-500">
                                        {holiday.description}
                                    </div>
                                )}
                            </div>
                            <button
                                onClick={() => deleteHoliday(holiday.id)}
                                className="p-1 text-red-500 hover:bg-red-50 rounded-full"
                                title="حذف تعطیلی"
                            >
                                ✕
                            </button>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};

export default ManageHolidays;
