import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Bell, Loader, Trash2, Calendar, TrendingUp, CheckCircle, Clock, XCircle, Edit } from 'lucide-react';

const getPriorityBadge = (priority) => {
    if (priority >= 100) {
        return {
            color: 'bg-red-600 text-white',
            label: 'بحرانی',
            icon: '🚨'
        };
    } else if (priority >= 71) {
        return {
            color: 'bg-red-500 text-white',
            label: 'فوری',
            icon: '⚠️'
        };
    } else if (priority >= 31) {
        return {
            color: 'bg-orange-500 text-white',
            label: 'خیلی مهم',
            icon: '⭐'
        };
    } else if (priority >= 11) {
        return {
            color: 'bg-yellow-500 text-white',
            label: 'مهم',
            icon: '📌'
        };
    } else {
        return {
            color: 'bg-gray-400 text-white',
            label: 'عادی',
            icon: 'ℹ️'
        };
    }
};

const AnnouncementAdmin = () => {
    const [announcements, setAnnouncements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editingId, setEditingId] = useState(null);
    const [stats, setStats] = useState({
        total: 0,
        active: 0,
        pending: 0,
        expired: 0
    });
    const [newAnnouncement, setNewAnnouncement] = useState({
        title: '',
        content: '',
        type: 'general',
        priority: 1,
        is_active: true,
        published_at: '',
        expires_at: ''
    });

    useEffect(() => {
        fetchData();
    }, []);

    useEffect(() => {
        if (!loading) {
            const timer = setTimeout(() => {
                initializeDatePickers();
            }, 300);

            return () => clearTimeout(timer);
        }
    }, [loading]);

    const fetchData = async () => {
        setLoading(true);
        await Promise.all([
            fetchStats(),
            fetchAnnouncements()
        ]);
        setLoading(false);
    };

    const fetchStats = async () => {
        try {
            const response = await fetch('/admin/announcements/stats', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('خطا در دریافت آمار');
            const data = await response.json();
            setStats(data);
        } catch (err) {
            console.error('Error fetching stats:', err);
        }
    };

    const fetchAnnouncements = async () => {
        try {
            const response = await fetch('/admin/announcements/list', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('خطا در دریافت اعلانات');
            const data = await response.json();
            setAnnouncements(Array.isArray(data) ? data : data.data || []);
            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const convertPersianToEnglish = (str) => {
        if (!str) return str;
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        let result = str;
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persianNumbers[i], 'g'), i.toString());
            result = result.replace(new RegExp(arabicNumbers[i], 'g'), i.toString());
        }
        return result;
    };

    const toJalali = (date) => {
        if (!date) return '-';
        try {
            return new Date(date).toLocaleDateString('fa-IR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return '-';
        }
    };

    const initializeDatePickers = () => {
        if (typeof $ === 'undefined' || !$.fn.persianDatepicker) {
            console.error('Persian DatePicker libraries not loaded');
            return;
        }

        const publishedInput = $('#published-date-picker');
        const expiresInput = $('#expires-date-picker');

        if (publishedInput.length === 0 || expiresInput.length === 0) {
            console.error('DatePicker inputs not found');
            return;
        }

        try {
            const datePickerConfig = {
                format: 'YYYY/MM/DD HH:mm',
                initialValue: false,
                initialValueType: 'persian',
                autoClose: true,
                persianDigit: true,
                timePicker: {
                    enabled: true,
                    meridiem: {
                        enabled: false
                    }
                },
                toolbox: {
                    enabled: true,
                    calendarSwitch: {
                        enabled: true,
                        format: 'MMMM'
                    },
                    todayButton: {
                        enabled: true,
                        text: {
                            fa: 'امروز'
                        }
                    },
                    submitButton: {
                        enabled: true,
                        text: {
                            fa: 'تایید'
                        }
                    },
                    calendarType: 'persian'
                },
                onSelect: function(unix) {
                    const gregorianDate = new persianDate(unix)
                        .toCalendar('gregorian')
                        .format('YYYY-MM-DD HH:mm:ss');
                    const cleanDate = convertPersianToEnglish(gregorianDate);

                    setNewAnnouncement(prev => ({
                        ...prev,
                        published_at: cleanDate
                    }));
                }
            };

            publishedInput.persianDatepicker(datePickerConfig);

            expiresInput.persianDatepicker({
                ...datePickerConfig,
                onSelect: function(unix) {
                    const gregorianDate = new persianDate(unix)
                        .toCalendar('gregorian')
                        .format('YYYY-MM-DD HH:mm:ss');
                    const cleanDate = convertPersianToEnglish(gregorianDate);

                    setNewAnnouncement(prev => ({
                        ...prev,
                        expires_at: cleanDate
                    }));
                }
            });

        } catch (error) {
            console.error('Error initializing DatePickers:', error);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            const persianPublished = convertPersianToEnglish(newAnnouncement.published_at);
            const persianExpires = newAnnouncement.expires_at ? convertPersianToEnglish(newAnnouncement.expires_at) : null;

            if (!persianPublished) {
                setError('لطفاً تاریخ انتشار را وارد کنید');
                return;
            }

            const cleanedData = {
                ...newAnnouncement,
                published_at: new Date(persianPublished).toISOString(),
                expires_at: persianExpires ? new Date(persianExpires).toISOString() : null
            };

            const response = await fetch('/admin/announcements/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(cleanedData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در ایجاد اعلان جدید');
            }

            await Promise.all([
                fetchStats(),
                fetchAnnouncements()
            ]);

            setNewAnnouncement({
                title: '',
                content: '',
                type: 'general',
                priority: 1,
                is_active: true,
                published_at: '',
                expires_at: ''
            });

            $('#published-date-picker').val('');
            $('#expires-date-picker').val('');

            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این اعلان اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/admin/announcements/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('خطا در حذف اعلان');

            await Promise.all([
                fetchStats(),
                fetchAnnouncements()
            ]);

            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const handleEdit = (announcement) => {
        setEditingId(announcement.id);
        setNewAnnouncement({
            title: announcement.title,
            content: announcement.content,
            type: announcement.type,
            priority: announcement.priority,
            is_active: announcement.is_active,
            published_at: announcement.published_at,
            expires_at: announcement.expires_at || ''
        });

        if (typeof persianDate !== 'undefined') {
            if (announcement.published_at) {
                const publishedDate = new Date(announcement.published_at);
                const jalaliPublished = new persianDate(publishedDate).format('YYYY/MM/DD HH:mm');
                $('#published-date-picker').val(jalaliPublished);
            }

            if (announcement.expires_at) {
                const expiresDate = new Date(announcement.expires_at);
                const jalaliExpires = new persianDate(expiresDate).format('YYYY/MM/DD HH:mm');
                $('#expires-date-picker').val(jalaliExpires);
            }
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancelEdit = () => {
        setEditingId(null);
        setNewAnnouncement({
            title: '',
            content: '',
            type: 'general',
            priority: 1,
            is_active: true,
            published_at: '',
            expires_at: ''
        });
        $('#published-date-picker').val('');
        $('#expires-date-picker').val('');
    };

    const handleUpdate = async (e) => {
        e.preventDefault();

        try {
            const persianPublished = convertPersianToEnglish(newAnnouncement.published_at);
            const persianExpires = newAnnouncement.expires_at ? convertPersianToEnglish(newAnnouncement.expires_at) : null;

            if (!persianPublished) {
                setError('لطفاً تاریخ انتشار را وارد کنید');
                return;
            }

            const cleanedData = {
                ...newAnnouncement,
                published_at: new Date(persianPublished).toISOString(),
                expires_at: persianExpires ? new Date(persianExpires).toISOString() : null
            };

            const response = await fetch(`/admin/announcements/${editingId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(cleanedData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در ویرایش اعلان');
            }

            await Promise.all([
                fetchStats(),
                fetchAnnouncements()
            ]);

            handleCancelEdit();
            setError(null);
        } catch (err) {
            setError(err.message);
        }
    };

    const setTodayPublished = () => {
        if (typeof persianDate !== 'undefined') {
            const today = new persianDate();
            const todayStr = today.format('YYYY/MM/DD HH:mm');
            $('#published-date-picker').val(todayStr);

            const gregorian = today.toCalendar('gregorian').format('YYYY-MM-DD HH:mm:ss');
            const cleanDate = convertPersianToEnglish(gregorian);

            setNewAnnouncement(prev => ({
                ...prev,
                published_at: cleanDate
            }));
        }
    };

    const setTodayExpires = () => {
        if (typeof persianDate !== 'undefined') {
            const today = new persianDate();
            const todayStr = today.format('YYYY/MM/DD HH:mm');
            $('#expires-date-picker').val(todayStr);

            const gregorian = today.toCalendar('gregorian').format('YYYY-MM-DD HH:mm:ss');
            const cleanDate = convertPersianToEnglish(gregorian);

            setNewAnnouncement(prev => ({
                ...prev,
                expires_at: cleanDate
            }));
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <Loader className="animate-spin h-8 w-8 text-blue-500" />
                <span className="mr-3 text-gray-600">در حال بارگذاری...</span>
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6" dir="rtl">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Bell className="h-6 w-6 text-blue-500" />
                    <h2 className="text-2xl font-bold">مدیریت اعلانات</h2>
                </div>
                <a href="/admin" className="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg className="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت به داشبورد
                </a>
            </div>

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <p>{error}</p>
                </Alert>
            )}

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card className="bg-gradient-to-br from-blue-500 to-blue-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm opacity-80 mb-1">کل اعلانات</p>
                                <p className="text-3xl font-bold">{stats.total}</p>
                            </div>
                            <div className="bg-white bg-opacity-20 rounded-full p-3">
                                <TrendingUp className="h-8 w-8" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-br from-green-500 to-green-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm opacity-80 mb-1">فعال</p>
                                <p className="text-3xl font-bold">{stats.active}</p>
                            </div>
                            <div className="bg-white bg-opacity-20 rounded-full p-3">
                                <CheckCircle className="h-8 w-8" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm opacity-80 mb-1">در انتظار</p>
                                <p className="text-3xl font-bold">{stats.pending}</p>
                            </div>
                            <div className="bg-white bg-opacity-20 rounded-full p-3">
                                <Clock className="h-8 w-8" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-gradient-to-br from-red-500 to-red-600 text-white border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm opacity-80 mb-1">منقضی شده</p>
                                <p className="text-3xl font-bold">{stats.expired}</p>
                            </div>
                            <div className="bg-white bg-opacity-20 rounded-full p-3">
                                <XCircle className="h-8 w-8" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>
                            {editingId ? 'ویرایش اعلان' : 'افزودن اعلان جدید'}
                        </CardTitle>
                        {editingId && (
                            <button
                                onClick={handleCancelEdit}
                                className="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1"
                            >
                                <XCircle className="h-4 w-4" />
                                انصراف
                            </button>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    <form onSubmit={editingId ? handleUpdate : handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">
                                    عنوان <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={newAnnouncement.title}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, title: e.target.value})}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="عنوان اعلان را وارد کنید"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">
                                    نوع اعلان <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={newAnnouncement.type}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, type: e.target.value})}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="general">عمومی</option>
                                    <option value="maintenance">تعمیرات</option>
                                    <option value="promotion">تبلیغاتی</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium text-gray-700">
                                محتوا <span className="text-red-500">*</span>
                            </label>
                            <textarea
                                value={newAnnouncement.content}
                                onChange={e => setNewAnnouncement({...newAnnouncement, content: e.target.value})}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                rows="4"
                                placeholder="محتوای اعلان را وارد کنید"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">
                                    <Calendar className="inline h-4 w-4 ml-1" />
                                    تاریخ انتشار <span className="text-red-500">*</span>
                                </label>
                                <div className="flex gap-2">
                                    <div className="relative flex-1">
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none z-10">
                                            <Calendar className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            id="published-date-picker"
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer"
                                            placeholder="کلیک کنید برای انتخاب تاریخ"
                                            readOnly
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={setTodayPublished}
                                        className="px-3 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded-lg text-sm whitespace-nowrap transition"
                                    >
                                        امروز
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">
                                    <Calendar className="inline h-4 w-4 ml-1" />
                                    تاریخ انقضا
                                </label>
                                <div className="flex gap-2">
                                    <div className="relative flex-1">
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none z-10">
                                            <Calendar className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            id="expires-date-picker"
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer"
                                            placeholder="کلیک کنید برای انتخاب تاریخ"
                                            readOnly
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={setTodayExpires}
                                        className="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm whitespace-nowrap transition"
                                    >
                                        امروز
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">
                                    اولویت <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    value={newAnnouncement.priority}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, priority: parseInt(e.target.value)})}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    min="0"
                                    placeholder="مثلاً: 1"
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={newAnnouncement.is_active}
                                onChange={e => setNewAnnouncement({...newAnnouncement, is_active: e.target.checked})}
                                className="ml-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                                فعال بودن اعلان
                            </label>
                        </div>

                        <div className="flex justify-end gap-2">
                            {editingId && (
                                <button
                                    type="button"
                                    onClick={handleCancelEdit}
                                    className="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors"
                                >
                                    انصراف
                                </button>
                            )}
                            <button
                                type="submit"
                                className="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2"
                            >
                                <Bell className="h-4 w-4" />
                                {editingId ? 'به‌روزرسانی اعلان' : 'افزودن اعلان'}
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>لیست اعلانات ({announcements.length})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عنوان</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نوع</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ انتشار</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ انقضا</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اولویت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">وضعیت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {announcements.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="px-6 py-4 text-center text-gray-500">
                                        هیچ اعلانی یافت نشد
                                    </td>
                                </tr>
                            ) : (
                                announcements.map(announcement => (
                                    <tr key={announcement.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{announcement.title}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    ${announcement.type === 'general' ? 'bg-blue-100 text-blue-800' : ''}
                                                    ${announcement.type === 'maintenance' ? 'bg-orange-100 text-orange-800' : ''}
                                                    ${announcement.type === 'promotion' ? 'bg-green-100 text-green-800' : ''}
                                                `}>
                                                    {announcement.type === 'general' && 'عمومی'}
                                                    {announcement.type === 'maintenance' && 'تعمیرات'}
                                                    {announcement.type === 'promotion' && 'تبلیغاتی'}
                                                </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {toJalali(announcement.published_at)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {toJalali(announcement.expires_at)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            {(() => {
                                                const badge = getPriorityBadge(announcement.priority);
                                                return (
                                                    <span className={`px-3 py-1 rounded-full text-xs font-semibold ${badge.color} inline-flex items-center gap-1`}>
                                                        <span>{badge.icon}</span>
                                                        <span>{announcement.priority}</span>
                                                        <span className="text-xs opacity-90">({badge.label})</span>
                                                    </span>
                                                );
                                            })()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                    announcement.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {announcement.is_active ? 'فعال' : 'غیرفعال'}
                                                </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    onClick={() => handleEdit(announcement)}
                                                    className="text-blue-600 hover:text-blue-900 transition-colors"
                                                    title="ویرایش"
                                                >
                                                    <Edit className="h-5 w-5" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(announcement.id)}
                                                    className="text-red-600 hover:text-red-900 transition-colors"
                                                    title="حذف"
                                                >
                                                    <Trash2 className="h-5 w-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default AnnouncementAdmin;
