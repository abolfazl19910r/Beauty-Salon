<?php

namespace App\Http\Controllers\Specialist\Wallet\Withdrawal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\Wallet\Withdrawal\StoreWithdrawalRequest;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Services\Specialist\SpecialistWalletService;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\Support\Facades\Log;

class SpecialistWithdrawalController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(private SpecialistWalletService $walletService)
    {
    }

    public function create()
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('requestWithdrawal', $wallet);

        $settings = WalletSetting::first();

        if (! $wallet->iban) {
            return redirect()->route('specialist.wallet.edit-iban')
                ->with('error', 'لطفاً ابتدا شماره شبا خود را ثبت کنید.');
        }

        return view('specialist.wallet.create-withdrawal', compact('specialist', 'wallet', 'settings'));
    }

    public function store(StoreWithdrawalRequest $request)
    {
        // requireSpecialist(): equivalent to the previous behavior of abort(404) in old storeWithdrawal(),
        // Only with a slightly more descriptive message text (from the project's central trait).
        $specialist = $this->requireSpecialist();

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('requestWithdrawal', $wallet);

        try {
            $result = $this->walletService->createWithdrawal($specialist, $request->validated());

            if (! $result['success']) {
                return back()->with('error', $result['message']);
            }

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'درخواست برداشت با موفقیت ثبت شد. کد پیگیری: '.$result['withdrawal_request']->reference_code);
        } catch (Exception $e) {
            Log::error('خطا در ثبت درخواست برداشت', [
                'specialist_id' => $specialist->id,
                'amount' => $request->input('amount'),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در ثبت درخواست: '.$e->getMessage());
        }
    }

    public function cancel(WithdrawalRequest $withdrawalRequest)
    {
        $specialist = $this->requireSpecialist();

        $this->authorize('requestWithdrawal', $specialist->wallet);

        if (! $withdrawalRequest->canBeCancelled()) {
            return back()->with('error', 'این درخواست قابل لغو نیست.');
        }

        try {
            $this->walletService->cancelWithdrawal($specialist, $withdrawalRequest);

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'درخواست برداشت با موفقیت لغو شد و موجودی بازگردانده شد.');
        } catch (Exception $e) {
            Log::error('خطا در لغو درخواست برداشت', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در لغو درخواست: '.$e->getMessage());
        }
    }
}
