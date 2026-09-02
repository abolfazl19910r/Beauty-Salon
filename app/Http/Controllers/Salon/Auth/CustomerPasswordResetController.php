<?php

namespace App\Http\Controllers\Salon\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ⭐ Customer identity redesign, item 3 (confirmed 2026-08-30, implemented as a follow-up to the
 * initial pass). Mirrors App\Http\Controllers\Auth\PasswordResetController (unchanged, still
 * staff-only) with the same salon-scoping pattern as CustomerRegisteredController/
 * CustomerAuthenticatedController — the user lookup adds salon_id + user_type='customer'.
 *
 * ⚠️ One extra wrinkle beyond the register/login split: password_reset_tokens is keyed by a
 * bare `phone` column (see the original controller's updateOrInsert(['phone' => ...], ...)).
 * Since phone is no longer globally unique for customers, two different salons' customers
 * requesting a reset with the same phone number around the same time would silently overwrite
 * each other's token if this used that column as-is. Rather than migrate that table (shared with
 * the still-untouched staff flow — out of scope to alter), the customer flow stores a
 * salon-prefixed composite string ("{salon_id}:{phone}") in that same `phone` column instead of
 * the bare phone number, which keeps it collision-free without touching the table's schema or
 * the staff controller at all.
 */
class CustomerPasswordResetController extends Controller
{
    public function __construct(
        protected readonly SMSService $smsService,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function create(): View
    {
        return view('salon.auth.forgot-password', ['salon' => $this->currentSalon->get()]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $salon = $this->currentSalon->get();

        $request->validate([
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::where('phone', $request->phone)
            ->where('salon_id', $salon->id)
            ->where('user_type', 'customer')
            ->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'کاربری با این شماره در این سالن یافت نشد.']);
        }

        $verificationCode = rand(100000, 999999);
        $token = Str::random(60);
        $tokenKey = $salon->id.':'.$request->phone;

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expire_at' => now()->addMinutes((int) config('auth.reset_code_expire_minutes', 2)),
        ]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $tokenKey],
            ['phone' => $tokenKey, 'token' => $token, 'created_at' => now()]
        );

        try {
            $template = config('services.kavenegar.templates.reset_password', 'verification');
            $this->smsService->sendTemplate($user->phone, $template, [(string) $verificationCode]);
        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'خطا در ارسال پیامک.']);
        }

        return redirect()->route('salon.password.verify', ['salon_slug' => $salon->slug, 'token' => $token])
            ->with('success', 'کد تایید ارسال شد.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        $token = $request->token;

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (! $resetRecord || Carbon::parse($resetRecord->created_at)->addHour()->isPast()) {
            return redirect()
                ->route('salon.password.request', ['salon_slug' => $this->currentSalon->get()->slug])
                ->withErrors(['phone' => 'لینک بازیابی نامعتبر یا منقضی شده است. لطفا مجدد درخواست دهید.']);
        }

        return view('salon.auth.reset-password', ['token' => $token, 'salon' => $this->currentSalon->get()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $salon = $this->currentSalon->get();

        $request->validate([
            'token' => 'required',
            'code' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (! $resetRecord) {
            return back()->withErrors(['code' => 'درخواست نامعتبر است.']);
        }

        // tokenKey was stored as "{salon_id}:{phone}" in sendCode() — split it back apart rather
        // than trusting the current route's salon_slug alone, so a token generated for one salon
        // can never be replayed against a same-phone customer of a different salon.
        [$tokenSalonId, $phone] = explode(':', $resetRecord->phone, 2);

        $user = User::where('phone', $phone)
            ->where('salon_id', $tokenSalonId)
            ->where('user_type', 'customer')
            ->first();

        if (! $user) {
            return back()->withErrors(['code' => 'کاربر یافت نشد.']);
        }

        if ($user->verification_code !== $request->code || now()->isAfter($user->verification_code_expire_at)) {
            return back()->withErrors(['code' => 'کد تایید اشتباه یا منقضی شده است.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'verification_code' => null,
            'verification_code_expire_at' => null,
        ]);

        DB::table('password_reset_tokens')->where('phone', $resetRecord->phone)->delete();

        return redirect()->route('salon.login', ['salon_slug' => $salon->slug])
            ->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
