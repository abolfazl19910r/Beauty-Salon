import React, { useState, useEffect, forwardRef, useImperativeHandle } from "react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Award, Star, TrendingUp, User } from "lucide-react";
import DashboardService from '@/services/DashboardService';

const SpecialistStats = forwardRef((props, ref) => {
    const [specialists, setSpecialists] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchData = async () => {
        try {
            setLoading(true);
            const result = await DashboardService.getActiveSpecialists();
            setSpecialists(result.activeSpecialists);
            setError(null);
        } catch (error) {
            console.error('Error fetching specialist stats:', error);
            setError('خطا در دریافت اطلاعات متخصصین');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    useImperativeHandle(ref, () => ({
        refresh: fetchData
    }));

    if (loading) return <div className="p-4">در حال بارگذاری...</div>;
    if (error) return <div className="p-4 text-red-500">{error}</div>;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-lg font-medium flex items-center gap-2">
                    <User className="w-5 h-5" />
                    آمار متخصصین
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-6">
                    {specialists.map((specialist) => (
                        <div key={specialist.id} className="space-y-2">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="relative h-10 w-10 overflow-hidden rounded-full bg-muted flex items-center justify-center">
                                        {specialist.image ? (
                                            <img
                                                src={specialist.image}
                                                alt={specialist.name}
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <span className="text-lg font-medium">{specialist.name[0]}</span>
                                        )}
                                    </div>
                                    <div>
                                        <h4 className="font-medium">{specialist.name}</h4>
                                        <p className="text-sm text-muted-foreground">{specialist.expertise}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="flex items-center gap-1">
                                        <TrendingUp className="w-4 h-4 text-green-500" />
                                        <span className="text-sm font-medium">{specialist.completion_rate}%</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Star className="w-4 h-4 text-yellow-500" />
                                        <span className="text-sm font-medium">{specialist.rating}</span>
                                    </div>
                                    {specialist.top_performer && (
                                        <Award className="w-5 h-5 text-purple-500" title="برترین عملکرد" />
                                    )}
                                </div>
                            </div>
                            <div>
                                <div className="flex justify-between text-sm text-muted-foreground mb-1">
                                    <span>عملکرد ماهانه</span>
                                    <span>{specialist.performance_score}%</span>
                                </div>
                                <div className="relative h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full bg-primary transition-all"
                                        style={{ width: `${specialist.performance_score}%` }}
                                    />
                                </div>
                            </div>
                            <div className="flex justify-between text-sm pt-2">
                                <div>
                                    <span className="text-muted-foreground">نوبت‌های موفق: </span>
                                    <span className="font-medium">{specialist.successful_bookings}</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">درآمد: </span>
                                    <span className="font-medium">{new Intl.NumberFormat('fa-IR').format(specialist.revenue)} تومان</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
});

SpecialistStats.displayName = 'SpecialistStats';

export default SpecialistStats;
