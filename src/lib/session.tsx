import { QueryCache, QueryClient, QueryClientProvider, MutationCache } from '@tanstack/react-query';
import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';

import { ApiError, type ApiClient, createClient } from './api';
import { APP_VERSION, DEFAULT_API_URL } from './config';
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
      setStatus('signedOut');
    },
    [api, queryClient],
  );

  signOutRef.current = () => {
    void signOut();
  };

  useEffect(() => {
    (async () => {
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
  };

  return (
    <SessionContext.Provider value={value}>
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    </SessionContext.Provider>
  );
}
