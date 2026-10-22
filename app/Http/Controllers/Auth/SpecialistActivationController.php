<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Services\SecurityLogService;
use App\Services\Specialist\SpecialistActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ⭐ فعال‌سازی حساب متخصص در وب (آزمون دستی ۲۰۲۶-۱۰-۲۲): متخصصی که مدیر با شماره‌ی تازه اضافه کرده، از صفحه‌ی ورود کادر
 * («متخصص سالن هستید و حساب ندارید؟») شماره و رمز می‌دهد، کد پیامکی را وارد می‌کند و مستقیم وارد پنل خودش می‌شود.
 */
class SpecialistActivationController extends Controller
{
    private const SESSION_KEY = 'specialist_activation_challenge';

    public function __construct(
        private readonly SpecialistActivationService $activation,
        private readonly SecurityLogService $securityLog,
    ) {}

    public function create(): View
    {
        return view('auth.specialist-activate');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], ['phone' => 'شماره موبایل', 'password' => 'رمز عبور']);

        try {
            $started = $this->activation->start($input['phone'], $input['password']);
        } catch (ApiException $e) {
            return back()->withErrors(['phone' => $e->getMessage()])->withInput($request->only('phone'));
        }

        session([self::SESSION_KEY => $started['challenge']]);

        return redirect()->route('specialist.activate.verify.show')->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        $phone = ($challenge = session(self::SESSION_KEY)) ? $this->activation->phoneOf($challenge) : null;

        if ($phone === null) {
            session()->forget(self::SESSION_KEY);

            return redirect()->route('specialist.activate')->withErrors(['phone' => 'مهلت فعال‌سازی تمام شده است. دوباره شروع کنید.']);
        }

        return view('auth.specialist-activate-verify', ['phone' => $phone]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return redirect()->route('specialist.activate')->withErrors(['phone' => 'مهلت فعال‌سازی تمام شده است. دوباره شروع کنید.']);
        }

        try {
            $user = $this->activation->verify($challenge, (string) $request->code);
        } catch (ApiException $e) {
            if (in_array($e->errorCode, ['invalid_code', 'code_expired'], true)) {
                return back()->withErrors(['code' => 'کد وارد شده نامعتبر یا منقضی شده است.']);
            }

            session()->forget(self::SESSION_KEY);

            return redirect()->route('specialist.activate')->withErrors(['phone' => $e->getMessage()]);
        }

        session()->forget(self::SESSION_KEY);
        Auth::login($user);
        $request->session()->regenerate();
        $this->securityLog->logLogin(true, $user->phone, $user);

        return redirect()->to($user->homePath())->with('success', 'حساب متخصص شما فعال شد. خوش آمدید!');
    }

    public function resend(): RedirectResponse
    {
        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return redirect()->route('specialist.activate');
        }

        try {
            $this->activation->resendFor($challenge);
        } catch (ApiException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
