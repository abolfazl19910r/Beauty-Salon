<?php

namespace App\Http\Controllers\Specialist\Wallet\Iban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specialist\UpdateIbanRequest;
use App\Services\Specialist\SpecialistWalletService;
use App\Traits\ResolvesSpecialist;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SpecialistIbanController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(private SpecialistWalletService $walletService)
    {
    }

    public function edit(): View
    {
        $specialist = $this->resolveSpecialist();

        if (! $specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $this->authorize('view', $wallet);

        return view('specialist.wallet.edit-iban', compact('specialist', 'wallet'));
    }

    public function update(UpdateIbanRequest $request): View|RedirectResponse
    {
        // ⚠️ Bugfix: The old controller here had no null check on the specialist
        // (unlike edit()); If a user reaches this route without an expert record,
        // getOrCreateWallet() would give a fatal error on null. Now it is the same with edit().
        $specialist = $this->resolveSpecialist();

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
