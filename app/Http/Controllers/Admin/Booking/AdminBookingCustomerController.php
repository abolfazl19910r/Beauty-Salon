<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Booking\QuickCreateCustomerRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): backs the customer search/quick-create
 * widget on admin/bookings/create.blade.php — replaces the old User::all() <select> so an admin
 * entering a phone/walk-in booking can find an existing customer by phone, or create a minimal
 * customer record on the spot, without leaving the booking form. Deliberately its own small
 * controller (not folded into AdminBookingController) since it's a distinct concern — customer
 * lookup, not booking creation — following this project's established pattern of splitting
 * controllers by responsibility rather than growing one further.
 */
class AdminBookingCustomerController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));

        if (mb_strlen($phone) < 3) {
            return response()->json(['customers' => []]);
        }

        $customers = User::query()
            ->where('phone', 'like', "%{$phone}%")
            ->orWhere('name', 'like', "%{$phone}%")
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
        ]);

        return response()->json([
            'customer' => $customer->only(['id', 'name', 'phone']),
        ], 201);
    }
}
