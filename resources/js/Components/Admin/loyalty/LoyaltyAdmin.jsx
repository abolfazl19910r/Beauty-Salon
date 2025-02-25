import React, { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Alert } from '@/components/ui/alert';

const LoyaltyAdmin = () => {
    const [rewards, setRewards] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [newReward, setNewReward] = useState({
        title: '',
        description: '',
        required_points: 0,
        discount_type: 'fixed',
        discount_amount: 0,
        max_uses: 1,
        is_active: true
    });

    useEffect(() => {
        fetchRewards();
    }, []);

    const fetchRewards = async () => {
        try {
            setLoading(true);
            const response = await fetch('/api/admin/rewards');
            if (!response.ok) throw new Error('خطا در دریافت لیست پاداش‌ها');
            const data = await response.json();
            setRewards(data);
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
            const response = await fetch('/api/admin/rewards', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(newReward)
            });

            if (!response.ok) throw new Error('خطا در ایجاد پاداش جدید');

            await fetchRewards();
            setNewReward({
                title: '',
                description: '',
                required_points: 0,
                discount_type: 'fixed',
                discount_amount: 0,
                max_uses: 1,
                is_active: true
            });

        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این پاداش اطمینان دارید؟')) return;

        try {
            const response = await fetch(`/api/admin/rewards/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) throw new Error('خطا در حذف پاداش');

            await fetchRewards();
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        );
    }

    return (
        <div className="space-y-6 p-6">
            <h2 className="text-2xl font-bold">مدیریت برنامه وفاداری</h2>

            {error && (
                <Alert variant="destructive">
                    <p>{error}</p>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>افزودن پاداش جدید</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block mb-2">عنوان</label>
                                <input
                                    type="text"
                                    value={newReward.title}
                                    onChange={e => setNewReward({...newReward, title: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2">امتیاز مورد نیاز</label>
                                <input
                                    type="number"
                                    value={newReward.required_points}
                                    onChange={e => setNewReward({...newReward, required_points: parseInt(e.target.value)})}
                                    className="w-full border rounded px-3 py-2"
                                    min="0"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block mb-2">توضیحات</label>
                            <textarea
                                value={newReward.description}
                                onChange={e => setNewReward({...newReward, description: e.target.value})}
                                className="w-full border rounded px-3 py-2"
                                rows="3"
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block mb-2">نوع تخفیف</label>
                                <select
                                    value={newReward.discount_type}
                                    onChange={e => setNewReward({...newReward, discount_type: e.target.value})}
                                    className="w-full border rounded px-3 py-2"
                                >
                                    <option value="fixed">مبلغ ثابت</option>
                                    <option value="percentage">درصدی</option>
                                </select>
                            </div>
                            <div>
                                <label className="block mb-2">مقدار تخفیف</label>
                                <input
                                    type="number"
                                    value={newReward.discount_amount}
                                    onChange={e => setNewReward({...newReward, discount_amount: parseInt(e.target.value)})}
                                    className="w-full border rounded px-3 py-2"
                                    min="0"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block mb-2">حداکثر استفاده</label>
                                <input
                                    type="number"
                                    value={newReward.max_uses}
                                    onChange={e => setNewReward({...newReward, max_uses: parseInt(e.target.value)})}
                                    className="w-full border rounded px-3 py-2"
                                    min="1"
                                    required
                                />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                checked={newReward.is_active}
                                onChange={e => setNewReward({...newReward, is_active: e.target.checked})}
                                className="ml-2"
                            />
                            <label>فعال</label>
                        </div>

                        <div>
                            <button
                                type="submit"
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                            >
                                افزودن پاداش
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>لیست پاداش‌ها</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عنوان</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">امتیاز مورد نیاز</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">نوع تخفیف</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">مقدار تخفیف</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">وضعیت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عملیات</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {rewards.map(reward => (
                                <tr key={reward.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">{reward.title}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{reward.required_points}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {reward.discount_type === 'fixed' ? 'مبلغ ثابت' : 'درصدی'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">{reward.discount_amount}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                reward.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {reward.is_active ? 'فعال' : 'غیرفعال'}
                                            </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            onClick={() => handleDelete(reward.id)}
                                            className="text-red-600 hover:text-red-900"
                                        >
                                            حذف
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

export default LoyaltyAdmin;
