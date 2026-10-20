<?php

namespace App\Http\Controllers\Specialist\Qr;

use App\Http\Controllers\Controller;
use App\Services\Qr\QrScanCounter;
use App\Services\Qr\SalonQrService;
use App\Traits\ResolvesSpecialist;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * «QR من» پنل متخصص (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): QR صفحه‌ی عمومی خود متخصص برای اینستاگرام و کارت ویزیت — همان PNG و PDF صفحه‌ی مدیر.
 */
class SpecialistQrController extends Controller
{
    use ResolvesSpecialist;

    public function __construct(private readonly SalonQrService $qr, private readonly QrScanCounter $scans) {}

    public function index(): View
    {
        $specialist = $this->requireSpecialist();
        $salon = $specialist->salon;

        return view('specialist.qr.index', [
            'target' => $this->qr->target($salon, $specialist),
            'logo' => $this->qr->logo($salon),
            'hits' => $this->scans->summary($salon)['specialists'][$specialist->id] ?? 0,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $input = $request->validate(['layout' => ['required', 'in:'.implode(',', array_keys(SalonQrService::LAYOUTS))]]);
        $specialist = $this->requireSpecialist();
        $target = $this->qr->target($specialist->salon, $specialist);

        return response($this->qr->pdf($specialist->salon, $specialist, $input['layout']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$target['file'].'-'.$input['layout'].'.pdf"',
        ]);
    }
}
