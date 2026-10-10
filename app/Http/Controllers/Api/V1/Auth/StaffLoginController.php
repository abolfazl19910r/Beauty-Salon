<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Api\ApiAccountPresenter;
use App\Services\Api\ApiAudienceGate;
use App\Services\Api\ApiLoginService;
use App\Services\SecurityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * ورود اپ «ماهرو همکار» — هم‌ارز /login وب (حساب کادر سراسری)، ولی فقط برای متخصص (تصمیم ۲۰۲۶-۱۰-۱۰).
 * POST /api/v1/staff/login | login/verify | login/resend
 */
class StaffLoginController extends Controller
{
    public function __construct(
        protected readonly ApiLoginService $loginService,
        protected readonly ApiAudienceGate $gate,
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SecurityLogService $securityLogService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->userRepository->findStaffByPhone($credentials['phone']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->securityLogService->logLogin(false, $credentials['phone'], $user);

            throw new ApiException('invalid_credentials', 'اطلاعات وارد شده صحیح نمی‌باشد.', 422);
        }

        // پیش از فرستادن پیامک: مدیرِ بدون رکورد متخصص یا سالن غیرفعال پیامک هدر نمی‌دهد
        $context = $this->gate->resolve($user, ApiAudienceGate::STAFF);

        return ApiResponse::success($this->loginService->start($user, ApiAudienceGate::STAFF, $context['salon']->id));
    }

    public function verify(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $this->loginService->verify($input['challenge'], $input['code'], ApiAudienceGate::STAFF, null);
        $context = $this->gate->resolve($user, ApiAudienceGate::STAFF);

        return ApiResponse::success(array_merge(
            $this->loginService->issueToken($user, ApiAudienceGate::STAFF, $input['device_name']),
            ['account' => ApiAccountPresenter::present($user, ApiAudienceGate::STAFF, $context['salon'], $context['specialist'])],
        ), [], 201);
    }

    public function resend(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->loginService->resend($input['challenge'], ApiAudienceGate::STAFF, null));
    }
}
