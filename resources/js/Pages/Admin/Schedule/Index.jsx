import React, { useState, useEffect } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const ScheduleIndex = () => {
    const [specialists, setSpecialists] = useState([]);
    const [selectedSpecialist, setSelectedSpecialist] = useState(null);
    const [activeTab, setActiveTab] = useState('schedule');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const tabs = [
        { id: 'schedule', name: 'برنامه کاری', icon: '⏰' },
        { id: 'holidays', name: 'تعطیلات', icon: '📅' },
        { id: 'leaves', name: 'مرخصی‌ها', icon: '🏖️' }
    ];

    useEffect(() => {
        fetchSpecialists();
    }, []);

    const fetchSpecialists = async () => {
        try {
            const response = await fetch('/api/admin/specialists');
            const data = await response.json();
            setSpecialists(data);
            setLoading(false);
        } catch (err) {
            setError('خطا در دریافت لیست متخصصین');
            setLoading(false);
        }
    };

    const renderContent = () => {
        if (!selectedSpecialist) return null;

        switch (activeTab) {
            case 'schedule':
                return <WorkSchedule specialist={selectedSpecialist} />;
            case 'holidays':
                return <Holidays specialist={selectedSpecialist} />;
            case 'leaves':
                return <Leaves specialist={selectedSpecialist} />;
            default:
                return null;
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
            </div>
        );
    }

    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-2xl font-bold mb-4">مدیریت برنامه کاری متخصصین</h1>
                <select
                    className="w-full max-w-xs border rounded-md p-2"
                    value={selectedSpecialist?.id || ''}
                    onChange={(e) => {
                        const specialist = specialists.find(s => s.id === parseInt(e.target.value));
                        setSelectedSpecialist(specialist);
                    }}
                >
                    <option value="">انتخاب متخصص</option>
                    {specialists.map(specialist => (
                        <option key={specialist.id} value={specialist.id}>
                            {specialist.name}
                        </option>
                    ))}
                </select>
            </div>

            {selectedSpecialist ? (
                <>
                    <div className="border-b mb-6">
                        <nav className="flex space-x-4 space-x-reverse">
                            {tabs.map(tab => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`flex items-center pb-4 px-2 border-b-2 transition-colors ${
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    <span className="ml-2">{tab.icon}</span>
                                    {tab.name}
                                </button>
                            ))}
                        </nav>
                    </div>

                    <div className="bg-white rounded-lg shadow p-6">
                        {renderContent()}
                    </div>
                </>
            ) : (
                <Alert>
                    <AlertDescription>
                        لطفاً یک متخصص را انتخاب کنید
                    </AlertDescription>
                </Alert>
            )}

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
};

const WorkSchedule = ({ specialist }) => {
    // Work Schedule Component Implementation
    return (
        <div>Work Schedule Component</div>
    );
};

const Holidays = ({ specialist }) => {
    // Holidays Component Implementation
    return (
        <div>Holidays Component</div>
    );
};

const Leaves = ({ specialist }) => {
    // Leaves Component Implementation
    return (
        <div>Leaves Component</div>
    );
};

export default ScheduleIndex;
