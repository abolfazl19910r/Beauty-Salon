// resources/js/Components/Loyalty/PointsOverview.jsx
import React, { useState, useEffect } from 'react';
import { Card, Progress, Badge } from '@/components/ui';
import { Coins, Award, Clock } from 'lucide-react';

const PointsOverview = () => {
    const [data, setData] = useState({
        points: 0,
        expiringPoints: 0,
        nextReward: null
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const response = await fetch('/api/loyalty/progress');
            const result = await response.json();
            setData(result);
        } catch (error) {
            console.error('Error fetching loyalty data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="animate-pulse h-48 bg-gray-100 rounded-lg" />;
    }

    return (
        <Card>
            <div className="p-6">
                <div className="flex justify-between items-start">
                    <div>
                        <h3 className="text-lg font-bold mb-1">امتیازات شما</h3>
                        <div className="text-3xl font-bold text-blue-600">
                            {new Intl.NumberFormat('fa-IR').format(data.points)}
                        </div>
                    </div>
                    <Coins className="text-blue-500 w-8 h-8" />
                </div>

                {data.expiringPoints > 0 && (
                    <div className="mt-4 flex items-center text-red-500">
                        <Clock className="w-4 h-4 mr-2" />
                        <span className="text-sm">
                            {new Intl.NumberFormat('fa-IR').format(data.expiringPoints)}
                            امتیاز در حال انقضا
                        </span>
                    </div>
                )}

                {data.nextReward && (
                    <div className="mt-6">
                        <div className="flex justify-between text-sm mb-2">
                            <span>تا پاداش بعدی: {data.nextReward.title}</span>
                            <span>
                                {new Intl.NumberFormat('fa-IR').format(data.nextReward.points_needed)}
                                امتیاز مانده
                            </span>
                        </div>
                        <Progress
                            value={data.nextReward.progress_percentage}
                            className="h-2"
                        />
                    </div>
                )}
            </div>
        </Card>
    );
};

export default PointsOverview;
