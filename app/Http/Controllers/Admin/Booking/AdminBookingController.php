<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Exceptions\BookingNotAvailableException;
use App\Http\Requests\Admin\Booking\StoreAdminBookingRequest;
use App\Http\Requests\Admin\Booking\UpdateAdminBookingRequest;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\Booking\AdminBookingService;
use App\Services\Booking\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function __construct(
        protected readonly AdminBookingService $bookingService,
        // ⭐ Fix (fix/admin-booking-slot-conflict, commit 2): shared with the online booking flow
        // specifically so both paths run through the exact same availability check — see
        // BookingService::createManualBooking().
        protected readonly BookingService $sharedBookingService,
    ) {}

    public function index(Request $request): View
    {
        $query = Booking::with(['user', 'specialist', 'service'])->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $hasDateFilter = $request->filled('date');

        if ($hasDateFilter) {
            $date = $request->date;
            $query->whereDate('booking_time', $date);
        }

        $bookings = $query->paginate(15)->withQueryString();

        $statsQuery = Booking::query();
        if ($hasDateFilter) {
            $statsQuery->whereDate('booking_time', $date);
        }

        $totalBookings = (clone $statsQuery)->count();
        $confirmedBookings = (clone $statsQuery)->where('status', 'confirmed')->count();
        $cancelledBookings = (clone $statsQuery)->where('status', 'cancelled')->count();

        return view('admin.bookings.index', compact(
            'bookings',
            'totalBookings',
            'confirmedBookings',
            'cancelledBookings',
            'hasDateFilter'
        ));
    }

    public function create(): View
    {
        // ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): $users (User::all()) removed —
        // it loaded every user in the system into one <select>, and had no path at all for a
        // walk-in/phone customer with no existing account. The view now uses an AJAX
        // search/quick-create widget (AdminBookingCustomerController) instead.
        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('admin.bookings.create', compact('services', 'specialists'));
    }

    public function store(StoreAdminBookingRequest $request): RedirectResponse
    {
        // ⭐ Fix (fix/admin-booking-slot-conflict, commit 2): previously this was a bare
        // Booking::create($request->validated()) with no availability check at all — a manually
        // entered phone/walk-in booking could silently collide with an online booking (or another
        // manual one) for the same specialist+time. createManualBooking() runs the same slot
        // check the online flow uses, plus a DB-level unique-index fallback for the race-condition
        // case (see migration 2026_08_29_000001_add_active_slot_key_to_bookings_table).
        try {
            $booking = $this->sharedBookingService->createManualBooking($request->validated());
        } catch (BookingNotAvailableException $e) {
            return back()->withInput()
                ->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
        }

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    public function edit(Booking $booking): View
    {
        $users = User::all();
        $services = BeautyService::all();
        $specialists = Specialist::all();

        return view('admin.bookings.edit', compact('booking', 'users', 'services', 'specialists'));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['service', 'user', 'specialist']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function update(UpdateAdminBookingRequest $request, Booking $booking): RedirectResponse
    {
        $redirectRoute = $request->isStatusOnly() ? 'admin.bookings.index' : 'admin.bookings.show';
        $redirectParams = $request->isStatusOnly() ? [] : ['booking' => $booking->id];

        try {
            $result = $request->isStatusOnly()
                ? $this->bookingService->updateStatus($booking, $request->validated()['status'])
                : $this->bookingService->updateFull($booking, $request->validated());

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('success', $result['message']);

        } catch (BookingNotAvailableException $e) {
            // ⭐ Fix (fix/admin-booking-slot-conflict, commit 4): caught ahead of the generic
            // \Exception below so a slot conflict gets its own clear Persian message instead of
            // the generic "خطایی...رخ داد" — same wording used on the create form (commit 2).
            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
        } catch (\Exception $e) {
            return redirect()->route($redirectRoute, $redirectParams)
                ->with('error', 'خطایی در بروزرسانی وضعیت نوبت رخ داد. لطفا مجددا تلاش کنید.');
        }
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('admin.bookings.index')
                ->with('error', 'نوبت‌های پرداخت شده را نمی‌توان حذف کرد. ابتدا آن را لغو کنید.');
        }

        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'نوبت با موفقیت حذف شد.');
    }
}
