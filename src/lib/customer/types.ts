/** شکل پاسخ‌های /api/v1/customer/* (بسته‌ی ۳الف لاراول، CustomerPresenter و کنترلرهای Customer*) */

export type SalonCard = {
  id: number;
  slug: string;
  name: string;
  tagline: string | null;
  logo_url: string | null;
  public_url: string;
};

export type SalonDetail = SalonCard & {
  bio: string | null;
  address: string | null;
  phone: string | null;
  working_hours: string[];
  experience_years: number | null;
  online_payment: boolean;
  /** امکانات پلن سالن (مثلاً treatment_courses) */
  features?: string[];
  rules: {
    prepayment_percentage: number;
    minimum_prepayment: number;
    payment_window_minutes: number;
    cancel_deadline_hours: number;
    cancellation_fee_within_hours: number;
    cancellation_fee_percentage: number;
  };
};

export type Service = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  duration_minutes: number;
  prepayment_amount: number;
  image_url: string | null;
  category: { id: number; name: string } | null;
};

export type Catalog = { categories: { id: number; name: string }[]; services: Service[] };

export type Specialist = {
  id: number;
  name: string;
  photo_url?: string | null;
  rating_avg?: number;
  rating_count?: number;
  completed_bookings?: number;
  services?: { id: number; name: string }[];
};

export type SpecialistDetail = Specialist & {
  reviews: { customer: string; rating: number; review: string | null; date_jalali: string }[];
};

export type BookableDate = { date: string; date_jalali: string; label: string };

export type Slot = { time: string; booking_time: string };

export type CustomerBookingStatus = 'pending_payment' | 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';

export type CustomerBooking = {
  id: number;
  status: CustomerBookingStatus;
  status_label: string;
  payment_status: 'paid' | 'unpaid' | string;
  booking_time: string | null;
  booking_time_jalali: string | null;
  booking_time_label: string | null;
  duration_minutes: number | null;
  service: { id: number; name: string; price: number } | null;
  specialist: { id: number; name: string; photo_url: string | null } | null;
  prepayment_amount: number;
  discount_code: string | null;
  discount_amount: number;
  remaining_amount: number;
  paid_at: string | null;
  pay_before: string | null;
  cancellation_reason: string | null;
  cancelled_by: string | null;
  rating: number | null;
  review: string | null;
  /** جلسه‌ی دوره‌ی درمانی؛ null برای نوبت عادی */
  course?: CourseBrief | null;
  actions: { pay: boolean; cancel: boolean; reschedule: boolean; rate: boolean };
};

export type Gateway = {
  id: number;
  name: string;
  driver: string;
  fee: number;
  total: number;
  fee_percent: number;
  fee_fixed: number;
};

export type Quote = {
  service: Service;
  price: number;
  prepayment_amount: number;
  discount_code: string | null;
  discount_amount: number;
  remaining_amount: number;
  online_payment: boolean;
  gateways: Gateway[];
  wallet_balance: number;
};

export type PaymentInfo = { required: boolean; amount: number; gateways: Gateway[]; wallet_balance: number };

/** نتیجه‌ی شروع پرداخت (pay یا شارژ): یا همان‌جا پرداخت شد، یا آدرس صفحه‌ی بانک در مرورگر */
export type PayStart =
  | { state: 'paid'; booking: CustomerBooking }
  | { state: 'redirect'; checkout_url: string; transaction: string; gateway: string | null; fee: number; expires_in: number };

export type PaymentState = 'pending' | 'paid' | 'failed' | 'refunded';

export type PaymentStatus = {
  transaction: string;
  purpose: 'booking' | 'wallet_charge' | 'course';
  state: PaymentState;
  amount: number;
  fee: number;
  ref_id: string | null;
  created_at: string | null;
  booking?: CustomerBooking | null;
  credited?: boolean;
  wallet_balance?: number;
  course?: Course | null;
};

export type Home = {
  salon: SalonCard;
  upcoming_bookings: CustomerBooking[];
  awaiting_payment_count: number;
  loyalty_points: number;
  wallet_balance: number;
  unread_notifications: number;
  features?: string[];
};

export type CustomerWallet = { balance: number; total_deposited: number; total_spent: number };

export type WalletTransaction = {
  id: number;
  type: 'deposit' | 'payment' | 'refund' | 'adjustment' | string;
  amount: number;
  balance_after: number;
  description: string | null;
  booking_id: number | null;
  created_at: string | null;
  created_at_jalali: string | null;
};

export type ChargeOptions = { online_payment: boolean; min: number; max: number; suggested: number[]; gateways: Gateway[] };

export type Reward = {
  id: number;
  title: string;
  description: string | null;
  required_points: number;
  discount_type: 'fixed' | 'percentage';
  discount_amount: number;
  can_redeem: boolean;
};

export type Loyalty = {
  points: number;
  expiring_points: number;
  next_reward: { id: number; title: string; points_needed: number; progress_percentage: number } | null;
  rewards: Reward[];
};

export type LoyaltyEntry = {
  id: number;
  points: number;
  type: 'earned' | 'spent' | 'expired' | string;
  description: string | null;
  booking_id: number | null;
  expires_at: string | null;
  created_at: string | null;
  created_at_jalali: string | null;
};

export type DiscountCode = {
  code: string;
  type: 'fixed' | 'percentage';
  amount: number;
  expires_at: string | null;
  expires_at_jalali: string | null;
  remaining_uses: number;
};

export type RedeemResult = { code: string; type: 'fixed' | 'percentage'; amount: number; expires_at: string | null; points: number };

/** marketing_sms: پیامک‌های پیگیری و پیشنهاد سالن (پیش‌فرض روشن)؛ پیامک‌های نوبت جدا و همیشه روشن‌اند */
export type Profile = { id: number; name: string; phone: string; marketing_sms: boolean; created_at: string | null };

export type CustomerNotification = {
  id: string;
  type: string;
  title: string | null;
  message: string;
  booking_id: number | null;
  read: boolean;
  created_at: string | null;
  created_at_jalali: string | null;
};


// ─── دوره‌ی درمانی (بسته‌ی ب؛ CoursePresenter لاراول) ────────────────────

export type CourseBrief = {
  id: number;
  name: string;
  session_number: number | null;
  sessions_total: number;
  label: string | null;
  photo_consent: boolean;
};

export type CoursePackage = {
  id: number;
  name: string;
  description: string | null;
  sessions_count: number;
  interval_days: number;
  validity_days: number | null;
  price: number;
  list_price: number;
  saving: number;
  min_prepayment: number;
  service: { id: number; name: string; duration_minutes: number } | null;
  services?: { id: number; name: string }[];
  session_minutes?: number;
  specialist: { id: number; name: string } | null;
};

export type CourseStatus = 'pending_payment' | 'active' | 'completed' | 'expired' | 'cancelled';

export type Course = {
  id: number;
  name: string;
  status: CourseStatus;
  status_label: string;
  sessions_total: number;
  sessions_used: number;
  sessions_burned: number;
  sessions_reserved: number;
  sessions_remaining: number;
  paid_sessions: number;
  price_total: number;
  amount_paid: number;
  balance_due: number;
  min_prepayment: number;
  discount_amount: number;
  service_id: number;
  services_label?: string;
  session_minutes?: number;
  specialist_id: number | null;
  purchased_at: string | null;
  expires_at: string | null;
  expires_at_jalali: string | null;
  next_due_at: string | null;
  photo_consent: boolean;
  refunded_amount: number;
  actions: { pay: boolean; book: boolean };
  book_blocker: string | null;
};

export type CoursePhoto = { id: number; kind: 'before' | 'after'; url: string };

export type CourseSession = {
  booking_id: number;
  session_number: number | null;
  label: string | null;
  status: CustomerBookingStatus;
  burned: boolean;
  booking_time: string | null;
  booking_time_jalali: string | null;
  specialist: { id: number; name: string } | null;
  note: string | null;
  note_visible_to_customer: boolean | null;
  photos: CoursePhoto[];
};

export type CourseDetail = Course & {
  sessions: CourseSession[];
  payments: { amount: number; method: string; created_at: string | null }[];
};

export type CoursePayOptions = { balance_due: number; min_amount: number; wallet_balance: number; online_payment: boolean; gateways: Gateway[] };

export type CoursePayStart =
  | { state: 'paid'; course: Course }
  | { state: 'redirect'; checkout_url: string; transaction: string; gateway: string | null; fee: number; expires_in: number };
