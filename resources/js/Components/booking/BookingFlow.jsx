//resources/js/Components/booking/ BookingFlow.jsx
import React, { useState } from 'react';
import { Calendar, User, Clock } from 'lucide-react';

const ServiceSelector = ({ services = [], onSelect }) => (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {services.map(service => (
            <div key={service.id} onClick={() => onSelect(service)}
                 className="p-4 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors">
                {service.image && (
                    <img src={service.image_url} alt={service.name}
                         className="w-full h-48 object-cover rounded-lg mb-4" />
                )}
                <h3 className="font-bold mb-2">{service.name}</h3>
                <p className="text-gray-600 text-sm">{service.description}</p>
                <div className="mt-2 text-blue-600">
                    {new Intl.NumberFormat('fa-IR').format(service.price)} تومان
                </div>
            </div>
        ))}
    </div>
);

const MonthCalendar = ({ specialist, yearMonth, onSelectDate }) => {
    const [availability, setAvailability] = useState(null);

    useEffect(() => {
        fetchAvailability();
    }, [yearMonth]);

    const fetchAvailability = async () => {
        const response = await fetch(`/api/specialists/${specialist.id}/availability/${yearMonth}`);
        const data = await response.json();
        setAvailability(data);
    };

    const getDayStatus = (date) => {
        if (availability?.holiday_days.includes(date)) return 'holiday';
        if (availability?.fully_booked_days.includes(date)) return 'fully-booked';
        return 'available';
    };

    return (
        <div className="grid grid-cols-7 gap-1">
            {/* Calendar header */}
            <div className="col-span-7 grid grid-cols-7 mb-2">
                {['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map(day => (
                    <div key={day} className="text-center text-gray-500 text-sm">{day}</div>
                ))}
            </div>

            {/* Calendar days */}
            {generateCalendarDays(yearMonth).map(day => {
                const status = getDayStatus(day.date);
                return (
                    <div
                        key={day.date}
                        onClick={() => status === 'available' && onSelectDate(day.date)}
                        className={`
              p-2 text-center cursor-pointer rounded transition-colors
              ${status === 'available' ? 'hover:bg-blue-50' : ''}
              ${status === 'holiday' ? 'bg-red-50 text-red-600' : ''}
              ${status === 'fully-booked' ? 'bg-gray-50 text-gray-400' : ''}
            `}
                    >
                        {day.dayOfMonth}
                    </div>
                );
            })}
        </div>
    );
};

const TimeSlotGrid = ({ slots = [], onSelect }) => (
    <div className="grid grid-cols-3 md:grid-cols-6 gap-2">
        {slots.map(slot => (
            <div
                key={slot}
                onClick={() => onSelect(slot)}
                className="p-2 text-center border rounded cursor-pointer hover:bg-blue-50 transition-colors"
            >
                {slot}
            </div>
        ))}
    </div>
);

const BookingSummary = ({ booking, discount, onApplyDiscount, onConfirm }) => (
    <div className="space-y-4 p-4 border rounded-lg bg-gray-50">
        <h3 className="font-bold text-lg">خلاصه رزرو</h3>
        <div className="space-y-2">
            <p>خدمت: {booking.service.name}</p>
            <p>متخصص: {booking.specialist.name}</p>
            <p>تاریخ: {new Intl.DateTimeFormat('fa-IR').format(new Date(booking.date))}</p>
            <p>ساعت: {booking.time}</p>

            <div className="border-t pt-4 mt-4">
                <div className="flex justify-between">
                    <span>قیمت خدمت:</span>
                    <span>{new Intl.NumberFormat('fa-IR').format(booking.service.price)} تومان</span>
                </div>
                {discount && (
                    <div className="flex justify-between text-green-600">
                        <span>تخفیف:</span>
                        <span>{new Intl.NumberFormat('fa-IR').format(discount)} تومان</span>
                    </div>
                )}
                <div className="flex justify-between font-bold mt-2">
                    <span>پیش پرداخت:</span>
                    <span>{new Intl.NumberFormat('fa-IR').format(50000)} تومان</span>
                </div>
            </div>

            <div className="mt-4">
                <input
                    type="text"
                    placeholder="کد تخفیف"
                    className="w-full border rounded p-2 mb-2"
                    onChange={(e) => onApplyDiscount(e.target.value)}
                />
            </div>
        </div>

        <button
            onClick={onConfirm}
            className="w-full bg-blue-600 text-white p-3 rounded hover:bg-blue-700 transition-colors"
        >
            تایید و پرداخت
        </button>
    </div>
);

const BookingFlow = () => {
    const [step, setStep] = useState(1);
    const [booking, setBooking] = useState({
        service: null,
        specialist: null,
        date: null,
        time: null
    });
    const [discount, setDiscount] = useState(null);

    const handleServiceSelect = async (service) => {
        setBooking(prev => ({ ...prev, service }));
        setStep(2);
    };

    const handleSpecialistSelect = (specialist) => {
        setBooking(prev => ({ ...prev, specialist }));
        setStep(3);
    };

    const handleDateSelect = (date) => {
        setBooking(prev => ({ ...prev, date }));
        setStep(4);
    };

    const handleTimeSelect = (time) => {
        setBooking(prev => ({ ...prev, time }));
        setStep(5);
    };

    const handleApplyDiscount = async (code) => {
        try {
            const response = await fetch('/api/bookings/apply-discount', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const data = await response.json();
            setDiscount(data.discount_amount);
        } catch (error) {
            console.error('Error applying discount:', error);
        }
    };

    const handleConfirm = async () => {
        try {
            const response = await fetch('/api/bookings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_id: booking.service.id,
                    specialist_id: booking.specialist.id,
                    booking_time: `${booking.date} ${booking.time}`,
                    discount_code: discount?.code
                })
            });

            if (response.ok) {
                const data = await response.json();
                window.location.href = `/payment/${data.booking_id}`;
            }
        } catch (error) {
            console.error('Error creating booking:', error);
        }
    };

    return (
        <div className="max-w-4xl mx-auto p-4">
            {/* Progress Steps */}
            <div className="mb-8">
                <div className="flex justify-between items-center">
                    {[1, 2, 3, 4, 5].map(s => (
                        <div
                            key={s}
                            className={`w-1/5 h-2 rounded ${
                                s <= step ? 'bg-blue-600' : 'bg-gray-200'
                            }`}
                        />
                    ))}
                </div>
                <div className="flex justify-between mt-2 text-sm text-gray-600">
                    <span>انتخاب خدمت</span>
                    <span>انتخاب متخصص</span>
                    <span>انتخاب تاریخ</span>
                    <span>انتخاب ساعت</span>
                    <span>تایید نهایی</span>
                </div>
            </div>

            {/* Content */}
            <div className="mt-6">
                {step === 1 && <ServiceSelector onSelect={handleServiceSelect} />}
                {step === 2 && <SpecialistSelector onSelect={handleSpecialistSelect} />}
                {step === 3 && <MonthCalendar onSelectDate={handleDateSelect} />}
                {step === 4 && <TimeSlotGrid onSelect={handleTimeSelect} />}
                {step === 5 && (
                    <BookingSummary
                        booking={booking}
                        discount={discount}
                        onApplyDiscount={handleApplyDiscount}
                        onConfirm={handleConfirm}
                    />
                )}
            </div>
        </div>
    );
};

export default BookingFlow;
