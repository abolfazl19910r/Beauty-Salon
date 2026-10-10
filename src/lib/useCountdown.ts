import { useEffect, useState } from 'react';

/** شمارش معکوس ثانیه‌ای (فاصله‌ی ارسال دوباره‌ی کد) */
export function useCountdown(initialSeconds: number) {
  const [left, setLeft] = useState(initialSeconds);

  useEffect(() => {
    if (left <= 0) {
      return;
    }
    const timer = setTimeout(() => setLeft((s) => s - 1), 1000);
    return () => clearTimeout(timer);
  }, [left]);

  return { left, restart: (seconds: number) => setLeft(seconds) };
}
