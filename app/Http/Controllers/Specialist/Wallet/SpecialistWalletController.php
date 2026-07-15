<?php

namespace App\Http\Controllers\Specialist\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Specialist\SpecialistWalletService;
use Illuminate\Http\Request;

class SpecialistWalletController extends Controller
{
    public function __construct(private SpecialistWalletService $walletService)
    {
    }

    public function index()
    {
        $specialist = $this->walletService->resolveSpecialist(auth()->user());

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        $data = $this->walletService->getWalletOverview($specialist);

        return view('specialist.wallet.index', array_merge(compact('specialist'), $data));
    }

    public function transactions(Request $request)
    {
        $specialist = $this->walletService->resolveSpecialist(auth()->user());

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        $transactions = $this->walletService->getTransactions(
            $specialist,
            $request->only(['type', 'date_from', 'date_to'])
        );

        return view('specialist.wallet.transactions', compact('specialist', 'wallet', 'transactions'));
    }

    public function calculateFee(Request $request)
    {
        $specialist = $this->walletService->resolveSpecialist(auth()->user());

        if (! $specialist) {
            return response()->json(['error' => 'متخصص یافت نشد'], 404);
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        $calculation = $this->walletService->calculateFee(
            $specialist,
            (float) $request->input('amount', 0),
            $request->input('method', 'iban')
        );

        return response()->json([
            'gross_amount' => number_format($calculation['gross_amount']),
            'fee' => number_format($calculation['fee']),
            'net_amount' => number_format($calculation['net_amount']),
        ]);
    }
}
