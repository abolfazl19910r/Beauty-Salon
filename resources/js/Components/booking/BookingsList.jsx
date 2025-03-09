//resources/js/Components/booking/BookingsList.jsx
import React, { useState, useEffect } from 'react';
import { Calendar, Clock } from 'lucide-react';

const BookingCard = ({ booking, onCancel, onReschedule }) => {
    const isPast = new Date(booking.booking_time) < new Date();

    return (
        <div className="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <div className="flex justify-between items-start">
                <div>
                    <h3 className="font-bold">{booking.service.name}</h3>
                    <p className="text-gray-600">{booking.specialist.name}</p>
                </div>
                <span className={`px-2 py-1 rounded text-sm ${booking.status_badge}`}>
          {booking.status_text}
        </span>
            </div>

            <div className="mt-4 space-y-2">
                <div className="flex items-center text-gray-600">
                    <Calendar className="w-4 h-4 mr-2" />
                    {new Intl.DateTimeFormat('fa-IR').format(new Date(booking.booking_time))}
                </div>
                <div className="flex items-center text-gray-600">
                    <Clock className="w-4 h-4 mr-2" />
                    {new Date(booking.booking_time).toLocaleTimeString('fa-IR')}
                </div>
            </div>

            {!isPast && booking.status !== 'cancelled' && (
                <div className="mt-4 flex gap-2">
                    {booking.can_be_rescheduled && (
                        <button
                            onClick={() => onReschedule(booking)}
                            className="px-3 py-1 text-sm border border-blue-500 text-blue-500 rounded hover:bg-blue-50"
                        >
                            تغییر زمان
                        </button>
                    )}
                    {booking.can_be_cancelled && (
                        <button
                            onClick={() => onCancel(booking)}
                            className="px-3 py-1 text-sm border border-red-500 text-red-500 rounded hover:bg-red-50"
                        >
                            لغو نوبت
                        </button>
                    )}
                </div>
            )}

            {isPast && !booking.rating && (
                <button
                    onClick={() => onRate(booking)}
                    className="mt-4 w-full px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600"
                >
                    ثبت نظر
                </button>
            )}
        </div>
    );
};

const BookingList = () => {
    const [filter, setFilter] = useState('upcoming');
    const [bookings, setBookings] = useState([]);

    useEffect(() => {
        fetchBookings();
    }, [filter]);

    const fetchBookings = async () => {
        const response = await fetch(`/api/bookings/${filter}`);
        const data = await response.json();
        setBookings(data);
    };

    const handleCancel = async (booking) => {
        const confirmed = window.confirm('آیا از لغو این نوبت اطمینان دارید؟');
        if (!confirmed) return;

        try {
            await fetch(`/api/bookings/${booking.id}/cancel`, { method: 'POST' });
            fetchBookings();
        } catch (error) {
            console.error('Error cancelling booking:', error);
        }
    };

    return (
        <div className="max-w-4xl mx-auto p-4">
            <div className="flex gap-4 mb-6">
                <button
                    onClick={() => setFilter('upcoming')}
                    className={`px-4 py-2 rounded ${
                        filter === 'upcoming' ? 'bg-blue-500 text-white' : 'bg-gray-100'
                    }`}
                >
                    نوبت‌های آینده
                </button>
                <button
                    onClick={() => setFilter('past')}
                    className={`px-4 py-2 rounded ${
                        filter === 'past' ? 'bg-blue-500 text-white' : 'bg-gray-100'
                    }`}
                >
                    نوبت‌های گذشته
                </button>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {bookings.map(booking => (
                    <BookingCard
                        key={booking.id}
                        booking={booking}
                        onCancel={handleCancel}
                        onReschedule={(booking) => {/* handle reschedule */}}
                    />
                ))}
            </div>

            {bookings.length === 0 && (
                <div className="text-center text-gray-500 py-8">
                    نوبتی یافت نشد
                </div>
            )}
        </div>
    );
};

export default BookingList;
