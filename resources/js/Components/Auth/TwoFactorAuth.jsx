import React, { useState, useEffect } from 'react';
import { Clock } from 'lucide-react';

const TwoFactorAuth = ({ onVerify, onResendCode }) => {
    const [code, setCode] = useState(['', '', '', '', '', '']);
    const [timeLeft, setTimeLeft] = useState(120); // 2 minutes
    const [error, setError] = useState(null);
    const inputs = Array(6).fill(0);

    useEffect(() => {
        if (timeLeft > 0) {
            const timer = setInterval(() => {
                setTimeLeft(prev => prev - 1);
            }, 1000);
            return () => clearInterval(timer);
        }
    }, [timeLeft]);

    const handleInput = (index, value) => {
        if (!/^\d*$/.test(value)) return;

        const newCode = [...code];
        newCode[index] = value;
        setCode(newCode);

        // Auto-focus next input
        if (value && index < 5) {
            document.getElementById(`code-${index + 1}`).focus();
        }

        // Auto-submit when all digits are entered
        if (value && index === 5) {
            handleSubmit(newCode.join(''));
        }
    };

    const handleKeyDown = (index, e) => {
        if (e.key === 'Backspace' && !code[index] && index > 0) {
            document.getElementById(`code-${index - 1}`).focus();
        }
    };

    const handleSubmit = async (fullCode) => {
        try {
            setError(null);
            await onVerify(fullCode);
        } catch (err) {
            setError(err.message || 'کد وارد شده نامعتبر است');
            setCode(['', '', '', '', '', '']);
            document.getElementById('code-0').focus();
        }
    };

    const handleResend = async () => {
        try {
            await onResendCode();
            setTimeLeft(120);
            setError(null);
            setCode(['', '', '', '', '', '']);
        } catch (err) {
            setError(err.message || 'خطا در ارسال مجدد کد');
        }
    };

    return (
        <div className="max-w-md mx-auto p-6 bg-white rounded-lg shadow">
            <h2 className="text-xl font-bold mb-6 text-center">تایید دو مرحله‌ای</h2>

            {error && (
                <div className="bg-red-50 text-red-600 p-3 rounded mb-4">
                    {error}
                </div>
            )}

            <div className="text-sm text-gray-600 mb-6 text-center">
                کد تایید ۶ رقمی ارسال شده به تلفن همراه خود را وارد کنید
            </div>

            <div className="flex justify-center gap-2 mb-6">
                {inputs.map((_, index) => (
                    <input
                        key={index}
                        id={`code-${index}`}
                        type="text"
                        maxLength={1}
                        value={code[index]}
                        onChange={(e) => handleInput(index, e.target.value)}
                        onKeyDown={(e) => handleKeyDown(index, e)}
                        className="w-12 h-12 text-center border rounded-lg text-2xl focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    />
                ))}
            </div>

            <div className="flex items-center justify-between text-sm">
                <button
                    onClick={handleResend}
                    disabled={timeLeft > 0}
                    className={`flex items-center ${
                        timeLeft > 0 ? 'text-gray-400' : 'text-blue-600 hover:text-blue-700'
                    }`}
                >
                    ارسال مجدد کد
                    {timeLeft > 0 && (
                        <>
                            <Clock className="w-4 h-4 mr-1" />
                            {Math.floor(timeLeft / 60)}:{String(timeLeft % 60).padStart(2, '0')}
                        </>
                    )}
                </button>
            </div>
        </div>
    );
};

export default TwoFactorAuth;
