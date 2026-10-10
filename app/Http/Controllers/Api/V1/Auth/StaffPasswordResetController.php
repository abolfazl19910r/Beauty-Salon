<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Api\ApiAudienceGate;
use App\Services\Api\ApiPasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * بازیابی رمز اپ «ماهرو همکار» (بسته‌ی ۲؛ تصمیم ۲۰۲۶-۱۰-۱۰): هم‌ارز /forgot-password وب برای حساب کادر
 * (findStaffByPhone — رفع همین بسته)، فقط متخصص (مثل ورود اپ). مثل وب «شماره پیدا نشد» را می‌گوید.
 * POST /api/v1/staff/password/forgot {phone} → challenge؛ POST .../resend {challenge}؛
 * POST .../reset {challenge, code, password, password_confirmation} → همه‌ی دستگاه‌ها خارج می‌شوند.
 */
class StaffPasswordResetController extends Controller
{
    public function __construct(
        protected readonly ApiPasswordResetService $resetService,
        protected readonly ApiAudienceGate $gate,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function forgot(Request $request): JsonResponse
    {
        $input = $request->validate(['phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/']]);

        $user = $this->userRepository->findStaffByPhone($input['phone']);
        if (! $user) {
            throw new ApiException('account_not_found', 'کاربری با این شماره یافت نشد.', 422);
        }

        $context = $this->gate->resolve($user, ApiAudienceGate::STAFF);

        return ApiResponse::success($this->resetService->start($user, ApiAudienceGate::STAFF, $context['salon']->id));
    }

    public function resend(Request $request): JsonResponse
    {
        $input = $request->validate(['challenge' => ['required', 'string', 'max:100']]);

        return ApiResponse::success($this->resetService->resend($input['challenge'], ApiAudienceGate::STAFF, null));
    }

    public function reset(Request $request): JsonResponse
    {
        $input = $request->validate([
            'challenge' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->resetService->resetPassword($input['challenge'], $input['code'], ApiAudienceGate::STAFF, $input['password']);

        return ApiResponse::success(['message' => 'رمز عبور تغییر کرد. دوباره وارد شوید.']);
    }
}
