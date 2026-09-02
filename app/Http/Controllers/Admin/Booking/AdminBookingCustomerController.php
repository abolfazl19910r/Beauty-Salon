<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\QuickCreateCustomerRequest;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 3; updated for the customer identity redesign,
 * 2026-08-30): backs the customer search/quick-create widget on
 * admin/bookings/create.blade.php — replaces the old User::all() <select> so an admin entering a
 * phone/walk-in booking can find an existing customer by phone, or create a minimal customer
 * record on the spot, without leaving the booking form. Deliberately its own small controller
 * (not folded into AdminBookingController) since it's a distinct concern — customer lookup, not
 * booking creation — following this project's established pattern of splitting controllers by
 * responsibility rather than growing one further.
 */
class AdminBookingCustomerController extends Controller
{
    public function __construct(protected readonly CurrentSalon $currentSalon) {}

    public function search(Request $request): JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));

        if (mb_strlen($phone) < 3) {
            return response()->json(['customers' => []]);
        }

        // ⭐ Customer identity redesign: scoped to this salon's own customers — without this,
        // an admin could find (and silently attach a booking to) a customer belonging to a
        // completely different salon, which is exactly the cross-tenant leak the whole
        // BelongsToSalon/salon_id effort exists to prevent. User itself isn't a BelongsToSalon
        // model (see that trait's docblock for why: admin/specialist rows would break under a
        // blanket salon_id filter), so this filter is applied explicitly here instead.
        $customers = User::query()
            ->where('user_type', 'customer')
            ->where('salon_id', $this->currentSalon->id())
            ->where(function ($query) use ($phone) {
                $query->where('phone', 'like', "%{$phone}%")
                    ->orWhere('name', 'like', "%{$phone}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'phone']);

        return response()->json(['customers' => $customers]);
    }

    public function quickCreate(QuickCreateCustomerRequest $request): JsonResponse
    {
        $customer = User::create([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            // ⭐ Walk-in/phone customers created from this widget authenticate normally later
            // via the app's SMS-OTP login (see Auth\SendResetCodeRequest / login flow) — they
            // never need this password directly, so a random one is sufficient and safer than
            // any fixed/predictable default.
            'password' => Hash::make(Str::random(40)),
            // ⭐ Customer identity redesign: this person is a customer OF the salon whose admin
            // is currently entering their walk-in booking — same as a self-registered customer,
            // just provisioned by staff instead of through /s/{slug}/register.
            'salon_id' => $this->currentSalon->id(),
            'user_type' => 'customer',
        ]);

        return response()->json([
            'customer' => $customer->only(['id', 'name', 'phone']),
        ], 201);
    }
}
