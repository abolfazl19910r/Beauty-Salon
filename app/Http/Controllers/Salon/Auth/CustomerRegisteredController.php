<?php

namespace App\Http\Controllers\Salon\Auth;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerRegistrationService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * ثبت‌نام مشتری در صفحه‌ی سالن. از ۲۰۲۶-۱۰-۱۱ حساب فقط بعد از تأیید کد پیامکی ساخته می‌شود
 * (CustomerRegistrationService — همان سرویس اپ «ماهرو»)؛ تا آن لحظه فقط challenge در session است.
 */
class CustomerRegisteredController extends Controller
{
    private const SESSION_KEY = 'customer_register_challenge';

    public function __construct(
        protected readonly CustomerRegistrationService $registration,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function create(): View
    {
        return view('salon.auth.register', ['salon' => $this->currentSalon->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // بسته‌ی مجوز 5د: پذیرش قوانین و حریم خصوصی، با نسخه و زمان
            'accept_terms' => ['accepted'],
        ], [
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد',
            'accept_terms.accepted' => 'برای ثبت‌نام، قوانین و شرایط و حریم خصوصی را بپذیرید.',
        ]);

        try {
            $result = $this->registration->start($this->currentSalon->id(), $request->name, $request->phone, $request->password);
        } catch (ApiException $e) {
            return back()->withErrors(['phone' => $e->getMessage()])->withInput($request->except('password', 'password_confirmation'));
        }

        session([self::SESSION_KEY => $result['challenge']]);
        $acceptance = app(\App\Services\Legal\LegalAcceptanceRecorder::class);
        $acceptance->remember('customer-register', $result['challenge'], $acceptance->capture($request, 'web'));

        return redirect()->route('salon.register.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        $phone = ($challenge = session(self::SESSION_KEY))
            ? $this->registration->phoneOf($challenge, $this->currentSalon->id())
            : null;

        if (! $phone) {
            session()->forget(self::SESSION_KEY);

            return redirect()->route('salon.register')
                ->withErrors(['error' => 'لطفا ابتدا ثبت نام کنید.']);
        }

        return view('salon.auth.register-verify', ['user' => (object) ['phone' => $phone], 'salon' => $this->currentSalon->get()]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return redirect()->route('salon.register')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت نام کنید.']);
        }

        try {
            $user = $this->registration->verify($challenge, (string) $request->code, $this->currentSalon->id());
        } catch (ApiException $e) {
            if (in_array($e->errorCode, ['invalid_code', 'code_expired'], true)) {
                return back()->withErrors(['code' => 'کد وارد شده نامعتبر یا منقضی شده است.']);
            }

            session()->forget(self::SESSION_KEY);

            return redirect()->route('salon.register')->withErrors(['error' => $e->getMessage()]);
        }

        session()->forget(self::SESSION_KEY);
        $acceptance = app(\App\Services\Legal\LegalAcceptanceRecorder::class);
        $acceptance->record($user, $this->currentSalon->get(), \App\Services\Legal\LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS,
            $acceptance->pull('customer-register', $challenge) ?? $acceptance->capture($request, 'web'));

        // ⭐ متخصصی که مدیر همین سالن با این شماره ثبت کرده، به‌جای مشتری حساب متخصص می‌گیرد و به پنل خودش می‌رود (۲۰۲۶-۱۰-۲۲)
        $isSpecialist = app(\App\Services\Specialist\SpecialistAccountLinker::class)->promoteCustomer($user);

        Auth::login($user);
        $request->session()->regenerate();

        if ($isSpecialist) {
            return redirect()->to($user->homePath())->with('success', 'حساب متخصص شما فعال شد. خوش آمدید!');
        }

        return redirect()->route('home', ['salon_slug' => $this->currentSalon->get()->slug])
            ->with('success', 'ثبت نام شما با موفقیت انجام شد. خوش آمدید!');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        try {
            $this->registration->resendFor($challenge, $this->currentSalon->id());
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
