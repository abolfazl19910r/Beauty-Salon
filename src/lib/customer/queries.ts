import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import type { ApiMeta } from '../api';
import { useApi } from '../session';
import type { PageMeta } from '../types';
import type {
  BookableDate,
  Catalog,
  ChargeOptions,
  Course,
  CourseDetail,
  CoursePackage,
  CoursePayOptions,
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
  coursePackages: ['c', 'course-packages'] as const,
  courses: ['c', 'courses'] as const,
  course: (id: number) => ['c', 'course', id] as const,
  courseSpecialists: (id: number) => ['c', 'course-specialists', id] as const,
  courseDates: (id: number, specialistId: number) => ['c', 'course-dates', id, specialistId] as const,
  courseSlots: (id: number, specialistId: number, date: string) => ['c', 'course-slots', id, specialistId, date] as const,
  coursePay: (id: number) => ['c', 'course-pay', id] as const,
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
    void qc.invalidateQueries({ queryKey: ckeys.courses });
    void qc.invalidateQueries({ queryKey: ['c', 'course'] });
    void qc.invalidateQueries({ queryKey: ['c', 'course-slots'] });
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
    mutationFn: async (changes: { name?: string; marketing_sms?: boolean }) =>
      (await api.request<Profile>('PATCH', '/customer/profile', { body: changes })).data,
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

// ─── دوره‌های درمانی (بسته‌ی ب) ─────────────────────────────────────────

/** بسته‌های فروش آنلاین؛ ۴۰۴ (not_found) یعنی امکان در پلن سالن نیست — صفحه آن را «فروش آنلاین ندارد» نشان می‌دهد */
export function useCoursePackages(enabled = true) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.coursePackages,
    queryFn: async () => (await api.get<CoursePackage[]>('/customer/courses/packages')).data,
    enabled,
    retry: false,
  });
}

export function useCourses() {
  const api = useApi();
  return useQuery({ queryKey: ckeys.courses, queryFn: async () => (await api.get<Course[]>('/customer/courses')).data });
}

export function useCourse(id: number) {
  const api = useApi();
  return useQuery({ queryKey: ckeys.course(id), queryFn: async () => (await api.get<CourseDetail>(`/customer/courses/${id}`)).data, enabled: id > 0 });
}

export function useCourseSpecialists(id: number) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.courseSpecialists(id),
    queryFn: async () => (await api.get<Specialist[]>(`/customer/courses/${id}/specialists`)).data,
    enabled: id > 0,
  });
}

export function useCourseDates(id: number, specialistId: number) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.courseDates(id, specialistId),
    queryFn: async () => (await api.get<{ from: string; to: string; dates: string[] }>(`/customer/courses/${id}/dates`, { specialist_id: specialistId })).data,
    enabled: id > 0 && specialistId > 0,
  });
}

export function useCourseSlots(id: number, specialistId: number, date: string | null) {
  const api = useApi();
  return useQuery({
    queryKey: ckeys.courseSlots(id, specialistId, date ?? ''),
    queryFn: async () => (await api.get<{ date: string; slots: string[] }>(`/customer/courses/${id}/slots`, { specialist_id: specialistId, date })).data,
    enabled: id > 0 && specialistId > 0 && !!date,
    staleTime: 0,
  });
}

export function useCoursePayOptions(id: number) {
  const api = useApi();
  return useQuery({ queryKey: ckeys.coursePay(id), queryFn: async () => (await api.get<CoursePayOptions>(`/customer/courses/${id}/pay`)).data, enabled: id > 0, staleTime: 0 });
}

export function useCourseActions() {
  const api = useApi();
  const qc = useQueryClient();
  const refresh = useRefreshMoney();
  return {
    purchase: useMutation({
      mutationFn: async (args: { packageId: number; discount_code?: string }) =>
        (await api.post<Course>(`/customer/courses/packages/${args.packageId}/purchase`, { discount_code: args.discount_code || undefined })).data,
      onSuccess: (course) => {
        void qc.invalidateQueries({ queryKey: ckeys.courses });
        qc.removeQueries({ queryKey: ckeys.course(course.id) });
      },
    }),
    book: useMutation({
      mutationFn: async (args: { courseId: number; specialist_id: number; date: string; time: string }) =>
        (await api.post<{ booking: CustomerBooking; course: Course }>(`/customer/courses/${args.courseId}/bookings`, {
          specialist_id: args.specialist_id, date: args.date, time: args.time,
        })).data,
      onSuccess: refresh,
    }),
    deletePhoto: useMutation({
      mutationFn: async (args: { courseId: number; photoId: number }) => api.del(`/customer/course-photos/${args.photoId}`),
      onSuccess: (_r, args) => void qc.invalidateQueries({ queryKey: ckeys.course(args.courseId) }),
    }),
  };
}
