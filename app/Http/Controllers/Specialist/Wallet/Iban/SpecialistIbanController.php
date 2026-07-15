<?php

namespace App\Http\Controllers\Specialist\Wallet\Iban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\UpdateIbanRequest;
use App\Services\Specialist\SpecialistWalletService;
use Exception;

class SpecialistIbanController extends Controller
{
    public function __construct(private SpecialistWalletService $walletService)
    {
    }

    public function edit()
    {
        $specialist = $this->walletService->resolveSpecialist(auth()->user());

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        return view('specialist.wallet.edit-iban', compact('specialist', 'wallet'));
    }

    public function update(UpdateIbanRequest $request)
    {
        $specialist = $this->walletService->resolveSpecialist(auth()->user());

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('updateIban', $wallet);

        try {
            $this->walletService->updateIban($wallet, $request->validated());

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'اطلاعات بانکی با موفقیت ثبت شد.');
        } catch (Exception $e) {
            return back()->with('error', 'خطایی رخ داد.');
        }
    }
}
