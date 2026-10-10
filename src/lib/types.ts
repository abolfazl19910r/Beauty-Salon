/** شکل پاسخ‌های /api/v1 (بسته‌های ۱ و ۲الف لاراول) */

export type Account = {
  user: { id: number; name: string; phone: string; type: 'staff' | 'customer' };
  salon: { id: number; slug: string; name: string; logo_url: string | null; public_url: string };
  specialist?: { id: number; name: string };
};

export type Challenge = {
  challenge: string;
  expires_in: number;
  code_expires_in: number;
  resend_after: number;
  phone_hint: string;
};

export type TokenResponse = {
  token: string;
  token_type: 'Bearer';
  token_id: number;
  expires_at: string;
  account: Account;
};

export type BookingStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'pending_payment';

export type Booking = {
  id: number;
  status: BookingStatus;
  status_label: string;
  payment_status: 'paid' | 'unpaid';
  booking_time: string;
  booking_time_jalali: string;
  duration_minutes: number | null;
  service: { id: number; name: string; price: number } | null;
  customer: { id: number; name: string; phone: string } | null;
  prepayment_amount: number;
  discount_amount: number;
  remaining_amount: number;
  source: string | null;
  notes: string | null;
  cancellation_reason: string | null;
  cancelled_by: string | null;
  actions: { confirm: boolean; cancel: boolean; complete: boolean };
};

export type Today = {
  date: string;
  date_jalali: string;
  date_label: string;
  summary: { count: number; pending_count: number; revenue: number };
  bookings: Booking[];
};

export type PageMeta = { current_page: number; per_page: number; total: number; last_page: number };

export type WorkingHours = { start_time: string | null; end_time: string | null; break_start: string | null; break_end: string | null };

export type CalendarDay = {
  date: string;
  date_jalali: string;
  weekday: number;
  weekday_label: string;
  working_hours: WorkingHours | null;
  holiday: { description: string | null } | null;
  leave: { id: number; status: 'pending' | 'approved' } | null;
  bookings: Booking[];
};

export type ScheduleDay = WorkingHours & { day_of_week: number; weekday_label: string; is_active: boolean };

export type Schedule = { auto_confirm_bookings: boolean; schedules: ScheduleDay[] };

export type Leave = {
  id: number;
  start_date: string;
  end_date: string;
  start_date_jalali: string;
  end_date_jalali: string;
  status: 'pending' | 'approved' | 'rejected';
  reason: string | null;
  reject_reason: string | null;
};

export type Wallet = {
  balance: number;
  total_earned: number;
  total_withdrawn: number;
  pending_amount: number;
  month_income: number;
  month_withdrawals: number;
  iban: { masked: string; account_holder_name: string | null; bank_name: string | null; verified: boolean } | null;
  withdrawal_limits: { minimum: number; maximum: number };
};

export type Transaction = {
  id: number;
  type: string;
  amount: number;
  balance_after: number | null;
  description: string | null;
  booking_id: number | null;
  created_at: string;
  created_at_jalali: string;
};

export type Withdrawal = {
  id: number;
  reference_code: string | null;
  amount: number;
  fee: number;
  net_amount: number;
  method: 'iban' | 'instant';
  status: string;
  rejection_reason: string | null;
  can_cancel: boolean;
  created_at: string;
  created_at_jalali: string;
};

export type AppNotification = {
  id: string;
  category: string;
  message: string;
  booking_id: number | null;
  read: boolean;
  created_at: string;
  created_at_jalali: string;
};

export type DeviceToken = {
  id: number;
  name: string;
  current: boolean;
  created_at: string | null;
  last_used_at: string | null;
  expires_at: string | null;
};
