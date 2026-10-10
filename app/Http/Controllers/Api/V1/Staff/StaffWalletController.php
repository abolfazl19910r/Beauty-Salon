<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Exceptions\Api\ApiException;
use App\Http\Requests\Specialist\UpdateIbanRequest;
use App\Http\Requests\Specialist\Wallet\Withdrawal\StoreWithdrawalRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Salon;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Notifications\Admin\Wallet\SpecialistIbanChangedNotification;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Services\Specialist\SpecialistWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

/**
 * کیف پول اپ همکار (بسته‌ی ۲؛ تصمیم ۲۰۲۶-۱۰-۱۰ «همه + تغییر شبا»). منطق پول همان SpecialistWalletService وب است
 * (قفل ردیف، idempotency برداشت، برگشت مبلغ با لغو). تغییر شبا از اپ دو محافظ دارد که وب ندارد: رمز فعلی حساب
 * (گوشی بازِ گم‌شده برای بردن پول کافی نباشد) و اعلان داخلی به مالک سالن. شبای تازه مثل وب تأییدنشده است.
 */
class StaffWalletController extends StaffController
{
    public function __construct(protected readonly SpecialistWalletService $walletService) {}

    public function show(Request $request): JsonResponse
    {
        $specialist = $this->specialist($request);
        $overview = $this->walletService->getWalletOverview($specialist);
        $settings = WalletSetting::get();

        return ApiResponse::success([
            'balance' => (float) $overview['wallet']->balance,
            'total_earned' => (float) $overview['wallet']->total_earned,
            'total_withdrawn' => (float) $overview['wallet']->total_withdrawn,
            'pending_amount' => (float) $overview['wallet']->pending_amount,
            'month_income' => (float) $overview['currentMonthIncome'],
            'month_withdrawals' => (float) $overview['currentMonthWithdrawals'],
            'iban' => $this->iban($overview['wallet']),
            'withdrawal_limits' => [
                'minimum' => (float) $settings->minimum_withdrawal_amount,
                'maximum' => (float) $settings->maximum_withdrawal_amount,
            ],
        ]);
    }

    public function fee(Request $request): JsonResponse
    {
        $input = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:instant,iban'],
        ]);

        $calc = $this->walletService->calculateFee($this->specialist($request), (float) $input['amount'], $input['method']);

        return ApiResponse::success([
            'gross_amount' => (float) $calc['gross_amount'],
            'fee' => (float) $calc['fee'],
            'net_amount' => (float) $calc['net_amount'],
        ]);
    }

    public function transactions(Request $request, WalletTransactionRepositoryInterface $transactions): JsonResponse
    {
        $input = $request->validate([
            'type' => ['nullable', 'string', 'max:30'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $wallet = $this->specialist($request)->getOrCreateWallet();
        $page = $transactions->paginateForWalletWithFilters($wallet->id, array_filter([
            'type' => $input['type'] ?? null,
            'date_from' => isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : null,
            'date_to' => isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : null,
        ]), 20);

        return ApiResponse::success(collect($page->items())->map(fn ($t) => [
            'id' => $t->id,
            'type' => $t->type,
            'amount' => (float) $t->amount,
            'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
            'description' => $t->description,
            'booking_id' => $t->booking_id,
            'created_at' => $t->created_at?->toIso8601String(),
            'created_at_jalali' => $t->created_at ? Jalalian::fromCarbon($t->created_at)->format('Y/m/d H:i') : null,
        ])->values(), $this->pageMeta($page));
    }

    public function withdrawals(Request $request, WithdrawalRequestRepositoryInterface $withdrawals): JsonResponse
    {
        $wallet = $this->specialist($request)->getOrCreateWallet();
        $page = $withdrawals->paginateForWallet($wallet->id, 20);

        return ApiResponse::success(collect($page->items())->map(fn (WithdrawalRequest $w) => $this->withdrawal($w))->values(), $this->pageMeta($page));
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request): JsonResponse
    {
        $result = $this->walletService->createWithdrawal($this->specialist($request), $request->validated());

        if (! $result['success']) {
            throw new ApiException('withdrawal_rejected', $result['message'], 422);
        }

        $replayed = ! empty($result['replayed']);

        return ApiResponse::success($this->withdrawal($result['withdrawal_request']->fresh()), ['replayed' => $replayed], $replayed ? 200 : 201);
    }

    public function cancelWithdrawal(Request $request, int $withdrawalId): JsonResponse
    {
        $specialist = $this->specialist($request);
        $withdrawal = WithdrawalRequest::whereKey($withdrawalId)->where('specialist_id', $specialist->id)->first();

        if (! $withdrawal) {
            throw new ApiException('not_found', 'درخواست برداشت پیدا نشد.', 404);
        }

        if (! $this->walletService->cancelWithdrawal($specialist, $withdrawal)) {
            throw new ApiException('withdrawal_not_cancellable', 'این درخواست برداشت دیگر قابل لغو نیست.', 409);
        }

        return ApiResponse::success($this->withdrawal($withdrawal->fresh()));
    }

    public function updateIban(UpdateIbanRequest $request): JsonResponse
    {
        $request->validate(['current_password' => ['required', 'string']]);

        if (! Hash::check((string) $request->input('current_password'), $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'رمز عبور فعلی درست نیست.']);
        }

        $specialist = $this->specialist($request);
        $wallet = $specialist->getOrCreateWallet();
        $this->walletService->updateIban($wallet, $request->validated());
        $wallet->refresh();

        Salon::find($specialist->salon_id)?->owner()?->notify(new SpecialistIbanChangedNotification($specialist, $wallet));

        return ApiResponse::success(['iban' => $this->iban($wallet)]);
    }

    /** شبا کامل برگردانده نمی‌شود — فقط برای شناختن («IR12…3456») */
    protected function iban(SpecialistWallet $wallet): ?array
    {
        if (! $wallet->iban) {
            return null;
        }

        return [
            'masked' => substr($wallet->iban, 0, 4).'…'.substr($wallet->iban, -4),
            'account_holder_name' => $wallet->account_holder_name,
            'bank_name' => $wallet->bank_name,
            'verified' => (bool) $wallet->iban_verified,
        ];
    }

    protected function withdrawal(WithdrawalRequest $w): array
    {
        return [
            'id' => $w->id,
            'reference_code' => $w->reference_code,
            'amount' => (float) $w->amount,
            'fee' => (float) $w->fee,
            'net_amount' => (float) $w->net_amount,
            'method' => $w->method,
            'status' => $w->status,
            'rejection_reason' => $w->rejection_reason,
            'can_cancel' => $w->canBeCancelled(),
            'created_at' => $w->created_at?->toIso8601String(),
            'created_at_jalali' => $w->created_at ? Jalalian::fromCarbon($w->created_at)->format('Y/m/d H:i') : null,
        ];
    }
}
