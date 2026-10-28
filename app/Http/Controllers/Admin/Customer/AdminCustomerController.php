<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomerCourse;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * پرونده‌ی مشتری در پنل مدیر (۲۰۲۶-۱۰-۱۴، همراه بسته‌ی ب): فهرست مشتری‌های همین سالن با جست‌وجو و صفحه‌ی هر مشتری — آخرین نوبت‌ها،
 * کیف پول، امتیاز باشگاه و دوره‌های درمانی (بعد از تنزل پلن هم دیده می‌شوند؛ دکمه‌ی فروش دوره فقط با امکان). فقط خواندنی؛ کارها در
 * صفحه‌های خودشان (نوبت، دوره) انجام می‌شود. «کاربران» پنل مدیر فقط کادر سالن است.
 */
class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:50']]);
        $salonId = (int) app(CurrentSalon::class)->id();

        $customers = User::query()
            ->where('salon_id', $salonId)
            ->where('user_type', 'customer')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$v.'%')->orWhere('phone', 'like', '%'.$v.'%')))
            ->withCount(['bookings as completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->withMax(['bookings as last_visit_at' => fn ($q) => $q->where('status', 'completed')], 'booking_time')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'filters'));
    }

    public function show(int $customer, LoyaltyService $loyalty): View
    {
        $salon = app(CurrentSalon::class)->get();
        $user = User::query()->where('salon_id', $salon?->id)->where('user_type', 'customer')->findOrFail($customer);

        $bookings = Booking::query()->where('user_id', $user->id)->with(['service', 'specialist'])->latest('booking_time')->limit(10)->get();
        $stats = [
            'completed' => Booking::query()->where('user_id', $user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::query()->where('user_id', $user->id)->where('status', 'cancelled')->count(),
            'last_visit' => Booking::query()->where('user_id', $user->id)->where('status', 'completed')->max('booking_time'),
        ];

        return view('admin.customers.show', [
            'customer' => $user,
            'bookings' => $bookings,
            'stats' => $stats,
            'courses' => CustomerCourse::query()->forCustomer($user->id)->latest('id')->get(),
            'canSellCourses' => (bool) $salon?->hasFeature(Feature::TreatmentCourses),
            'walletBalance' => (float) ($user->wallet()->first()?->balance ?? 0),
            'points' => $loyalty->getCurrentPoints($user->id),
            'noShow' => $this->noShowSummary($user),
        ]);
    }

    /** بسته‌ی 5ه، تصمیم ۱۶: مدیر محدودیت مشتری بدقول را دستی برمی‌دارد — سابقه می‌ماند، «نیامد»های پیش از این لحظه شمرده نمی‌شوند */
    public function clearNoShow(int $customer): \Illuminate\Http\RedirectResponse
    {
        $salon = app(CurrentSalon::class)->get();
        $user = User::query()->where('salon_id', $salon?->id)->where('user_type', 'customer')->findOrFail($customer);
        $user->forceFill(['no_show_cleared_at' => now()])->save();
        activity()->performedOn($user)->causedBy(auth()->user())->log('customer_no_show_cleared');

        return redirect()->route('admin.customers.show', $user->id)->with('success', 'محدودیت مشتری برداشته شد؛ از این پس مثل بقیه‌ی مشتری‌ها رزرو می‌کند.');
    }

    private function noShowSummary(User $user): array
    {
        $record = app(\App\Services\Booking\NoShowRecord::class);
        $policy = \App\Models\BookingPolicy::current();
        $label = $record->label($user, $policy);
        $payment = app(\App\Services\Booking\BookingPaymentPolicy::class);

        return [
            'count' => $record->count($user, $policy),
            'days' => (int) $policy->no_show_window_days,
            'label' => $label,
            'effect' => $label === null ? null : ($payment->flaggedWithoutPrepayment($user)
                ? ($policy->flagged_force_pending ? 'نوبت آنلاینش همیشه منتظر تأیید شما می‌ماند و سقف نوبت فعالش کمتر است.' : 'سقف نوبت فعالش کمتر است.')
                : 'فقط با پیش‌پرداخت آنلاین می‌تواند رزرو کند.'),
        ];
    }
}
