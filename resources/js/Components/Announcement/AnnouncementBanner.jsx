import React, { useState, useEffect } from 'react';
import { X, AlertCircle, Info, Star, AlertTriangle, Bell } from 'lucide-react';

const AnnouncementBanner = () => {
    const [announcements, setAnnouncements] = useState([]);
    const [dismissedIds, setDismissedIds] = useState([]);

    useEffect(() => {
        fetchActiveAnnouncements();
        const dismissed = JSON.parse(localStorage.getItem('dismissedAnnouncements') || '[]');
        setDismissedIds(dismissed);
    }, []);

    const fetchActiveAnnouncements = async () => {
        try {
            const response = await fetch('/api/announcements/active');
            if (!response.ok) throw new Error('خطا در دریافت اطلاعیه ها');
            const data = await response.json();
            setAnnouncements(data);
        } catch (error) {
            console.error('Error fetching announcements:', error);
        }
    };

    const getAnnouncementStyle = (priority, type) => {
        if (priority >= 100) {
            return {
                bg: 'bg-red-100 border-red-500',
                text: 'text-red-900',
                icon: AlertCircle,
                iconColor: 'text-red-600',
                borderWidth: 'border-r-4'
            };
        } else if (priority >= 71) {
            return {
                bg: 'bg-orange-100 border-orange-500',
                text: 'text-orange-900',
                icon: AlertTriangle,
                iconColor: 'text-orange-600',
                borderWidth: 'border-r-4'
            };
        } else if (priority >= 31) {
            return {
                bg: 'bg-yellow-100 border-yellow-500',
                text: 'text-yellow-900',
                icon: Star,
                iconColor: 'text-yellow-600',
                borderWidth: 'border-r-4'
            };
        } else {
            return {
                bg: 'bg-blue-100 border-blue-500',
                text: 'text-blue-900',
                icon: Info,
                iconColor: 'text-blue-600',
                borderWidth: 'border-r-4'
            };
        }
    };

    const dismissAnnouncement = (id) => {
        const newDismissed = [...dismissedIds, id];
        setDismissedIds(newDismissed);
        localStorage.setItem('dismissedAnnouncements', JSON.stringify(newDismissed));
    };

    const visibleAnnouncements = announcements.filter(
        announcement => !dismissedIds.includes(announcement.id)
    );

    if (visibleAnnouncements.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3 p-4" dir="rtl">
            {visibleAnnouncements.map(announcement => {
                const style = getAnnouncementStyle(announcement.priority, announcement.type);
                const Icon = style.icon;

                return (
                    <div
                        key={announcement.id}
                        className={`${style.bg} ${style.borderWidth} rounded-lg p-4 shadow-md relative animate-fadeIn`}
                    >
                        <div className="flex items-start gap-3">
                            <Icon className={`h-6 w-6 ${style.iconColor} flex-shrink-0 mt-0.5`} />

                            <div className="flex-1">
                                <h3 className={`font-bold text-lg ${style.text} mb-1`}>
                                    {announcement.title}
                                </h3>
                                <p className={`${style.text} text-sm whitespace-pre-line`}>
                                    {announcement.content}
                                </p>

                                {announcement.priority >= 31 && (
                                    <div className="mt-2 flex items-center gap-2">
                                        <span className="inline-block px-2 py-1 bg-white bg-opacity-50 rounded text-xs font-semibold">
                                            اولویت بالا: {announcement.priority}
                                        </span>
                                        {announcement.type && (
                                            <span className="inline-block px-2 py-1 bg-white bg-opacity-50 rounded text-xs">
                                                {announcement.type === 'general' && '📢 عمومی'}
                                                {announcement.type === 'maintenance' && '🔧 تعمیرات'}
                                                {announcement.type === 'promotion' && '🎉 تبلیغاتی'}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>

                            {announcement.priority < 100 && (
                                <button
                                    onClick={() => dismissAnnouncement(announcement.id)}
                                    className={`${style.iconColor} hover:opacity-70 transition-opacity flex-shrink-0`}
                                    title="بستن این اطلاعیه"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            )}
                        </div>
                    </div>
                );
            })}

            <style jsx>{`
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .animate-fadeIn {
                    animation: fadeIn 0.3s ease-out;
                }
            `}</style>
        </div>
    );
};

export default AnnouncementBanner;
