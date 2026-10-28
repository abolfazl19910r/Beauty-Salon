<?php

namespace App\Http\Controllers\Admin\Legal;

use App\Http\Controllers\Controller;
use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «قوانین به‌روز شد» مالک سالن (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸): قوانین سالن‌ها برای همین سالن و حریم خصوصی برای خود مالک، در
 * legal_acceptances با salon_id، نسخه‌ی فعلی، IP و user agent (منبع web). فقط مالک (گروه salon.owner).
 */
class AdminTermsUpdateController extends Controller
{
    public function __construct(
        private readonly LegalStatus $status,
        private readonly LegalAcceptanceRecorder $recorder,
        private readonly CurrentSalon $currentSalon,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $salon = $this->currentSalon->get();
        $pending = $this->status->pending($request->user(), LegalAcceptanceRecorder::SALON_DOCUMENTS, $salon);
        if ($pending === []) {
            return redirect()->to($this->target($request));
        }

        return view('admin.terms-update.show', ['pending' => $pending, 'salon' => $salon]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'برای ادامه باید نسخه‌ی تازه را بپذیرید.']);

        $salon = $this->currentSalon->get();
        $pending = $this->status->pending($request->user(), LegalAcceptanceRecorder::SALON_DOCUMENTS, $salon);
        if ($pending !== []) {
            $this->recorder->acceptNow($request->user(), $salon, array_column($pending, 'document'), $request, 'web');
        }

        return redirect()->to($this->target($request))->with('success', 'نسخه‌ی تازه‌ی قوانین پذیرفته شد.');
    }

    private function target(Request $request): string
    {
        $intended = $request->session()->pull('legal.admin_intended');

        return is_string($intended) && $intended !== '' ? $intended : route('admin.dashboard');
    }
}
