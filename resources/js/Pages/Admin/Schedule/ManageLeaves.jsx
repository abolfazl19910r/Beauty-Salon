import React, { useState, useEffect } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';

const ManageLeaves = ({ specialist }) => {
    const [leaves, setLeaves] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchLeaves();
    }, [specialist]);

    const fetchLeaves = async () => {
        try {
            const response = await fetch(`/api/specialists/${specialist.id}/leaves`);
            const data = await response.json();
            setLeaves(data);
            setLoading(false);
        } catch (err) {
            setError('خطا در دریافت لیست مرخصی‌ها');
            setLoading(false);
        }
    };

    const updateLeaveStatus = async (leaveId, status, rejectReason = null) => {
        try {
            const response = await fetch(
                `/api/specialists/${specialist.id}/leaves/${leaveId}`,
                {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status, reject_reason: rejectReason })
                }
            );

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'خطا در بروزرسانی وضعیت مرخصی');
            }

            const updatedLeave = await response.json();
            setLeaves(leaves.map(leave =>
                leave.id === leaveId ? updatedLeave : leave
            ));
        } catch (err) {
            setError(err.message);
        }
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'approved':
                return 'bg-green-100 text-green-800';
            case 'rejected':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = (status) => {
        switch (status) {
            case 'pending':
                return 'در انتظار تایید';
            case 'approved':
                return 'تایید شده';
            case 'rejected':
                return 'رد شده';
            default:
                return status;
        }
    };

    if (loading) {
        return (
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
        );
    }

    return (
        <div className="space-y-6">
            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            <div className="bg-white rounded-lg shadow divide-y">
                <h3 className="text-lg font-semibold p-4">درخواست‌های مرخصی</h3>

                {leaves.length === 0 ? (
                    <div className="p-4 text-center text-gray-500">
                        درخواست مرخصی‌ای وجود ندارد
                    </div>
                ) : (
                    leaves.map(leave => (
                        <div
                            key={leave.id}
                            className="p-4 hover:bg-gray-50"
                        >
                            <div className="flex items-center justify-between mb-2">
                                <div>
                                    <div className="font-medium">
                                        {new Date(leave.start_date).toLocaleDateString('fa-IR')} تا{' '}
                                        {new Date(leave.end_date).toLocaleDateString('fa-IR')}
                                    </div>
                                    {leave.reason && (
                                        <div className="text-sm text-gray-500">
                                            دلیل: {leave.reason}
                                        </div>
                                    )}
                                </div>
                                <span className={`px-2 py-1 rounded-full text-sm ${getStatusBadgeClass(leave.status)}`}>
                  {getStatusText(leave.status)}
                </span>
                            </div>

                            {leave.status === 'pending' && (
                                <div className="flex justify-end space-x-2 space-x-reverse">
                                    <button
                                        onClick={() => updateLeaveStatus(leave.id, 'approved')}
                                        className="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600"
                                    >
                                        تایید
                                    </button>
                                    <button
                                        onClick={() => {
                                            const reason = prompt('دلیل رد درخواست را وارد کنید:');
                                            if (reason !== null) {
                                                updateLeaveStatus(leave.id, 'rejected', reason);
                                            }
                                        }}
                                        className="px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600"
                                    >
                                        رد
                                    </button>
                                </div>
                            )}

                            {leave.status === 'rejected' && leave.reject_reason && (
                                <div className="mt-2 text-sm text-red-500">
                                    دلیل رد: {leave.reject_reason}
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};

export default ManageLeaves;
