// resources/js/components/admin/LoyaltyAdmin.jsx
import React, { useState, useEffect } from 'react';

export const LoyaltyAdmin = () => {
    const [rewards, setRewards] = useState([]);
    const [newReward, setNewReward] = useState({
        title: '',
        description: '',
        required_points: 0,
        discount_type: 'fixed',
        discount_amount: 0
    });

    useEffect(() => {
        fetchRewards();
    }, []);

    const fetchRewards = async () => {
        try {
            const response = await fetch('/api/admin/rewards');
            const data = await response.json();
            setRewards(data);
        } catch (error) {
            console.error('Error fetching rewards:', error);
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

            if (response.ok) {
                fetchRewards();
                setNewReward({
                    title: '',
                    description: '',
                    required_points: 0,
                    discount_type: 'fixed',
                    discount_amount: 0
                });
            }
        } catch (error) {
            console.error('Error creating reward:', error);
        }
    };

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold">مدیریت پاداش‌ها</h2>

            {/* فرم افزودن پاداش جدید */}
            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow">
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
                            required
                        />
                    </div>
                </div>

                <div className="mt-4">
                    <label className="block mb-2">توضیحات</label>
                    <textarea
                        value={newReward.description}
                        onChange={e => setNewReward({...newReward, description: e.target.value})}
                        className="w-full border rounded px-3 py-2"
                        rows="3"
                        required
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 mt-4">
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
                            required
                        />
                    </div>
                </div>

                <div className="mt-6">
                    <button type="submit" className="bg-blue-500 text-white px-4 py-2 rounded">
                        افزودن پاداش
                    </button>
                </div>
            </form>

            {/* لیست پاداش‌ها */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <table className="min-w-full">
                    <thead>
                    <tr className="bg-gray-50">
                        <th className="px-6 py-3 text-right">عنوان</th>
                        <th className="px-6 py-3">امتیاز مورد نیاز</th>
                        <th className="px-6 py-3">نوع تخفیف</th>
                        <th className="px-6 py-3">مقدار تخفیف</th>
                        <th className="px-6 py-3">وضعیت</th>
                        <th className="px-6 py-3">عملیات</th>
                    </tr>
                    </thead>
                    <tbody className="divide-y">
                    {rewards.map(reward => (
                        <tr key={reward.id}>
                            <td className="px-6 py-4">{reward.title}</td>
                            <td className="px-6 py-4">{reward.required_points}</td>
                            <td className="px-6 py-4">
                                {reward.discount_type === 'fixed' ? 'مبلغ ثابت' : 'درصدی'}
                            </td>
                            <td className="px-6 py-4">{reward.discount_amount}</td>
                            <td className="px-6 py-4">
                                    <span className={`px-2 py-1 rounded-full ${
                                        reward.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    }`}>
                                        {reward.is_active ? 'فعال' : 'غیرفعال'}
                                    </span>
                            </td>
                            <td className="px-6 py-4">
                                <button
                                    onClick={() => handleDelete(reward.id)}
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

export class AnnouncementAdmin {
}
