<?php

namespace App\Http\Controllers\Central;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SuperAdmin\PhoneSearchController;
use App\Services\Auth\MySalonsChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * بسته‌ی «ح»، ۱۲: «سالن‌های من» در دامنه‌ی اصلی (MySalonsChallenge). نتیجه‌ی تأیید فقط ۱۵ دقیقه در نشست همین دامنه می‌ماند و
 * هیچ ورودی نمی‌سازد؛ هر سالن ورود (رمز/کد) خودش را دارد.
 */
class MySalonsController extends Controller
{
    private const CHALLENGE = 'my_salons.challenge';

    private const PHONE = 'my_salons.phone';

    private const VERIFIED = 'my_salons.verified';

    private const VERIFIED_MINUTES = 15;

    public function __construct(private readonly MySalonsChallenge $challenges) {}

    public function show(Request $request): View
    {
        $verified = $request->session()->get(self::VERIFIED);
        if (is_array($verified) && ($verified['until'] ?? 0) > now()->getTimestamp()) {
            return view('central.my-salons.list', ['phone' => $verified['phone'], 'salons' => $this->challenges->salonsFor($verified['phone'])]);
        }
        $request->session()->forget(self::VERIFIED);

        return view('central.my-salons.phone');
    }

    public function send(Request $request): RedirectResponse
    {
        $phone = PhoneSearchController::normalize((string) $request->input('phone'));
        if (! preg_match('/^09\d{9}$/', $phone)) {
            return back()->withInput()->withErrors(['phone' => 'شماره‌ی موبایل را درست وارد کنید؛ مثلاً 09121234567.']);
        }

        $customer = $this->challenges->firstCustomer($phone);
        // بی‌حساب: challenge ساختگی بدون پیامک — صفحه‌ی بعد همان است و هیچ کدی درست نیست
        $challenge = $customer ? $this->challenges->open(['phone' => $phone, 'user_id' => $customer->id]) : 'none:'.Str::random(40);

        $request->session()->put(self::CHALLENGE, $challenge);
        $request->session()->put(self::PHONE, $phone);

        return redirect()->route('my-salons.verify');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::CHALLENGE)) {
            return redirect()->route('my-salons.show');
        }

        return view('central.my-salons.verify', ['phone' => $request->session()->get(self::PHONE)]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $challenge = (string) $request->session()->get(self::CHALLENGE);
        if ($challenge === '') {
            return redirect()->route('my-salons.show');
        }
        if (str_starts_with($challenge, 'none:')) {
            return back()->withErrors(['code' => 'کد وارد شده نامعتبر است.']);
        }

        $code = PhoneSearchController::normalize((string) $request->input('code'));
        try {
            $data = $this->challenges->confirm($challenge, $code);
        } catch (ApiException $e) {
            if ($e->errorCode === 'invalid_code') {
                return back()->withErrors(['code' => 'کد وارد شده نامعتبر است.']);
            }
            $request->session()->forget([self::CHALLENGE, self::PHONE]);

            return redirect()->route('my-salons.show')->withErrors(['phone' => $e->getMessage()]);
        }

        $request->session()->forget([self::CHALLENGE, self::PHONE]);
        $request->session()->regenerate();
        $request->session()->put(self::VERIFIED, ['phone' => $data['phone'], 'until' => now()->addMinutes(self::VERIFIED_MINUTES)->getTimestamp()]);

        return redirect()->route('my-salons.show');
    }

    public function resend(Request $request): RedirectResponse
    {
        $challenge = (string) $request->session()->get(self::CHALLENGE);
        if ($challenge === '') {
            return redirect()->route('my-salons.show');
        }
        if (! str_starts_with($challenge, 'none:')) {
            try {
                $this->challenges->resend($challenge);
            } catch (ApiException $e) {
                return back()->withErrors(['code' => $e->getMessage()]);
            }
        }

        return back()->with('status', 'اگر این شماره در سالنی حساب داشته باشد، کد تازه فرستاده شد.');
    }

    public function forget(Request $request): RedirectResponse
    {
        $request->session()->forget([self::VERIFIED, self::CHALLENGE, self::PHONE]);

        return redirect()->route('my-salons.show');
    }
}
