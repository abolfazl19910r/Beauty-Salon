//resources/js/Components/booking/BookingCard.jsx
import React from 'react';
import { Calendar, Clock, User } from 'lucide-react';

const sampleBooking = {
    id: 1,
    service: {
        name: 'کوتاه کردن مو',
        price: 150000
    },
    specialist: {
        name: 'خانم رضایی'
    },
    booking_time: '2024-01-26T14:30:00',
    status: 'pending',
    status_text: 'در انتظار تایید',
    status_badge: 'bg-yellow-100 text-yellow-800',
    payment_status: 'unpaid',
    can_be_rescheduled: true,
    can_be_cancelled: true,
    rating: null
};

const BookingCard = ({
                         booking = sampleBooking,
                         onCancel = () => {},
                         onReschedule = () => {},
                         onRate = () => {}
                     }) => {
    const isPast = new Date(booking.booking_time) < new Date();

    return (
        <div className="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <div className="flex justify-between items-start">
                <div>
                    <h3 className="font-bold text-lg">{booking.service.name}</h3>
                    <div className="flex items-center text-gray-600 mt-1">
                        <User className="w-4 h-4 mr-1" />
                        <span>{booking.specialist.name}</span>
                    </div>
                </div>

                <span className={`px-2 py-1 rounded text-sm ${booking.status_badge}`}>
          {booking.status_text}
        </span>
            </div>

            <div className="mt-4 space-y-2">
                <div className="flex items-center text-gray-600">
                    <Calendar className="w-4 h-4 mr-2" />
                    <span>
            {new Intl.DateTimeFormat('fa-IR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(booking.booking_time))}
          </span>
                </div>

                <div className="flex items-center text-gray-600">
                    <Clock className="w-4 h-4 mr-2" />
                    <span>
            {new Date(booking.booking_time).toLocaleTimeString('fa-IR', {
                hour: '2-digit',
                minute: '2-digit'
            })}
          </span>
                </div>
            </div>

            {booking.payment_status === 'unpaid' && (
                <div className="mt-4 bg-yellow-50 text-yellow-800 p-2 rounded text-sm">
                    در انتظار پرداخت
                </div>
            )}

            <div className="mt-4 flex gap-2">
                {!isPast && booking.status !== 'cancelled' && (
                    <>
                        {booking.can_be_rescheduled && (
                            <button
                                onClick={() => onReschedule(booking)}
                                className="flex-1 px-3 py-2 text-sm border border-blue-500 text-blue-500 rounded hover:bg-blue-50"
                            >
                                تغییر زمان
                            </button>
                        )}

                        {booking.can_be_cancelled && (
                            <button
                                onClick={() => onCancel(booking)}
                                className="flex-1 px-3 py-2 text-sm border border-red-500 text-red-500 rounded hover:bg-red-50"
                            >
                                لغو نوبت
                            </button>
                        )}
                    </>
                )}

                {isPast && !booking.rating && (
                    <button
                        onClick={() => onRate(booking)}
                        className="w-full px-3 py-2 text-sm bg-green-500 text-white rounded hover:bg-green-600"
                    >
                        ثبت نظر
                    </button>
                )}
            </div>
        </div>
    );
};

export default BookingCard;
