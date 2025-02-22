import React, { useState, useEffect } from 'react';
import { AlertCircle, Lock, RefreshCw } from 'lucide-react';

const SecureForm = ({
                        onSubmit,
                        children,
                        maxAttempts = 3,
                        blockDuration = 5,
                        className = '',
                        submitButton = true,
                        submitText = 'ارسال'
                    }) => {
    const [attempts, setAttempts] = useState(0);
    const [isBlocked, setIsBlocked] = useState(false);
    const [blockExpiry, setBlockExpiry] = useState(null);
    const [error, setError] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [timeLeft, setTimeLeft] = useState(0);

    useEffect(() => {
        const storedBlockExpiry = localStorage.getItem('formBlockExpiry');
        if (storedBlockExpiry) {
            const expiry = new Date(storedBlockExpiry);
            if (expiry > new Date()) {
                setIsBlocked(true);
                setBlockExpiry(expiry);
            } else {
                localStorage.removeItem('formBlockExpiry');
            }
        }
    }, []);

    useEffect(() => {
        let timer;
        if (isBlocked && blockExpiry) {
            timer = setInterval(() => {
                const now = new Date();
                if (blockExpiry <= now) {
                    setIsBlocked(false);
                    setAttempts(0);
                    localStorage.removeItem('formBlockExpiry');
                    clearInterval(timer);
                } else {
                    setTimeLeft(Math.ceil((blockExpiry - now) / 1000));
                }
            }, 1000);
        }
        return () => clearInterval(timer);
    }, [isBlocked, blockExpiry]);

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (isBlocked) {
            setError('فرم به دلیل تلاش‌های ناموفق مکرر مسدود شده است');
            return;
        }

        try {
            setIsLoading(true);
            setError(null);
            const formData = new FormData(e.target);
            await onSubmit(formData);
            setAttempts(0);

            e.target.reset();

        } catch (err) {
            setAttempts(prev => {
                const newAttempts = prev + 1;
                if (newAttempts >= maxAttempts) {
                    const expiry = new Date(Date.now() + blockDuration * 60 * 1000);
                    setIsBlocked(true);
                    setBlockExpiry(expiry);
                    localStorage.setItem('formBlockExpiry', expiry.toISOString());
                }
                return newAttempts;
            });

            setError(err.message || 'خطا در ارسال فرم');
        } finally {
            setIsLoading(false);
        }
    };

    const remainingAttempts = maxAttempts - attempts;

    return (
        <form onSubmit={handleSubmit} className={`space-y-4 ${className}`}>
            {/* Security Header */}
            <div className="flex items-center justify-between bg-gray-50 p-3 rounded-lg text-sm">
                <div className="flex items-center text-gray-600">
                    <Lock className="w-4 h-4 mr-2" />
                    <span>فرم امن</span>
                </div>
                {!isBlocked && remainingAttempts < maxAttempts && (
                    <div className="text-yellow-600">
                        {remainingAttempts} تلاش باقیمانده
                    </div>
                )}
            </div>

            {/* Error Message */}
            {error && (
                <div className="bg-red-50 border border-red-200 rounded p-4 flex items-center">
                    <AlertCircle className="w-5 h-5 text-red-500 mr-2 flex-shrink-0" />
                    <span className="text-red-700">{error}</span>
                </div>
            )}

            {/* Form Content */}
            <div className={isBlocked ? 'opacity-50 pointer-events-none' : ''}>
                {children}
            </div>

            {/* Block Message */}
            {isBlocked && (
                <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                    <div className="font-bold text-yellow-800 mb-2">
                        فرم موقتاً مسدود شده است
                    </div>
                    <div className="text-sm text-yellow-700">
                        لطفاً {Math.ceil(timeLeft / 60)} دقیقه و {timeLeft % 60} ثانیه دیگر مجدداً تلاش کنید
                    </div>
                </div>
            )}

            {/* Submit Button */}
            {submitButton && (
                <button
                    type="submit"
                    disabled={isBlocked || isLoading}
                    className={`
                        w-full flex justify-center items-center px-4 py-2 rounded-lg
                        ${isBlocked || isLoading
                        ? 'bg-gray-300 cursor-not-allowed'
                        : 'bg-blue-500 hover:bg-blue-600'
                    } text-white font-medium transition-colors
                    `}
                >
                    {isLoading ? (
                        <>
                            <RefreshCw className="w-5 h-5 mr-2 animate-spin" />
                            در حال پردازش...
                        </>
                    ) : submitText}
                </button>
            )}
        </form>
    );
};

export default SecureForm;
