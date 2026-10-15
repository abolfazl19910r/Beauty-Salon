import { createContext, type ReactNode, useContext, useEffect, useState } from 'react';

import { loadSalonTheme, setSalonTheme } from '../salonTheme';
import { loadSalon, saveSalon } from './store';
import type { SalonCard } from './types';

/** سالنی که اپ مشتری به آن وصل است (پیش از ورود هم لازم است: صفحه‌ی ورود کارت همان سالن را نشان می‌دهد). */
type SalonValue = {
  ready: boolean;
  salon: SalonCard | null;
  setSalon: (salon: SalonCard | null) => Promise<void>;
};

const SalonContext = createContext<SalonValue | null>(null);

export function useCustomerSalon(): SalonValue {
  const value = useContext(SalonContext);
  if (!value) {
    throw new Error('useCustomerSalon outside CustomerSalonProvider');
  }
  return value;
}

export function CustomerSalonProvider({ children }: { children: ReactNode }) {
  const [ready, setReady] = useState(false);
  const [salon, setSalonState] = useState<SalonCard | null>(null);

  useEffect(() => {
    // بسته‌ی «و»: آخرین قالب ذخیره‌شده پیش از رسیدن پاسخ سرور، بعد قالب کارت همین سالن
    void loadSalonTheme();
    loadSalon().then((s) => {
      setSalonState(s);
      if (s && 'theme' in s) {
        setSalonTheme(s.theme);
      }
      setReady(true);
    });
  }, []);

  const setSalon = async (next: SalonCard | null) => {
    await saveSalon(next);
    setSalonState(next);
    // سالن دیگر ← قالب همان سالن؛ بی سالن ← پیش‌فرض
    setSalonTheme(next?.theme ?? null);
  };

  return <SalonContext.Provider value={{ ready, salon, setSalon }}>{children}</SalonContext.Provider>;
}
