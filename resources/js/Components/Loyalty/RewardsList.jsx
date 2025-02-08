// resources/js/Components/Loyalty/RewardsList.jsx
import React, { useState, useEffect } from 'react';
import { Card, Button } from '@/components/ui';
import { Gift, AlertCircle } from 'lucide-react';
import { toast } from 'react-hot-toast';

const RewardsList = () => {
    const [rewards, setRewards] = useState([]);
    const [userPoints, setUserPoints] = useState(0);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchRewards();
    }, []);

    const fetchRewards = async () => {
        try {
            const response = await fetch('/api/loyalty/rewards');
            const data = await response.json();
            setRewards(data.rewards);
            setUserPoints(data.user_points);
        } catch (error) {
            console.error('Error fetching rewards:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleRedeem = async (rewardId) => {
        try {
            const response = await fetch(`/api/loyalty/rewards/${rewardId}/redeem`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok) {
                toast.success(
                    <div>
                        <p>پاداش با موفقیت دریافت شد</p>
                        <p className="text-sm mt-1">کد تخفیف: {data.discount_code}</p>
                    </div>
                );
                fetchRewards(); // بروزرسانی لیست
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            toast.error(error.message || 'خطا در دریافت پاداش');
        }
    };

    if (loading) {
        return <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {[1, 2, 3].map(i => (
                <div key={i} className="animate-pulse h-64 bg-gray-100 rounded-lg" />
            ))}
        </div>;
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {rewards.map(reward => (
                <Card key={reward.id} className="p-6">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <h3 className="font-bold">{reward.title}</h3>
                            <p className="text-sm text-gray-600">{reward.description}</p>
                        </div>
                        <Gift className="text-blue-500 w-6 h-6" />
                    </div>

                    <div className="space-y-2 mb-4">
                        <div className="text-sm">
                            <span className="font-medium">امتیاز مورد نیاز: </span>
                            <span className={userPoints >= reward.required_points ?
                                'text-green-600' : 'text-gray-600'}>
                                {new Intl.NumberFormat('fa-IR').format(reward.required_points)}
                            </span>
                        </div>

                        <div className="text-sm">
                            <span className="font-medium">تخفیف: </span>
                            {reward.discount_type === 'fixed' ?
                                `${new Intl.NumberFormat('fa-IR').format(reward.discount_amount)} تومان` :
                                `${reward.discount_amount}٪`
                            }
                        </div>

                        {reward.max_uses && (
                            <div className="text-sm text-gray-500">
                                {reward.max_uses - reward.used_count} عدد باقی مانده
                            </div>
                        )}
                    </div>

                    <Button
                        onClick={() => handleRedeem(reward.id)}
                        disabled={userPoints < reward.required_points}
                        className="w-full"
                        variant={userPoints >= reward.required_points ? "primary" : "secondary"}
                    >
                        {userPoints >= reward.required_points ?
                            'دریافت پاداش' :
                            `${new Intl.NumberFormat('fa-IR').format(reward.required_points - userPoints)} امتیاز کم دارید`
                        }
                    </Button>
                </Card>
            ))}
        </div>
    );
};

export default RewardsList;
