<?php

namespace App\Http\Controllers\Admin\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Wallet\ApproveWithdrawalRequest;
use App\Http\Requests\Admin\Wallet\RejectWithdrawalRequest;
use App\Models\WithdrawalRequest;
use App\Services\Admin\Wallet\WalletAdminService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminWithdrawalController extends Controller
{
    public function __construct(
        private readonly WalletAdminService $walletAdminService
    ) {
    }

    public function index(Request $request): View
    {
        $withdrawals = $this->walletAdminService->getWithdrawalsList(
            $request->only(['status', 'method', 'search'])
        );

        $stats = $this->walletAdminService->getWithdrawalStats();

        return view('admin.wallet.withdrawals', array_merge(
            ['withdrawals' => $withdrawals],
            $stats
        ));
    }

    public function show(WithdrawalRequest $withdrawalRequest): View
    {
        $withdrawalRequest->load(['specialist', 'wallet', 'processedBy']);

        return view('admin.wallet.withdrawal-show', compact('withdrawalRequest'));
    }

    public function approve(ApproveWithdrawalRequest $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قابل تایید نیست.');
        }

        try {
            $this->walletAdminService->approveWithdrawal($withdrawalRequest, $request->validated());

            return redirect()->route('admin.wallet.withdrawals')
                ->with('success', 'درخواست برداشت با موفقیت تایید شد.');
        } catch (Exception $e) {
            Log::error('خطا در تایید درخواست برداشت', [
                'withdrawal_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تایید درخواست: ' . $e->getMessage());
        }
    }

    public function reject(RejectWithdrawalRequest $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قابل رد نیست.');
        }

        try {
            $this->walletAdminService->rejectWithdrawal(
                $withdrawalRequest,
                $request->validated('rejection_reason')
            );

            return redirect()->route('admin.wallet.withdrawals')
                ->with('success', 'درخواست برداشت رد شد و موجودی بازگردانده شد.');
        } catch (Exception $e) {
            Log::error('خطا در رد درخواست برداشت', [
                'withdrawal_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در رد درخواست: ' . $e->getMessage());
        }
    }

    public function autoPayout(WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قبلاً پردازش شده است.');
        }

        try {
            $result = $this->walletAdminService->autoPayout($withdrawalRequest);

            if ($result['success']) {
                return back()->with(
                    'success',
                    'تسویه حساب آنلاین با موفقیت انجام شد. کد ارجاع: ' . $result['reference_code']
                );
            }

            return back()->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Zarinpal Payout Error: ' . $e->getMessage());

            return back()->with('error', 'خطای سیستمی: ' . $e->getMessage());
        }
    }
}
