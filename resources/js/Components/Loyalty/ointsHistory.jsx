// resources/js/Components/Loyalty/PointsHistory.jsx
import React, { useState, useEffect } from 'react';
import { Card, Table, Badge, Pagination } from '@/components/ui';
import { Calendar, ArrowUp, ArrowDown } from 'lucide-react';

const PointsHistory = () => {
    const [history, setHistory] = useState({
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchHistory(1);
    }, []);

    const fetchHistory = async (page) => {
        try {
            const response = await fetch(`/api/loyalty/history?page=${page}`);
            const data = await response.json();
            setHistory(data);
        } catch (error) {
            console.error('Error fetching history:', error);
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        return new Intl.DateTimeFormat('fa-IR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(dateString));
    };

    if (loading) {
        return <Card className="animate-pulse h-96" />;
    }

    return (
        <Card>
            <div className="p-6 border-b">
                <h3 className="text-lg font-bold">تاریخچه امتیازات</h3>
            </div>

            <Table>
                <Table.Header>
                    <Table.Row>
                        <Table.Head>تاریخ</Table.Head>
                        <Table.Head>شرح</Table.Head>
                        <Table.Head>نوع</Table.Head>
                        <Table.Head>امتیاز</Table.Head>
                        <Table.Head>انقضا</Table.Head>
                    </Table.Row>
                </Table.Header>

                <Table.Body>
                    {history.data.map((point) => (
                        <Table.Row key={point.id}>
                            <Table.Cell className="whitespace-nowrap">
                                <div className="flex items-center text-sm text-gray-600">
                                    <Calendar className="w-4 h-4 mr-2" />
                                    {formatDate(point.created_at)}
                                </div>
                            </Table.Cell>

                            <Table.Cell>{point.description}</Table.Cell>

                            <Table.Cell>
                                <Badge
                                    variant={point.type === 'earned' ? 'success' : 'error'}
                                    icon={point.type === 'earned' ? ArrowUp : ArrowDown}
                                >
                                    {point.type === 'earned' ? 'دریافت' : 'مصرف'}
                                </Badge>
                            </Table.Cell>

                            <Table.Cell className={`font-bold ${
                                point.type === 'earned' ? 'text-green-600' : 'text-red-600'
                            }`}>
                                {point.type === 'earned' ? '+' : '-'}
                                {new Intl.NumberFormat('fa-IR').format(Math.abs(point.points))}
                            </Table.Cell>

                            <Table.Cell>
                                {point.expires_at ?
                                    formatDate(point.expires_at) :
                                    <span className="text-gray-400">---</span>
                                }
                            </Table.Cell>
                        </Table.Row>
                    ))}

                    {history.data.length === 0 && (
                        <Table.Row>
                            <Table.Cell colSpan={5} className="text-center py-8 text-gray-500">
                                تاریخچه‌ای وجود ندارد
                            </Table.Cell>
                        </Table.Row>
                    )}
                </Table.Body>
            </Table>

            {history.last_page > 1 && (
                <div className="p-4 border-t">
                    <Pagination
                        currentPage={history.current_page}
                        totalPages={history.last_page}
                        onPageChange={fetchHistory}
                    />
                </div>
            )}
        </Card>
    );
};

export default PointsHistory;
