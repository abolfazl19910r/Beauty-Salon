import React, { useState, useEffect } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const ManageWorkSchedule = ({ specialist }) => {
    const [schedule, setSchedule] = useState({
        work_days: [],
        start_time: '09:00',
        end_time: '17:00'
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const weekDays = [
        { id: 0, name: 'یکشنبه', shortName: 'ی' },
        { id: 1, name: 'دوشنبه', shortName: 'د' },
        { id: 2, name: 'سه‌شنبه', shortName: 'س' },
        { id: 3, name: 'چهارشنبه', shortName: 'چ' },
        { id: 4, name: 'پنج‌شنبه', shortName: 'پ' },
        { id: 5, name: 'جمعه', shortName: 'ج' },
        { id: 6, name: 'شنبه', shortName: 'ش' }
    ];

    useEffect(() => {
        fetchSchedule();
    }, [specialist]);

    const fetchSchedule = async () => {
        try {
            const response = await fetch(`/api/specialists/${specialist.id}/schedule`);
            const data = await response.json();
            if (data) {
                setSchedule(data);
            }
            setLoading(false);
        } catch (err) {
            setError('خطا در دریافت برنامه کاری');
            setLoading(false);
        }
    };

    const handleSave = async () => {
        try {
            setSaving(true);
            setError(null);
            setSuccess(false);

            const response = await fetch(`/api/specialists/${specialist.id}/schedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(schedule)
            });

            if (!response.ok) {
                throw new Error('خطا در ذخیره برنامه کاری');
            }

            setSuccess(true);
            setTimeout(() => setSuccess(false), 3000);
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    const generateTimeSlots = () => {
        const slots = [];
        for (let i = 0; i < 24; i++) {
            const hour = i.toString().padStart(2, '0');
            slots.push(`${hour}:00`);
            slots.push(`${hour}:30`);
        }
        return slots;
    };

    if (loading) {
        return (
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
        );
    }

    return (
        <div className="space-y-6">
            {/* روزهای کاری */}
            <div>
                <h3 className="text-lg font-semibold mb-4">روزهای کاری</h3>
                <div className="flex flex-wrap gap-2">
                    {weekDays.map(day => (
                        <button
                            key={day.id}
                            onClick={() => {
                                const newDays = schedule.work_days.includes(day.id)
                                    ? schedule.work_days.filter(d => d !== day.id)
                                    : [...schedule.work_days, day.id];
                                setSchedule({ ...schedule, work_days: newDays });
                            }}
                            className={`
                flex flex-col items-center justify-center
                w-16 h-16 rounded-lg transition-colors
                ${schedule.work_days.includes(day.id)
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            }
              `}
                            title={day.name}
                        >
                            <span className="text-lg font-semibold">{day.shortName}</span>
                            <span className="text-xs mt-1">{day.name}</span>
                        </button>
                    ))}
                </div>
            </div>

            {/* ساعات کاری */}
            <div>
                <h3 className="text-lg font-semibold mb-4">ساعات کاری</h3>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">ساعت شروع</label>
                        <select
                            value={schedule.start_time}
                            onChange={e => setSchedule({ ...schedule, start_time: e.target.value })}
                            className="w-full border rounded-lg p-2"
                        >
                            {generateTimeSlots().map(time => (
                                <option
                                    key={time}
                                    value={time}
                                    disabled={time >= schedule.end_time}
                                >
                                    {time}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">ساعت پایان</label>
                        <select
                            value={schedule.end_time}
                            onChange={e => setSchedule({ ...schedule, end_time: e.target.value })}
                            className="w-full border rounded-lg p-2"
                        >
                            {generateTimeSlots().map(time => (
                                <option
                                    key={time}
                                    value={time}
                                    disabled={time <= schedule.start_time}
                                >
                                    {time}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            {/* پیام‌های خطا و موفقیت */}
            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {success && (
                <Alert className="bg-green-50 text-green-800">
                    <AlertDescription>برنامه کاری با موفقیت ذخیره شد</AlertDescription>
                </Alert>
            )}

            {/* دکمه ذخیره */}
            <div className="flex justify-end">
                <button
                    onClick={handleSave}
                    disabled={saving}
                    className={`
            px-4 py-2 rounded-lg text-white
            ${saving ? 'bg-gray-400' : 'bg-blue-500 hover:bg-blue-600'}
          `}
                >
                    {saving ? (
                        <div className="flex items-center">
                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2" />
                            در حال ذخیره...
                        </div>
                    ) : (
                        'ذخیره تغییرات'
                    )}
                </button>
            </div>
        </div>
    );
};

export default ManageWorkSchedule;
