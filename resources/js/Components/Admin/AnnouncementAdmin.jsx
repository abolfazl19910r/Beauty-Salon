// resources/js/components/admin/AnnouncementAdmin.jsx
import {useEffect, useState} from "react";

const AnnouncementAdmin = () => {
    const [announcements, setAnnouncements] = useState([]);
    const [newAnnouncement, setNewAnnouncement] = useState({
        title: '',
        content: '',
        is_active: true,
        published_at: new Date().toISOString().slice(0, 16)
    });

    useEffect(() => {
        fetchAnnouncements();
    }, []);

    const fetchAnnouncements = async () => {
        try {
            const response = await fetch('/api/admin/announcements');
            const data = await response.json();
            setAnnouncements(data);
        } catch (error) {
            console.error('Error fetching announcements:', error);
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

            if (response.ok) {
                fetchAnnouncements();
                setNewAnnouncement({
                    title: '',
                    content: '',
                    is_active: true,
                    published_at: new Date().toISOString().slice(0, 16)
                });
            }
        } catch (error) {
            console.error('Error creating announcement:', error);
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

            if (response.ok) {
                fetchAnnouncements();
            }
        } catch (error) {
            console.error('Error deleting announcement:', error);
        }
    };

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold">مدیریت اعلانات</h2>

            {/* فرم افزودن اعلان جدید */}
            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow">
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

                <div className="mt-4">
                    <label className="block mb-2">محتوا</label>
                    <textarea
                        value={newAnnouncement.content}
                        onChange={e => setNewAnnouncement({...newAnnouncement, content: e.target.value})}
                        className="w-full border rounded px-3 py-2"
                        rows="4"
                        required
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 mt-4">
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
                    <div className="flex items-center">
                        <input
                            type="checkbox"
                            checked={newAnnouncement.is_active}
                            onChange={e => setNewAnnouncement({...newAnnouncement, is_active: e.target.checked})}
                            className="ml-2"
                        />
                        <label>فعال</label>
                    </div>
                </div>

                <div className="mt-6">
                    <button type="submit" className="bg-blue-500 text-white px-4 py-2 rounded">
                        افزودن اعلان
                    </button>
                </div>
            </form>

            {/* لیست اعلانات */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full">
                    <thead>
                    <tr className="bg-gray-50">
                        <th className="px-6 py-3 text-right">عنوان</th>
                        <th className="px-6 py-3">تاریخ انتشار</th>
                        <th className="px-6 py-3">وضعیت</th>
                        <th className="px-6 py-3">عملیات</th>
                    </tr>
                    </thead>
                    <tbody className="divide-y">
                    {announcements.map(announcement => (
                        <tr key={announcement.id}>
                            <td className="px-6 py-4">{announcement.title}</td>
                            <td className="px-6 py-4" dir="ltr">
                                {new Date(announcement.published_at).toLocaleString('fa-IR')}
                            </td>
                            <td className="px-6 py-4">
                                    <span className={`px-2 py-1 rounded-full ${
                                        announcement.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    }`}>
                                        {announcement.is_active ? 'فعال' : 'غیرفعال'}
                                    </span>
                            </td>
                            <td className="px-6 py-4">
                                <button
                                    onClick={() => handleDelete(announcement.id)}
                                    className="text-red-500 hover:text-red-700">
                                    حذف
                                </button>
                            </td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export { LoyaltyAdmin, AnnouncementAdmin };
