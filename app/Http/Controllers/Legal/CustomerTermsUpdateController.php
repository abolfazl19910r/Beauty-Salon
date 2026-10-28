<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحه‌ی «قوانین به‌روز شد» مشتری وب (۲۰۲۶-۱۰-۲۸): خلاصه‌ی تغییرهای مهم، لینک متن کامل و تیک پذیرش. ثبت در legal_acceptances
 * با نسخه‌ی فعلی، زمان، IP و user agent همین درخواست (منبع web). بعد از ورود، مشتری در انتظار مستقیم به اینجا می‌آید.
 */
class CustomerTermsUpdateController extends Controller
{
    public function __construct(
        private readonly LegalStatus $status,
        private readonly LegalAcceptanceRecorder $recorder,
        private readonly CurrentSalon $currentSalon,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $pending = $this->status->pending($request->user(), LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS);
        if ($pending === []) {
            return redirect()->to($this->target($request));
        }

        return view('legal.update.customer', ['pending' => $pending]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'برای ادامه باید نسخه‌ی تازه را بپذیرید.']);

        $user = $request->user();
        $pending = $this->status->pending($user, LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS);
        if ($pending !== []) {
            $this->recorder->acceptNow($user, $this->currentSalon->get(), array_column($pending, 'document'), $request, 'web');
        }

        return redirect()->to($this->target($request))->with('success', 'نسخه‌ی تازه‌ی قوانین پذیرفته شد.');
    }

    private function target(Request $request): string
    {
        $intended = $request->session()->pull('legal.intended');

        return is_string($intended) && $intended !== '' ? $intended : route('home');
    }
}
