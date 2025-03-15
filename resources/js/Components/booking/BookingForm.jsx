import React, { useState, useEffect } from 'react';
import { Calendar, Clock, User, ChevronRight, AlertCircle } from 'lucide-react';
import axios from 'axios';

const BookingForm = () => {
    const [currentStep, setCurrentStep] = useState(0);
    const [services, setServices] = useState([]);
    const [specialists, setSpecialists] = useState([]);
    const [availableDates, setAvailableDates] = useState([]);
    const [availableTimeSlots, setAvailableTimeSlots] = useState([]);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedSpecialist, setSelectedSpecialist] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [discountCode, setDiscountCode] = useState('');
    const [discountInfo, setDiscountInfo] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const steps = ["انتخاب خدمت", "انتخاب متخصص", "انتخاب تاریخ", "انتخاب ساعت", "تأیید نهایی"];

    const progressPercentage = ((currentStep + 1) / steps.length) * 100;

    useEffect(() => {
        fetchServices();
    }, []);

    useEffect(() => {
        if (selectedService) {
            fetchSpecialists();
        }
    }, [selectedService]);

    useEffect(() => {
        if (selectedSpecialist) {
            fetchAvailableDates();
        }
    }, [selectedSpecialist]);

    useEffect(() => {
        if (selectedDate) {
            fetchAvailableTimeSlots();
        }
    }, [selectedDate]);

    const fetchServices = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get('/api/services');
            setServices(response.data);
        } catch (err) {
            setError('خطا در دریافت لیست خدمات');
        } finally {
            setLoading(false);
        }
    };

    const fetchSpecialists = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/specialists/${selectedService.id}`);
            setSpecialists(response.data);
        } catch (err) {
            setError('خطا در دریافت لیست متخصصین');
        } finally {
            setLoading(false);
        }
    };

    const fetchAvailableDates = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/specialists/${selectedSpecialist.id}/available-dates`);
            setAvailableDates(response.data);
        } catch (err) {
            setError('خطا در دریافت تاریخ‌های در دسترس');
        } finally {
            setLoading(false);
        }
    };

    const fetchAvailableTimeSlots = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/specialists/${selectedSpecialist.id}/time-slots/${selectedDate}`);
            setAvailableTimeSlots(response.data.slots || []);
        } catch (err) {
            setError('خطا در دریافت ساعت‌های در دسترس');
        } finally {
            setLoading(false);
        }
    };

    const checkDiscount = async () => {
        if (!discountCode) return;

        setLoading(true);
        setError(null);
        try {
            const response = await axios.post('/api/bookings/check-discount', {
                code: discountCode,
                service_id: selectedService.id
            });

            if (response.data.valid) {
                setDiscountInfo({
                    amount: response.data.discount_amount,
                    finalPrice: response.data.final_price,
                    message: response.data.message
                });
            } else {
                setError(response.data.message);
                setDiscountInfo(null);
            }
        } catch (err) {
            setError('خطا در بررسی کد تخفیف');
            setDiscountInfo(null);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async () => {
        setLoading(true);
        setError(null);
        try {
            const bookingDateTime = `${selectedDate}T${selectedTime}:00`;

            const response = await axios.post('/api/bookings', {
                service_id: selectedService.id,
                specialist_id: selectedSpecialist.id,
                booking_time: bookingDateTime,
                discount_code: discountCode || null
            });

            if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            } else {
                window.location.href = `/bookings/${response.data.booking_id}`;
            }
        } catch (err) {
            setError(err.response?.data?.message || 'خطا در ثبت رزرو');
        } finally {
            setLoading(false);
        }
    };

    const selectService = (service) => {
        setSelectedService(service);
        setSelectedSpecialist(null);
        setSelectedDate(null);
        setSelectedTime(null);
    };

    const selectSpecialist = (specialist) => {
        setSelectedSpecialist(specialist);
        setSelectedDate(null);
        setSelectedTime(null);
    };

    const selectDate = (date) => {
        setSelectedDate(date);
        setSelectedTime(null);
    };

    const selectTime = (time) => {
        setSelectedTime(time);
    };

    const nextStep = () => {
        if (currentStep < steps.length - 1) {
            setCurrentStep(currentStep + 1);
        }
    };

    const prevStep = () => {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    };

    const formatDate = (dateString) => {
        const persianDate = new Date(dateString).toLocaleDateString('fa-IR');
        return persianDate;
    };

    const formatPrice = (price) => {
        return new Intl.NumberFormat('fa-IR').format(price);
    };

    return (
        <div className="max-w-3xl mx-auto">
            <h1 className="text-2xl font-bold mb-6 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">
                رزرو نوبت جدید
            </h1>

            {/* نوار پیشرفت */}
            <div className="mb-8">
                <div className="relative">
                    <div className="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                        <div
                            className="bg-gradient-to-r from-pink-500 to-purple-600 transition-all duration-500"
                            style={{ width: `${progressPercentage}%` }}
                        ></div>
                    </div>
                    <div className="flex text-xs justify-between mt-1">
                        {steps.map((step, index) => (
                            <span
                                key={index}
                                className={currentStep >= index ? "text-gray-800" : "text-gray-400"}
                            >
                {step}
              </span>
                        ))}
                    </div>
                </div>
            </div>

            {/* نمایش خطا */}
            {error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                    <AlertCircle className="w-5 h-5 mr-2" />
                    <span>{error}</span>
                </div>
            )}

            <div className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-6">
                {/* مرحله ۱: انتخاب خدمت */}
                {currentStep === 0 && (
                    <div className="space-y-6">
                        <h2 className="text-lg font-bold">انتخاب خدمت</h2>
                        {loading ? (
                            <div className="py-8 flex justify-center">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {services.map((service) => (
                                    <div
                                        key={service.id}
                                        onClick={() => selectService(service)}
                                        className={`
                      border rounded-lg p-4 cursor-pointer transition-all duration-300
                      ${selectedService && selectedService.id === service.id
                                            ? 'border-pink-500 bg-pink-50'
                                            : 'border-gray-200 hover:border-pink-300 hover:bg-pink-50/30'}
                    `}
                                    >
                                        <div className="flex items-start">
                                            {service.image && (
                                                <div className="w-16 h-16 rounded-md overflow-hidden mr-4">
                                                    <img
                                                        src={service.image_url}
                                                        alt={service.name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                            )}
                                            <div className="flex-grow">
                                                <h3 className="font-bold">{service.name}</h3>
                                                <p className="text-sm text-gray-600 line-clamp-2 mt-1">{service.description}</p>
                                                <div className="flex items-center justify-between mt-2">
                                                    <span className="text-pink-600 font-bold">{formatPrice(service.price)} تومان</span>
                                                    <span className="text-gray-500 text-sm">{service.duration} دقیقه</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                        {services.length === 0 && !loading && (
                            <div className="py-8 text-center text-gray-500">
                                خدمتی یافت نشد.
                            </div>
                        )}
                        <div className="mt-6">
                            <button
                                onClick={nextStep}
                                disabled={!selectedService}
                                className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                ادامه
                            </button>
                        </div>
                    </div>
                )}

                {/* مرحله ۲: انتخاب متخصص */}
                {currentStep === 1 && (
                    <div className="space-y-6">
                        <div className="flex justify-between items-center">
                            <h2 className="text-lg font-bold">انتخاب متخصص</h2>
                            <button
                                onClick={prevStep}
                                className="text-gray-500 hover:text-gray-700 text-sm flex items-center"
                            >
                                <ChevronRight className="w-4 h-4 ml-1" />
                                بازگشت
                            </button>
                        </div>

                        {loading ? (
                            <div className="py-8 flex justify-center">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4">
                                {specialists.map((specialist) => (
                                    <div
                                        key={specialist.id}
                                        onClick={() => selectSpecialist(specialist)}
                                        className={`
                      border rounded-lg p-4 cursor-pointer transition-all duration-300
                      ${selectedSpecialist && selectedSpecialist.id === specialist.id
                                            ? 'border-pink-500 bg-pink-50'
                                            : 'border-gray-200 hover:border-pink-300 hover:bg-pink-50/30'}
                    `}
                                    >
                                        <div className="flex items-center">
                                            <div className="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center ml-4">
                        <span className="text-xl font-bold text-gray-500">
                          {specialist.name.charAt(0)}
                        </span>
                                            </div>
                                            <div className="flex-grow">
                                                <h3 className="font-bold">{specialist.name}</h3>
                                                <div className="flex items-center mt-1">
                                                    {specialist.rating && (
                                                        <div className="flex items-center text-yellow-400 ml-2">
                                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                            </svg>
                                                            <span className="text-sm mr-1">{specialist.rating}/5</span>
                                                        </div>
                                                    )}
                                                    {specialist.expertise && (
                                                        <div className="text-sm text-gray-500">
                                                            {specialist.expertise}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {specialists.length === 0 && !loading && (
                            <div className="py-8 text-center text-gray-500">
                                متخصصی برای این خدمت یافت نشد.
                            </div>
                        )}

                        <div className="mt-6">
                            <button
                                onClick={nextStep}
                                disabled={!selectedSpecialist}
                                className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                ادامه
                            </button>
                        </div>
                    </div>
                )}

                {/* مرحله ۳: انتخاب تاریخ */}
                {currentStep === 2 && (
                    <div className="space-y-6">
                        <div className="flex justify-between items-center">
                            <h2 className="text-lg font-bold">انتخاب تاریخ</h2>
                            <button
                                onClick={prevStep}
                                className="text-gray-500 hover:text-gray-700 text-sm flex items-center"
                            >
                                <ChevronRight className="w-4 h-4 ml-1" />
                                بازگشت
                            </button>
                        </div>

                        {loading ? (
                            <div className="py-8 flex justify-center">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                {availableDates.map((date) => (
                                    <div
                                        key={date}
                                        onClick={() => selectDate(date)}
                                        className={`
                      p-3 rounded-lg text-center cursor-pointer transition-all
                      ${selectedDate === date
                                            ? 'bg-pink-500 text-white'
                                            : 'bg-gray-100 hover:bg-pink-100'}
                    `}
                                    >
                                        <div className="text-sm mb-1">{new Date(date).toLocaleDateString('fa-IR', { weekday: 'short' })}</div>
                                        <div className="font-bold">{new Date(date).toLocaleDateString('fa-IR', { day: 'numeric' })}</div>
                                        <div className="text-xs">{new Date(date).toLocaleDateString('fa-IR', { month: 'short' })}</div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {availableDates.length === 0 && !loading && (
                            <div className="py-8 text-center text-gray-500">
                                تاریخی در دسترس نیست.
                            </div>
                        )}

                        <div className="mt-6">
                            <button
                                onClick={nextStep}
                                disabled={!selectedDate}
                                className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                ادامه
                            </button>
                        </div>
                    </div>
                )}

                {/* مرحله ۴: انتخاب ساعت */}
                {currentStep === 3 && (
                    <div className="space-y-6">
                        <div className="flex justify-between items-center">
                            <h2 className="text-lg font-bold">انتخاب ساعت</h2>
                            <button
                                onClick={prevStep}
                                className="text-gray-500 hover:text-gray-700 text-sm flex items-center"
                            >
                                <ChevronRight className="w-4 h-4 ml-1" />
                                بازگشت
                            </button>
                        </div>

                        <div className="text-center mb-4">
              <span className="inline-flex items-center bg-gray-100 px-3 py-1 rounded-full text-sm">
                <Calendar className="w-4 h-4 ml-1" />
                  {formatDate(selectedDate)}
              </span>
                        </div>

                        {loading ? (
                            <div className="py-8 flex justify-center">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-3 sm:grid-cols-4 gap-3">
                                {availableTimeSlots.map((time) => (
                                    <div
                                        key={time}
                                        onClick={() => selectTime(time)}
                                        className={`
                      p-3 rounded-lg text-center cursor-pointer transition-all
                      ${selectedTime === time
                                            ? 'bg-pink-500 text-white'
                                            : 'bg-gray-100 hover:bg-pink-100'}
                    `}
                                    >
                                        <Clock className="w-4 h-4 mx-auto mb-1" />
                                        <span dir="ltr">{time}</span>
                                    </div>
                                ))}
                            </div>
                        )}

                        {availableTimeSlots.length === 0 && !loading && (
                            <div className="py-8 text-center text-gray-500">
                                ساعتی در دسترس نیست.
                            </div>
                        )}

                        <div className="mt-6">
                            <button
                                onClick={nextStep}
                                disabled={!selectedTime}
                                className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                ادامه
                            </button>
                        </div>
                    </div>
                )}

                {/* مرحله ۵: تأیید نهایی */}
                {currentStep === 4 && (
                    <div className="space-y-6">
                        <div className="flex justify-between items-center">
                            <h2 className="text-lg font-bold">تأیید نهایی</h2>
                            <button
                                onClick={prevStep}
                                className="text-gray-500 hover:text-gray-700 text-sm flex items-center"
                            >
                                <ChevronRight className="w-4 h-4 ml-1" />
                                بازگشت
                            </button>
                        </div>

                        <div className="bg-gray-50 p-4 rounded-lg">
                            <h3 className="font-bold mb-4">خلاصه رزرو</h3>

                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">خدمت:</span>
                                    <span>{selectedService.name}</span>
                                </div>

                                <div className="flex justify-between">
                                    <span className="text-gray-600">متخصص:</span>
                                    <span>{selectedSpecialist.name}</span>
                                </div>

                                <div className="flex justify-between">
                                    <span className="text-gray-600">تاریخ:</span>
                                    <span>{formatDate(selectedDate)}</span>
                                </div>

                                <div className="flex justify-between">
                                    <span className="text-gray-600">ساعت:</span>
                                    <span dir="ltr">{selectedTime}</span>
                                </div>

                                <hr className="my-3" />

                                <div className="flex justify-between font-bold">
                                    <span>قیمت:</span>
                                    <span>{formatPrice(selectedService.price)} تومان</span>
                                </div>

                                <div className="flex justify-between text-pink-600 font-bold">
                                    <span>پیش پرداخت:</span>
                                    <span>{formatPrice(50000)} تومان</span>
                                </div>

                                {discountInfo && (
                                    <div className="flex justify-between text-green-600">
                                        <span>تخفیف:</span>
                                        <span>{formatPrice(discountInfo.amount)} تومان</span>
                                    </div>
                                )}

                                {discountInfo && (
                                    <div className="flex justify-between font-bold">
                                        <span>مبلغ نهایی:</span>
                                        <span>{formatPrice(discountInfo.finalPrice)} تومان</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={discountCode}
                                onChange={(e) => setDiscountCode(e.target.value)}
                                placeholder="کد تخفیف"
                                className="flex-1 border rounded-lg px-3 py-2"
                            />
                            <button
                                onClick={checkDiscount}
                                disabled={!discountCode || loading}
                                className="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 disabled:bg-gray-300"
                            >
                                اعمال
                            </button>
                        </div>

                        <div className="mt-6">
                            <button
                                onClick={handleSubmit}
                                disabled={loading}
                                className="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                            >
                                {loading ? (
                                    <>
                                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white ml-2"></div>
                                        در حال پردازش...
                                    </>
                                ) : 'تأیید و پرداخت'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default BookingForm;
