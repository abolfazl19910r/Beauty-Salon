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
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * ورود اپ «ماهرو» — هم‌ارز /s/{slug}/login وب: حساب مشتری در هر سالن جداست، پس سالن جزو مسیر است
 * (سالن دوم = ورود و توکن جدا). سالن ناموجود/معلق/منقضی مثل وب ۴۰۴ (salon.resolve).
 * POST /api/v1/customer/salons/{salon_slug}/login | login/verify | login/resend
 */
class CustomerLoginController extends Controller
{
    public function __construct(
        protected readonly ApiLoginService $loginService,
        protected readonly ApiAudienceGate $gate,
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SecurityLogService $securityLogService,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $salon = $this->currentSalon->get();

        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->userRepository->findCustomerByPhoneInSalon($credentials['phone'], $salon->id);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->securityLogService->logLogin(false, $credentials['phone'], $user);

            throw new ApiException('invalid_credentials', 'اطلاعات وارد شده صحیح نمی‌باشد.', 422);
        }

        return ApiResponse::success($this->loginService->start($user, ApiAudienceGate::CUSTOMER, $salon->id));
    }

    public function verify(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $this->loginService->verify($input['challenge'], $input['code'], ApiAudienceGate::CUSTOMER, $this->currentSalon->id());
        $context = $this->gate->resolve($user, ApiAudienceGate::CUSTOMER);

        return ApiResponse::success(array_merge(
            $this->loginService->issueToken($user, ApiAudienceGate::CUSTOMER, $input['device_name']),
            ['account' => ApiAccountPresenter::present($user, ApiAudienceGate::CUSTOMER, $context['salon'])],
        ), [], 201);
    }

    public function resend(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->loginService->resend($input['challenge'], ApiAudienceGate::CUSTOMER, $this->currentSalon->id()));
    }
}
