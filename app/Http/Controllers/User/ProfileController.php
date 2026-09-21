<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Profile\ProfileUpdateRequest;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly BookingRepositoryInterface $bookingRepository) {}

    public function show(): View
    {
        // eager load and sort by booking_time (not created_at)
        // so that the list of appointments is aligned with my appointments page
        $bookings = $this->bookingRepository->query()
            ->with(['service', 'specialist'])
            ->where('user_id', auth()->id())
            ->orderBy('booking_time', 'desc')
            ->paginate(10);

        return view('profile.show', [
            'user' => auth()->user(),
            'bookings' => $bookings,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePassword(\App\Http\Requests\User\Profile\UpdatePasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ⭐ Fix (same real bug as AuthenticatedSessionController::destroy(), same fix): '/' isn't
        // a registered route at all in this app anymore (every '/' lives under some prefix —
        // /s/{slug}/ for the public homepage, /admin for the admin dashboard) — a user who just
        // deleted their account was redirected straight to a 404. route('login') is the correct,
        // always-valid target once the session is gone.
        return redirect()->route('login');
    }
}
