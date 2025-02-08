// resources/js/Components/Admin/Reports/Dashboard/PopularServices.jsx

import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { TrendingUp, TrendingDown } from 'lucide-react';
import ExportButtons from '../Common/ExportButtons';

const PopularServices = () => {
    const [services, setServices] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchServices = async () => {
            try {
                const response = await fetch('/admin/reports/popular-services');
                const data = await response.json();
                setServices(data);
            } catch (error) {
                console.error('Error fetching services:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchServices();
    }, []);

    if (loading) return <div className="p-4">در حال بارگذاری...</div>;

    return (
        <div className="bg-white rounded-lg shadow p-4">
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">خدمات محبوب</h2>
                <ExportButtons reportType="services" />
            </div>

            <div className="h-96">
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={services}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="name" />
                        <YAxis yAxisId="left" orientation="left" stroke="#8884d8" />
                        <YAxis yAxisId="right" orientation="right" stroke="#82ca9d" />
                        <Tooltip />
                        <Legend />
                        <Bar yAxisId="left" dataKey="bookings_count" name="تعداد نوبت" fill="#8884d8" />
                        <Bar yAxisId="right" dataKey="revenue" name="درآمد" fill="#82ca9d" />
                    </BarChart>
                </ResponsiveContainer>
            </div>

            <div className="mt-4">
                <div className="grid gap-4">
                    {services.map((service) => (
                        <div key={service.id} className="border rounded-lg p-4">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h3 className="font-semibold">{service.name}</h3>
                                    <p className="text-sm text-gray-500">
                                        {service.bookings_count} نوبت - {new Intl.NumberFormat('fa-IR').format(service.revenue)} تومان
                                    </p>
                                </div>
                                <div className="flex items-center">
                                    {service.trend > 0 ? (
                                        <TrendingUp className="text-green-500 w-5 h-5" />
                                    ) : (
                                        <TrendingDown className="text-red-500 w-5 h-5" />
                                    )}
                                    <span className={`mr-1 ${service.trend > 0 ? 'text-green-500' : 'text-red-500'}`}>
                    {Math.abs(service.trend)}%
                  </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default PopularServices;
