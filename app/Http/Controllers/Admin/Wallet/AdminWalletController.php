<?php

namespace App\Http\Controllers\Admin\Wallet;

use Exception;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\SpecialistWallet;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Admin\Wallet\WalletAdminService;
use App\Http\Requests\Admin\Wallet\AdjustWalletRequest;

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

    /**
     * Manually settle "pending" earnings for all specialists (so they can request a withdrawal).
     */
    public function settlePending(Request $request): RedirectResponse
    {
        try {
            $result = $this->walletAdminService->settlePendingIncomes(
                wallet: null,
                ignoreDelay: $request->boolean('ignore_delay'),
                source: 'admin_manual'
            );

            return back()->with('success', $this->buildSettlementMessage($result));
        } catch (Exception $e) {
            Log::error('خطا در تسویه‌ی دستی کیف‌پول‌ها', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تسویه: ' . $e->getMessage());
        }
    }

    /**
     * Manual settlement of "pending" earnings by a specific specialist only.
     */
    public function settlePendingForWallet(Request $request, SpecialistWallet $wallet): RedirectResponse
    {
        try {
            $result = $this->walletAdminService->settlePendingIncomes(
                wallet: $wallet,
                ignoreDelay: $request->boolean('ignore_delay'),
                source: 'admin_manual'
            );

            return back()->with('success', $this->buildSettlementMessage($result));
        } catch (Exception $e) {
            Log::error('خطا در تسویه‌ی دستی کیف‌پول متخصص', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطا در تسویه: ' . $e->getMessage());
        }
    }

    /**
     * @param  array{settledCount: int, failedCount: int, settledAmount: float}  $result
     */
    private function buildSettlementMessage(array $result): string
    {
        if ($result['settledCount'] === 0) {
            return 'هیچ تراکنش قابل‌تسویه‌ای یافت نشد.';
        }

        $message = 'تسویه انجام شد — تعداد: ' . number_format($result['settledCount'])
            . '، مبلغ کل: ' . number_format($result['settledAmount']) . ' تومان.';

        if ($result['failedCount'] > 0) {
            $message .= ' (' . number_format($result['failedCount']) . ' تراکنش با خطا مواجه شد، جزئیات در لاگ سرور)';
        }

        return $message;
    }
}
