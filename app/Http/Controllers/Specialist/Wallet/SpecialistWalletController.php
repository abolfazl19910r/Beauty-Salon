<?php

namespace App\Http\Controllers\Specialist\Wallet;

use App\Http\Controllers\Controller;
use App\Services\Specialist\SpecialistWalletService;
use App\Traits\ResolvesSpecialist;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SpecialistWalletController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(private SpecialistWalletService $walletService)
    {
    }

    public function index(): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        $data = $this->walletService->getWalletOverview($specialist);

        return view('specialist.wallet.index', array_merge(compact('specialist'), $data));
    }

    public function transactions(Request $request): View
    {
        $specialist = $this->resolveSpecialist();

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

    public function calculateFee(Request $request): JsonResponse
    {
        // ⚠️ intentionally used resolveSpecialist() (not requireSpecialist()):
        // This method is an endpoint called with fetch/AJAX and should always return JSON.
        // requireSpecialist() returns Laravel's default HTML error page with abort(404)
        // that breaks the JSON convention of this endpoint.
        $specialist = $this->resolveSpecialist();

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
