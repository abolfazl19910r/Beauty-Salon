import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import type { ApiMeta } from '../api';
import { useApi } from '../session';
import type { PageMeta } from '../types';
import type {
  BookableDate,
  Catalog,
  ChargeOptions,
  CustomerBooking,
  CustomerNotification,
  CustomerWallet,
  DiscountCode,
  Home,
  Loyalty,
  LoyaltyEntry,
  PaymentStatus,
  Profile,
  RedeemResult,
  SalonDetail,
  Slot,
  Specialist,
  SpecialistDetail,
  WalletTransaction,
} from './types';

/** کلیدهای cache اپ مشتری؛ بعد از هر تغییر کلیدهای وابسته باطل می‌شوند */
export const ckeys = {
  home: ['c', 'home'] as const,
  salon: ['c', 'salon'] as const,
  services: ['c', 'services'] as const,
  serviceSpecialists: (serviceId: number) => ['c', 'service-specialists', serviceId] as const,
  specialists: ['c', 'specialists'] as const,
  specialist: (id: number) => ['c', 'specialist', id] as const,
  dates: (specialistId: number, serviceId: number) => ['c', 'dates', specialistId, serviceId] as const,
  slots: (specialistId: number, serviceId: number, date: string) => ['c', 'slots', specialistId, serviceId, date] as const,
  bookings: (scope: 'upcoming' | 'past') => ['c', 'bookings', scope] as const,
  booking: (id: number) => ['c', 'booking', id] as const,
  payment: (tx: string) => ['c', 'payment', tx] as const,
  wallet: ['c', 'wallet'] as const,
  walletTransactions: ['c', 'wallet-transactions'] as const,
  chargeOptions: ['c', 'charge-options'] as const,
  loyalty: ['c', 'loyalty'] as const,
  loyaltyHistory: ['c', 'loyalty-history'] as const,
  codes: ['c', 'codes'] as const,
  profile: ['c', 'profile'] as const,
  notifications: ['c', 'notifications'] as const,
};

const nextPage = (meta: ApiMeta) => {
  const m = meta as unknown as PageMeta;
  return m.current_page < m.last_page ? m.current_page + 1 : undefined;
};

/** هر تغییری در نوبت/پول این‌ها را کهنه می‌کند */
export function useRefreshMoney() {
  const qc = useQueryClient();
  return () => {
    void qc.invalidateQueries({ queryKey: ckeys.home });
    void qc.invalidateQueries({ queryKey: ['c', 'bookings'] });
    void qc.invalidateQueries({ queryKey: ['c', 'booking'] });
    void qc.invalidateQueries({ queryKey: ckeys.wallet });
    void qc.invalidateQueries({ queryKey: ckeys.walletTransactions });
    void qc.invalidateQueries({ queryKey: ckeys.loyalty });
    void qc.invalidateQueries({ queryKey: ['c', 'slots'] });
  };
}

export function useHome() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.home, queryFn: async () => (await api.get<Home>('/customer/home')).data });
}

export function useSalonDetail() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.salon, queryFn: async () => (await api.get<SalonDetail>('/customer/salon')).data, staleTime: 5 * 60_000 });
}

export function useServices() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.services, queryFn: async () => (await api.get<Catalog>('/customer/services')).data, staleTime: 5 * 60_000 });
}

export function useServiceSpecialists(serviceId: number) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.serviceSpecialists(serviceId),
    queryFn: async () => (await api.get<Specialist[]>(`/customer/services/${serviceId}/specialists`)).data,
    enabled: serviceId > 0,
  });
}

export function useSpecialists() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.specialists, queryFn: async () => (await api.get<Specialist[]>('/customer/specialists')).data, staleTime: 5 * 60_000 });
}

export function useSpecialist(id: number) {
  const api = useApi();
  return useQuery({ queryKey: ckeys.specialist(id), queryFn: async () => (await api.get<SpecialistDetail>(`/customer/specialists/${id}`)).data });
}

export function useDates(specialistId: number, serviceId: number) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.dates(specialistId, serviceId),
    queryFn: async () => (await api.get<BookableDate[]>(`/customer/specialists/${specialistId}/dates`, { service_id: serviceId })).data,
    enabled: specialistId > 0 && serviceId > 0,
  });
}

export function useSlots(specialistId: number, serviceId: number, date: string | null) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.slots(specialistId, serviceId, date ?? ''),
    queryFn: async () => (await api.get<Slot[]>(`/customer/specialists/${specialistId}/slots`, { date, service_id: serviceId })).data,
    enabled: specialistId > 0 && serviceId > 0 && !!date,
    staleTime: 0,
  });
}

export function useBookings(scope: 'upcoming' | 'past') {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: ckeys.bookings(scope),
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<CustomerBooking[]>('/customer/bookings', { scope, page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useBooking(id: number) {
  const api = useApi();
  return useQuery({ queryKey: ckeys.booking(id), queryFn: async () => (await api.get<CustomerBooking>(`/customer/bookings/${id}`)).data, enabled: id > 0 });
}

export function useBookingActions() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = useRefreshMoney();
  const done = (booking: CustomerBooking) => {
    qc.setQueryData(ckeys.booking(booking.id), booking);
    refresh();
  };
  return {
    cancel: useMutation({
      mutationFn: async (id: number) => (await api.post<CustomerBooking>(`/customer/bookings/${id}/cancel`)).data,
      onSuccess: done,
    }),
    reschedule: useMutation({
      mutationFn: async (args: { id: number; booking_time: string }) =>
        (await api.post<CustomerBooking>(`/customer/bookings/${args.id}/reschedule`, { booking_time: args.booking_time })).data,
      onSuccess: done,
    }),
    rate: useMutation({
      mutationFn: async (args: { id: number; rating: number; review?: string }) =>
        (await api.post<CustomerBooking>(`/customer/bookings/${args.id}/rate`, { rating: args.rating, review: args.review || undefined })).data,
      onSuccess: (b) => {
        done(b);
        void qc.invalidateQueries({ queryKey: ckeys.loyaltyHistory });
      },
    }),
  };
}

export function usePaymentStatus(tx: string, poll: boolean) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.payment(tx),
    queryFn: async () => (await api.get<PaymentStatus>(`/customer/payments/${tx}`)).data,
    enabled: !!tx,
    staleTime: 0,
    refetchInterval: (q) => (poll && q.state.data?.state === 'pending' ? 3000 : false),
  });
}

export function useWallet() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.wallet, queryFn: async () => (await api.get<CustomerWallet>('/customer/wallet')).data });
}

export function useWalletTransactions() {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: ckeys.walletTransactions,
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<WalletTransaction[]>('/customer/wallet/transactions', { page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useChargeOptions() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.chargeOptions, queryFn: async () => (await api.get<ChargeOptions>('/customer/wallet/charge')).data });
}

export function useLoyalty() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.loyalty, queryFn: async () => (await api.get<Loyalty>('/customer/loyalty')).data });
}

export function useLoyaltyHistory() {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: ckeys.loyaltyHistory,
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<LoyaltyEntry[]>('/customer/loyalty/history', { page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useDiscountCodes() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.codes, queryFn: async () => (await api.get<{ active: DiscountCode[]; expired: DiscountCode[] }>('/customer/loyalty/codes')).data });
}

export function useRedeem() {
  const api = useApi();
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (rewardId: number) => (await api.post<RedeemResult>(`/customer/loyalty/rewards/${rewardId}/redeem`)).data,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ckeys.loyalty });
      void qc.invalidateQueries({ queryKey: ckeys.codes });
      void qc.invalidateQueries({ queryKey: ckeys.loyaltyHistory });
      void qc.invalidateQueries({ queryKey: ckeys.home });
    },
  });
}

export function useProfile() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.profile, queryFn: async () => (await api.get<Profile>('/customer/profile')).data });
}

export function useUpdateProfile() {
  const api = useApi();
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (name: string) => (await api.request<Profile>('PATCH', '/customer/profile', { body: { name } })).data,
    onSuccess: (profile) => qc.setQueryData(ckeys.profile, profile),
  });
}

export function useNotifications() {
  const api = useApi();
  return useInfiniteQuery({
    queryKey: ckeys.notifications,
    initialPageParam: 1,
    queryFn: ({ pageParam }) => api.get<CustomerNotification[]>('/customer/notifications', { page: pageParam }),
    getNextPageParam: (last) => nextPage(last.meta),
  });
}

export function useNotificationMutations() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = () => {
    void qc.invalidateQueries({ queryKey: ckeys.notifications });
    void qc.invalidateQueries({ queryKey: ckeys.home });
  };
  return {
    read: useMutation({ mutationFn: async (id: string) => api.post(`/customer/notifications/${id}/read`), onSuccess: refresh }),
    readAll: useMutation({ mutationFn: async () => api.post('/customer/notifications/read-all'), onSuccess: refresh }),
  };
}
