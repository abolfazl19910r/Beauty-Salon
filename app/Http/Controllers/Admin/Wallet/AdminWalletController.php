<?php

namespace App\Http\Controllers\Admin\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Wallet\AdjustWalletRequest;
use App\Models\SpecialistWallet;
use App\Services\Admin\Wallet\WalletAdminService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminWalletController extends Controller
{
    public function __construct(
        private readonly WalletAdminService $walletAdminService
    ) {
    }

    public function index(Request $request): View
    {
        $wallets = $this->walletAdminService->getWalletsList(
            $request->only(['search', 'sort_by'])
        );

        $totals = $this->walletAdminService->getWalletTotals();

        return view('admin.wallet.index', array_merge(
            ['wallets' => $wallets],
            $totals
        ));
    }

    public function show(SpecialistWallet $wallet): View
    {
        return view('admin.wallet.show', $this->walletAdminService->getWalletDetail($wallet));
    }

    public function verifyIban(SpecialistWallet $wallet): RedirectResponse
    {
        try {
            $this->walletAdminService->verifyIban($wallet);

            return back()->with('success', 'شماره شبا با موفقیت تایید شد.');
        } catch (Exception $e) {
            Log::error('خطا در تایید شماره شبا', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تایید شماره شبا: ' . $e->getMessage());
        }
    }

    public function adjust(AdjustWalletRequest $request, SpecialistWallet $wallet): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->walletAdminService->adjustWallet(
                $wallet,
                (float) $validated['amount'],
                $validated['description']
            );

            return back()->with('success', 'تعدیل با موفقیت انجام شد.');
        } catch (Exception $e) {
            Log::error('خطا در تعدیل کیف پول', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تعدیل: ' . $e->getMessage());
        }
    }
}

