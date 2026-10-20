<?php

namespace App\Http\Controllers\Admin\SalonQr;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Services\Qr\QrScanCounter;
use App\Services\Qr\SalonQrService;
use App\Support\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * صفحه‌ی «QR و پوستر سالن» پنل مدیر (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): QR سالن یا یک متخصص (?specialist=)، PNG اینستاگرام (استوری و پست،
 * در مرورگر)، PDF چاپی (پوستر A5 و برگه‌ی ۸ کارت)، و بازدید از QRها در ۳۰ روز اخیر. هر مدیر سالن (بدون مجوز جدا).
 */
class AdminSalonQrController extends Controller
{
    public function __construct(
        private readonly SalonQrService $qr,
        private readonly QrScanCounter $scans,
        private readonly CurrentSalon $currentSalon,
    ) {}

    public function index(Request $request): View
    {
        $salon = $this->currentSalon->get();
        abort_if($salon === null, 404);

        $specialists = Specialist::query()->orderBy('name')->get(['id', 'name', 'salon_id']);
        $selected = $request->filled('specialist') ? $specialists->firstWhere('id', (int) $request->query('specialist')) : null;
        abort_if($request->filled('specialist') && $selected === null, 404);

        return view('admin.salon-qr.index', [
            'salon' => $salon,
            'target' => $this->qr->target($salon, $selected),
            'specialists' => $specialists,
            'selected' => $selected,
            'logo' => $this->qr->logo($salon),
            'scans' => $this->scans->summary($salon),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $salon = $this->currentSalon->get();
        abort_if($salon === null, 404);
        $input = $request->validate([
            'layout' => ['required', 'in:'.implode(',', array_keys(SalonQrService::LAYOUTS))],
            'specialist' => ['nullable', 'integer'],
        ]);

        // scope سالن جاری: متخصص سالن دیگر پیدا نمی‌شود
        $specialist = isset($input['specialist']) ? Specialist::query()->findOrFail($input['specialist']) : null;
        $target = $this->qr->target($salon, $specialist);

        return response($this->qr->pdf($salon, $specialist, $input['layout']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$target['file'].'-'.$input['layout'].'.pdf"',
        ]);
    }
}
