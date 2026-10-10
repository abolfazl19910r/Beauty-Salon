import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import type { ApiMeta } from './api';
import { useApi } from './session';
import type {
  AppNotification,
  Booking,
  CalendarDay,
  DeviceToken,
  Leave,
  PageMeta,
  Schedule,
  Today,
  Transaction,
  Wallet,
  Withdrawal,
} from './types';

/** کلیدهای cache — پس از هر تغییر، کلیدهای وابسته باطل می‌شوند */
export const keys = {
  today: ['today'] as const,
  bookings: (filter: string) => ['bookings', filter] as const,
  booking: (id: number) => ['booking', id] as const,
  calendar: (from: string, to: string) => ['calendar', from, to] as const,
  schedule: ['schedule'] as const,
  leaves: ['leaves'] as const,
  wallet: ['wallet'] as const,
  transactions: ['transactions'] as const,
  withdrawals: ['withdrawals'] as const,
  notifications: ['notifications'] as const,
  tokens: ['tokens'] as const,
};

const nextPage = (meta: ApiMeta) => {
  const m = meta as unknown as PageMeta;
  return m.current_page < m.last_page ? m.current_page + 1 : undefined;
};

export function useToday() {
  const api = useApi();
  return useQuery({ queryKey: keys.today, queryFn: async () => (await api.get<Today>('/staff/today')).data });
}

export function useBookings(status: string | null, from: string, to: string) {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: keys.bookings(`${status ?? 'all'}:${from}:${to}`),
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<Booking[]>('/staff/bookings', { status, from, to, page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useBooking(id: number) {
  const api = useApi();
  return useQuery({ queryKey: keys.booking(id), queryFn: async () => (await api.get<Booking>(`/staff/bookings/${id}`)).data });
}

export function useBookingAction() {
  const api = useApi();
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (args: { id: number; action: 'confirm' | 'cancel' | 'complete'; reason?: string }) =>
      (await api.post<Booking>(`/staff/bookings/${args.id}/${args.action}`, args.action === 'cancel' ? { reason: args.reason } : {})).data,
    onSuccess: (booking) => {
      qc.setQueryData(keys.booking(booking.id), booking);
      void qc.invalidateQueries({ queryKey: keys.today });
      void qc.invalidateQueries({ queryKey: ['bookings'] });
      void qc.invalidateQueries({ queryKey: ['calendar'] });
    },
  });
}

export function useCalendar(from: string, to: string) {
  const api = useApi();
  return useQuery({ queryKey: keys.calendar(from, to), queryFn: async () => (await api.get<CalendarDay[]>('/staff/calendar', { from, to })).data });
}

export function useSchedule() {
  const api = useApi();
  return useQuery({ queryKey: keys.schedule, queryFn: async () => (await api.get<Schedule>('/staff/schedule')).data });
}

export function useSaveSchedule() {
  const api = useApi();
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (body: { schedules: unknown[]; auto_confirm_bookings: boolean }) => (await api.put<Schedule>('/staff/schedule', body)).data,
    onSuccess: (schedule) => {
      qc.setQueryData(keys.schedule, schedule);
      void qc.invalidateQueries({ queryKey: ['calendar'] });
    },
  });
}

export function useLeaves() {
  const api = useApi();
  return useQuery({ queryKey: keys.leaves, queryFn: async () => (await api.get<Leave[]>('/staff/leaves')).data });
}

export function useLeaveMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => {
    void qc.invalidateQueries({ queryKey: keys.leaves });
    void qc.invalidateQueries({ queryKey: ['calendar'] });
  };
  return {
    create: useMutation({
      mutationFn: async (body: { start_date: string; end_date: string; reason?: string }) => (await api.post<Leave>('/staff/leaves', body)).data,
      onSuccess: refresh,
    }),
    remove: useMutation({ mutationFn: async (id: number) => api.del(`/staff/leaves/${id}`), onSuccess: refresh }),
  };
}

export function useWallet() {
  const api = useApi();
  return useQuery({ queryKey: keys.wallet, queryFn: async () => (await api.get<Wallet>('/staff/wallet')).data });
}

export function useTransactions() {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: keys.transactions,
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<Transaction[]>('/staff/wallet/transactions', { page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useWithdrawals() {
  const api = useApi();
  return useQuery({ queryKey: keys.withdrawals, queryFn: async () => (await api.get<Withdrawal[]>('/staff/wallet/withdrawals')).data });
}

export function useWalletMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => {
    void qc.invalidateQueries({ queryKey: keys.wallet });
    void qc.invalidateQueries({ queryKey: keys.withdrawals });
    void qc.invalidateQueries({ queryKey: keys.transactions });
  };
  return {
    withdraw: useMutation({
      mutationFn: async (body: { amount: number; method: 'iban' | 'instant'; idempotency_key: string }) =>
        api.post<Withdrawal>('/staff/wallet/withdrawals', body),
      onSuccess: refresh,
    }),
    cancel: useMutation({ mutationFn: async (id: number) => api.del<Withdrawal>(`/staff/wallet/withdrawals/${id}`), onSuccess: refresh }),
    iban: useMutation({
      mutationFn: async (body: { iban: string; account_holder_name: string; bank_name: string; current_password: string }) =>
        api.put('/staff/wallet/iban', body),
      onSuccess: refresh,
    }),
  };
}

export function useNotifications() {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: keys.notifications,
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<AppNotification[]>('/staff/notifications', { page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useNotificationMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => void qc.invalidateQueries({ queryKey: keys.notifications });
  return {
    read: useMutation({ mutationFn: async (id: string) => api.post(`/staff/notifications/${id}/read`), onSuccess: refresh }),
    readAll: useMutation({ mutationFn: async () => api.post('/staff/notifications/read-all'), onSuccess: refresh }),
  };
}

export function useTokens() {
  const api = useApi();
  return useQuery({ queryKey: keys.tokens, queryFn: async () => (await api.get<DeviceToken[]>('/tokens')).data });
}

export function useTokenMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => void qc.invalidateQueries({ queryKey: keys.tokens });
  return {
    revoke: useMutation({ mutationFn: async (id: number) => api.del(`/tokens/${id}`), onSuccess: refresh }),
    revokeOthers: useMutation({ mutationFn: async () => api.post<{ revoked: number }>('/tokens/revoke-others'), onSuccess: refresh }),
  };
}
