import React, {useState, useEffect} from 'react';
import {Card, CardHeader, CardTitle, CardContent} from '@/components/ui/card';
import {Alert} from '@/components/ui/alert';

const LoyaltyAdmin = () => {
    const [rewards, setRewards] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
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

        const flashSuccess = document.querySelector('.bg-green-50');
        const flashError = document.querySelector('.bg-red-50');

        if (flashSuccess) {
            setSuccess(flashSuccess.textContent.trim());
            flashSuccess.style.display = 'none';
        }

        if (flashError) {
            setError(flashError.textContent.trim());
            flashError.style.display = 'none';
        }
    }, []);

    const fetchRewards = async () => {
        try {
            setLoading(true);
            const response = await fetch(window.initialData.routes.rewards);
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
            setLoading(true);

            const response = await fetch(window.initialData.routes.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(newReward)
            });

            const contentType = response.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'خطا در ایجاد پاداش جدید');
                }

                setSuccess('پاداش با موفقیت ایجاد شد');
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
            } else {
                await fetchRewards();
                setSuccess('پاداش با موفقیت ایجاد شد');

                setNewReward({
                    title: '',
                    description: '',
                    required_points: 0,
                    discount_type: 'fixed',
                    discount_amount: 0,
                    max_uses: 1,
                    is_active: true
                });
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('آیا از حذف این پاداش اطمینان دارید؟')) return;

        try {
            setLoading(true);
            const response = await fetch(`${window.initialData.routes.destroy.replace(':id', id)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'خطا در حذف پاداش');
            }

            setSuccess('پاداش با موفقیت حذف شد');
            await fetchRewards();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (success) {
            const timer = setTimeout(() => {
                setSuccess(null);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [success]);

    useEffect(() => {
        if (error) {
            const timer = setTimeout(() => {
                setError(null);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [error]);

    if (loading && rewards.length === 0) {
        return (
            <div className="flex justify-center items-center min-h-[400px]">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span className="mr-2 text-gray-500">در حال بارگذاری...</span>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <h2 className="text-2xl font-bold">مدیریت برنامه وفاداری</h2>

            {error && (
                <div className="bg-red-50 border-r-4 border-red-500 p-4 text-red-800 rounded-lg shadow-sm" role="alert">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 fill="currentColor">
                                <path fillRule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                      clipRule="evenodd"/>
                            </svg>
                        </div>
                        <div className="mr-3">
                            <span>{error}</span>
                        </div>
                    </div>
                </div>
            )}

            {success && (
                <div className="bg-green-50 border-r-4 border-green-500 p-4 text-green-800 rounded-lg shadow-sm"
                     role="alert">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                      clipRule="evenodd"/>
                            </svg>
                        </div>
                        <div className="mr-3">
                            <span>{success}</span>
                        </div>
                    </div>
                </div>
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
                                    onChange={e => setNewReward({
                                        ...newReward,
                                        required_points: parseInt(e.target.value)
                                    })}
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
                                    onChange={e => setNewReward({
                                        ...newReward,
                                        discount_amount: parseInt(e.target.value)
                                    })}
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
                                className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition flex items-center"
                                disabled={loading}
                            >
                                {loading && (
                                    <div
                                        className="animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2"></div>
                                )}
                                افزودن پاداش
                            </button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row justify-between items-center">
                    <CardTitle>لیست پاداش‌ها</CardTitle>

                    <button
                        onClick={fetchRewards}
                        className="p-2 text-gray-600 hover:text-blue-600 transition-colors"
                        title="بازخوانی لیست"
                    >
                        <svg className="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path
                                d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                        </svg>
                    </button>
                </CardHeader>
                <CardContent>
                    {loading && rewards.length > 0 && (
                        <div className="flex justify-center items-center py-4">
                            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                            <span className="mr-2 text-gray-500">در حال بروزرسانی...</span>
                        </div>
                    )}

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عنوان</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">امتیاز
                                    مورد نیاز
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">نوع
                                    تخفیف
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">مقدار
                                    تخفیف
                                </th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">وضعیت</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 tracking-wider">عملیات</th>
                            </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                            {rewards.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                        هیچ پاداشی یافت نشد
                                    </td>
                                </tr>
                            ) : (
                                rewards.map(reward => (
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
                                            <a href={window.initialData.routes.edit?.replace(':id', reward.id)}
                                               className="text-blue-600 hover:text-blue-900 ml-4">
                                                ویرایش
                                            </a>
                                            <button
                                                onClick={() => handleDelete(reward.id)}
                                                className="text-red-600 hover:text-red-900"
                                                disabled={loading}
                                            >
                                                حذف
                                            </button>
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

export default LoyaltyAdmin;
