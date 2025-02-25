import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';
import { Bell, Loader, Trash2 } from 'lucide-react';

const AnnouncementAdmin = () => {
    const [announcements, setAnnouncements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [newAnnouncement, setNewAnnouncement] = useState({
        title: '',
        content: '',
        type: 'general',
        priority: 1,
        is_active: true,
        published_at: new Date().toISOString().slice(0, 16),
        expires_at: ''
    });

    useEffect(() => {
        fetchAnnouncements();
    }, []);

    const fetchAnnouncements = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/admin/announcements');
            if (!response.ok) throw new Error('خطا در دریافت اعلانات');
            const data = await response.json();
            setAnnouncements(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('/api/admin/announcements', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(newAnnouncement)
            });

            if (!response.ok) throw new Error('خطا در ایجاد اعلان جدید');

            await fetchAnnouncements();
            setNewAnnouncement({
                title: '',
                content: '',
                type: 'general',
                priority: 1,
                is_active: true,
                published_at: new Date().toISOString().slice(0, 16),
                expires_at: ''
            });
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این اعلان اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/api/admin/announcements/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('خطا در حذف اعلان');

            await fetchAnnouncements();
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <Loader className="animate-spin h-8 w-8 text-blue-500" />
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6">
            <div className="flex items-center gap-2">
                <Bell className="h-6 w-6" />
                <h2 className="text-2xl font-bold">مدیریت اعلانات</h2>
            </div>

            {error && (
                <Alert variant="destructive">
                    <p>{error}</p>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>افزودن اعلان جدید</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2">عنوان</label>
                                <input
                                    type="text"
                                    value={newAnnouncement.title}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, title: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2">نوع اعلان</label>
                                <select
                                    value={newAnnouncement.type}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, type: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                >
                                    <option value="general">عمومی</option>
                                    <option value="maintenance">تعمیرات</option>
                                    <option value="promotion">تبلیغاتی</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block mb-2">محتوا</label>
                            <textarea
                                value={newAnnouncement.content}
                                onChange={e => setNewAnnouncement({...newAnnouncement, content: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                rows="4"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block mb-2">تاریخ انتشار</label>
                                <input
                                    type="datetime-local"
                                    value={newAnnouncement.published_at}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, published_at: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2">تاریخ انقضا</label>
                                <input
                                    type="datetime-local"
                                    value={newAnnouncement.expires_at}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, expires_at: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                />
                            </div>
                            <div>
                                <label className="block mb-2">اولویت</label>
                                <input
                                    type="number"
                                    value={newAnnouncement.priority}
                                    onChange={e => setNewAnnouncement({...newAnnouncement, priority: parseInt(e.target.value)})}
                                    className="w-full border rounded px-3 py-2"
                                    min="1"
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                checked={newAnnouncement.is_active}
                                onChange={e => setNewAnnouncement({...newAnnouncement, is_active: e.target.checked})}
                                className="ml-2"
                            />
                            <label>فعال</label>
                        </div>

                        <div>
                            <button
                                type="submit"
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                            >
                                افزودن اعلان
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>لیست اعلانات</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عنوان</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">نوع</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">تاریخ انتشار</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">تاریخ انقضا</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">وضعیت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عملیات</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {announcements.map(announcement => (
                                <tr key={announcement.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">{announcement.title}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {announcement.type === 'general' && 'عمومی'}
                                        {announcement.type === 'maintenance' && 'تعمیرات'}
                                        {announcement.type === 'promotion' && 'تبلیغاتی'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap" dir="ltr">
                                        {new Date(announcement.published_at).toLocaleString('fa-IR')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap" dir="ltr">
                                        {announcement.expires_at
                                            ? new Date(announcement.expires_at).toLocaleString('fa-IR')
                                            : '-'
                                        }
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                announcement.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {announcement.is_active ? 'فعال' : 'غیرفعال'}
                                            </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            onClick={() => handleDelete(announcement.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            <Trash2 className="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default AnnouncementAdmin;
