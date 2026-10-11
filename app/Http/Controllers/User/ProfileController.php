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

    public function destroy(Request $request, \App\Services\Customer\CustomerAccountDeletionService $deletion): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // نوبت فعال یا موجودی کیف پول: حذف نمی‌شود (۲۰۲۶-۱۰-۱۱)
        if ($blocker = $deletion->blocker($user)) {
            return back()->withErrors(['password' => $blocker], 'userDeletion');
        }

        Auth::logout();

        $deletion->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ورود مشتری فقط از صفحه‌ی سالن خودش (تصمیم ۲۰۲۶-۱۰-۱۰)؛ /login مرکزی فقط کادر است
        return redirect()->route('salon.login');
    }
}
