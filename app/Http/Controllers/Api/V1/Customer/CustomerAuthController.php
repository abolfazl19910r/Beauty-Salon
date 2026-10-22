<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Api\ApiAccountPresenter;
use App\Services\Api\ApiAudienceGate;
use App\Services\Api\ApiLoginService;
use App\Services\Api\ApiPasswordResetService;
use App\Services\Api\CustomerPresenter;
use App\Services\Customer\CustomerRegistrationService;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * پیش از ورود در اپ «ماهرو» (بسته‌ی ۳): کارت سالن، ثبت‌نام و بازیابی رمز — همه زیر /api/v1/customer/salons/{salon_slug}
 * (salon.resolve: سالن ناموجود/معلق/منقضی → ۴۰۴). ورود مشتری در CustomerLoginController (بسته‌ی ۱).
 * تصمیم ۲۰۲۶-۱۰-۱۱: پیش از ورود فقط کارت سالن (نام، لوگو) دیده می‌شود؛ خدمات و زمان‌ها بعد از ورود.
 */
class CustomerAuthController extends Controller
{
    public function __construct(
        protected readonly CurrentSalon $currentSalon,
        protected readonly CustomerRegistrationService $registration,
        protected readonly ApiLoginService $loginService,
        protected readonly ApiPasswordResetService $resetService,
        protected readonly ApiAudienceGate $gate,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    /** GET customer/salons/{slug} — اپ با این، لینک/QR/کد سالن را پیش از ورود اعتبارسنجی می‌کند. */
    public function card(): JsonResponse
    {
        return ApiResponse::success(CustomerPresenter::salonCard($this->currentSalon->get()));
    }

    public function register(Request $request): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // ⭐ متخصص همین سالن حساب متخصص می‌گیرد، نه مشتری؛ اپ مشتری جای پنل متخصص نیست ← اپ همکار (۲۰۲۶-۱۰-۲۲)
        if (app(\App\Services\Specialist\SpecialistAccountLinker::class)->hasPending($input['phone'], $this->currentSalon->id())) {
            throw new \App\Exceptions\Api\ApiException('specialist_phone', 'این شماره متخصص همین سالن است. حساب متخصص را از اپ «ماهرو همکار» ← «فعال‌سازی حساب متخصص» بسازید.', 422);
        }

        return ApiResponse::success($this->registration->start($this->currentSalon->id(), $input['name'], $input['phone'], $input['password']));
    }

    /** کد درست → حساب ساخته می‌شود و همان لحظه توکن می‌گیرد (مثل وب که بعد از ثبت‌نام وارد می‌کند). */
    public function verifyRegistration(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $this->registration->verify($input['challenge'], $input['code'], $this->currentSalon->id());
        $context = $this->gate->resolve($user, ApiAudienceGate::CUSTOMER);

        return ApiResponse::success(array_merge(
            $this->loginService->issueToken($user, ApiAudienceGate::CUSTOMER, $input['device_name']),
            ['account' => ApiAccountPresenter::present($user, ApiAudienceGate::CUSTOMER, $context['salon'])],
        ), [], 201);
    }

    public function resendRegistration(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->registration->resendFor($input['challenge'], $this->currentSalon->id()));
    }

    /** مثل فرم وب «شماره پیدا نشد» را می‌گوید. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $input = $request->validate(['phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/']]);

        $user = $this->userRepository->findCustomerByPhoneInSalon($input['phone'], $this->currentSalon->id());
        if (! $user) {
            throw new ApiException('account_not_found', 'کاربری با این شماره در این سالن یافت نشد.', 422);
        }

        $this->gate->resolve($user, ApiAudienceGate::CUSTOMER);

        return ApiResponse::success($this->resetService->start($user, ApiAudienceGate::CUSTOMER, $this->currentSalon->id()));
    }

    public function resendPassword(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->resetService->resend($input['challenge'], ApiAudienceGate::CUSTOMER, $this->currentSalon->id()));
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->resetService->resetPassword($input['challenge'], $input['code'], ApiAudienceGate::CUSTOMER, $input['password'], $this->currentSalon->id());

        return ApiResponse::success(['message' => 'رمز عبور تغییر کرد. دوباره وارد شوید.']);
    }
}
