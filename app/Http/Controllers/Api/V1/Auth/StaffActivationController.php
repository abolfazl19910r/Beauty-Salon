<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Api\ApiAccountPresenter;
use App\Services\Api\ApiAudienceGate;
use App\Services\Api\ApiLoginService;
use App\Services\SecurityLogService;
use App\Services\Specialist\SpecialistActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ⭐ فعال‌سازی حساب متخصص در اپ «ماهرو همکار» (۲۰۲۶-۱۰-۲۲) — هم‌ارز /specialist/activate وب.
 * POST /api/v1/staff/activate {phone, password, password_confirmation} → challenge (کد پیامکی)
 * POST /api/v1/staff/activate/verify {challenge, code, device_name} → توکن + حساب (مثل staff/login/verify)
 * POST /api/v1/staff/activate/resend {challenge}
 * خطاها: not_a_specialist (مدیر این شماره را متخصص نکرده)، account_exists (حساب کادر دارد ← ورود)، کدهای challenge مثل ثبت‌نام.
 */
class StaffActivationController extends Controller
{
    public function __construct(
        private readonly SpecialistActivationService $activation,
        private readonly ApiLoginService $loginService,
        private readonly ApiAudienceGate $gate,
        private readonly SecurityLogService $securityLog,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $input = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return ApiResponse::success($this->activation->start($input['phone'], $input['password']));
    }

    public function verify(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $this->activation->verify($input['challenge'], $input['code']);
        $context = $this->gate->resolve($user, ApiAudienceGate::STAFF);
        $this->securityLog->logLogin(true, $user->phone, $user);

        return ApiResponse::success(array_merge(
            $this->loginService->issueToken($user, ApiAudienceGate::STAFF, $input['device_name']),
            ['account' => ApiAccountPresenter::present($user, ApiAudienceGate::STAFF, $context['salon'], $context['specialist'])],
        ), [], 201);
    }

    public function resend(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->activation->resendFor($input['challenge']));
    }
}
