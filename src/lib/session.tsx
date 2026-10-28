import { QueryCache, QueryClient, QueryClientProvider, MutationCache } from '@tanstack/react-query';
import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';

import { ApiError, type ApiClient, createClient } from './api';
import { APP_VERSION, DEFAULT_API_URL } from './config';
import { loadSalonTheme, setSalonTheme } from './salonTheme';
import { clearSession, loadServerOverride, loadSession, saveServerOverride, saveSession } from './storage';
import type { Account, TokenResponse } from './types';

type Status = 'loading' | 'signedOut' | 'signedIn';

type SessionValue = {
  status: Status;
  account: Account | null;
  api: ApiClient;
  serverUrl: string;
  /** سالن معلق یا اشتراکش تمام (403 salon_inactive) — صفحه‌ی جدا، بدون خروج */
  salonInactive: boolean;
  signIn: (response: TokenResponse) => Promise<void>;
  signOut: (options?: { revoke?: boolean }) => Promise<void>;
  setServerUrl: (url: string | null) => Promise<void>;
  recheckSalon: () => void;
  /** پذیرش دوباره‌ی قوانین (۲۰۲۶-۱۰-۲۸): تغییر مهمی که هنوز پذیرفته نشده؛ deferred = «فعلاً نه» تا رزرو بعدی یا اجرای بعدی اپ */
  termsPending: boolean;
  termsDeferred: boolean;
  setTermsPending: (pending: boolean) => void;
  deferTerms: () => void;
};

const SessionContext = createContext<SessionValue | null>(null);

export function useSession(): SessionValue {
  const value = useContext(SessionContext);
  if (!value) {
    throw new Error('useSession outside SessionProvider');
  }
  return value;
}

export function useApi(): ApiClient {
  return useSession().api;
}

export function SessionProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<Status>('loading');
  const [account, setAccount] = useState<Account | null>(null);
  const [serverUrl, setServerUrlState] = useState(DEFAULT_API_URL);
  const [salonInactive, setSalonInactive] = useState(false);
  const [termsPending, setTermsPendingState] = useState(false);
  const [termsDeferred, setTermsDeferred] = useState(false);
  const tokenRef = useRef<string | null>(null);
  const serverRef = useRef(DEFAULT_API_URL);
  const signOutRef = useRef<() => void>(() => {});

  const queryClient = useMemo(() => {
    // خطاهای سراسری: سالن غیرفعال → صفحه‌ی جدا؛ wrong_app (مثلاً متخصص حذف شد) → خروج
    const onError = (error: unknown) => {
      if (error instanceof ApiError) {
        if (error.code === 'salon_inactive') {
          setSalonInactive(true);
        } else if (error.code === 'wrong_app') {
          signOutRef.current();
        }
      }
    };

    return new QueryClient({
      queryCache: new QueryCache({ onError }),
      mutationCache: new MutationCache({ onError }),
      defaultOptions: {
        queries: {
          staleTime: 30_000,
          // خطای منطقی سرور تکرار نمی‌شود؛ فقط خطای شبکه (حداکثر ۲ بار)
          retry: (count, error) => error instanceof ApiError && error.code === 'network_error' && count < 2,
        },
      },
    });
  }, []);

  const api = useMemo(
    () =>
      createClient({
        baseUrl: () => serverRef.current,
        token: () => tokenRef.current,
        appVersion: APP_VERSION,
        onUnauthorized: () => signOutRef.current(),
        onErrorCode: (code) => {
          if (code === 'terms_update_required') {
            // نوبت یا دوره‌ی تازه تا پذیرش نسخه‌ی تازه بسته است → صفحه‌ی «قوانین به‌روز شد» (هر مسیر درخواستی)
            setTermsPendingState(true);
            setTermsDeferred(false);
          }
        },
      }),
    [],
  );

  const signOut = useCallback(
    async (options?: { revoke?: boolean }) => {
      if (options?.revoke && tokenRef.current) {
        // خروج واقعی از سرور (ابطال همین توکن)؛ اگر نشد هم محلی خارج می‌شویم
        await api.post('/logout').catch(() => undefined);
      }
      tokenRef.current = null;
      await clearSession();
      queryClient.clear();
      setAccount(null);
      setSalonInactive(false);
      setTermsPendingState(false);
      setTermsDeferred(false);
      setStatus('signedOut');
    },
    [api, queryClient],
  );

  const setTermsPending = useCallback((pending: boolean) => {
    setTermsPendingState(pending);
    setTermsDeferred(false);
  }, []);
  const deferTerms = useCallback(() => setTermsDeferred(true), []);

  signOutRef.current = () => {
    void signOut();
  };

  useEffect(() => {
    (async () => {
      await loadSalonTheme();
      const override = await loadServerOverride();
      if (override) {
        serverRef.current = override;
        setServerUrlState(override);
      }
      const session = await loadSession();
      if (session) {
        tokenRef.current = session.token;
        setAccount(session.account);
        setStatus('signedIn');
      } else {
        setStatus('signedOut');
      }
    })();
  }, []);

  // ⭐ بسته‌ی «و»: قالب سالن از حساب (ورود یا نشست ذخیره‌شده) و یک بار تازه از /me هر بار که اپ باز می‌شود
  useEffect(() => {
    if (account?.salon && 'theme' in account.salon) {
      setSalonTheme(account.salon.theme);
    }
  }, [account]);

  useEffect(() => {
    if (status !== 'signedIn') {
      return;
    }
    api
      .get<Account>('/me')
      .then((res) => {
        const salon = (res.data as Account | undefined)?.salon;
        if (salon && 'theme' in salon) {
          setSalonTheme(salon.theme);
        }
        const pending = (res.data as Account | undefined)?.legal_pending;
        if (Array.isArray(pending)) {
          setTermsPendingState(pending.length > 0);
        }
      })
      .catch(() => undefined);
  }, [status, api]);

  const value: SessionValue = {
    status,
    account,
    api,
    serverUrl,
    salonInactive,
    signIn: async (response) => {
      tokenRef.current = response.token;
      await saveSession(response.token, response.account);
      setAccount(response.account);
      setSalonInactive(false);
      setTermsPendingState((response.account.legal_pending?.length ?? 0) > 0);
      setTermsDeferred(false);
      setStatus('signedIn');
    },
    signOut,
    setServerUrl: async (url) => {
      await saveServerOverride(url);
      serverRef.current = url ?? DEFAULT_API_URL;
      setServerUrlState(serverRef.current);
    },
    recheckSalon: () => {
      setSalonInactive(false);
      void queryClient.invalidateQueries();
    },
    termsPending,
    termsDeferred,
    setTermsPending,
    deferTerms,
  };

  return (
    <SessionContext.Provider value={value}>
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    </SessionContext.Provider>
  );
}
