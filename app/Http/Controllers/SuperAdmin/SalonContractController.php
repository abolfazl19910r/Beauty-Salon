<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LegalAcceptance;
use App\Models\Salon;
use App\Services\SuperAdmin\SuperAdminAudit;
use App\Support\Pdf\ReportPdf;
use App\Support\PlatformSettings;
use Illuminate\Http\Response;

/**
 * سند PDF پذیرش قرارداد سالن (۲۰۲۶-۱۱-۲۲) — فقط پنل ابرمدیر، برای هر سالن جدا (تصمیم ابوالفضل: تا روشن شدن ابهام‌های متن، در پنل
 * مالک و مدیر سالن دیده نمی‌شود). یک PDF برای هر ردیف legal_acceptances همان سالن: نام سالن، پذیرنده، سند و نسخه، زمان دقیق، IP،
 * مرورگر و منبع پذیرش؛ و برای «قوانین سالن‌ها» متن بخش «ب» صفحه‌ی قوانین. متن نسخه‌های قدیمی بایگانی نمی‌شود: اگر نسخه‌ی پذیرفته‌شده
 * با نسخه‌ی فعلی فرق کند، روی سند نوشته می‌شود که متن چاپ‌شده مال نسخه‌ی فعلی است.
 */
class SalonContractController extends Controller
{
    public function pdf(Salon $salon, LegalAcceptance $acceptance): Response
    {
        abort_unless((int) $acceptance->salon_id === (int) $salon->id, 404);
        $acceptance->load('user');

        $current = (string) (config('legal.versions')[$acceptance->document] ?? '');
        $mpdf = ReportPdf::make(['margin_top' => 14, 'margin_bottom' => 16]);
        $mpdf->SetTitle('پذیرش قرارداد — '.$salon->name);
        $mpdf->SetAuthor(config('brand.name'));
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML(view('superadmin.salons.contract-pdf', [
            'salon' => $salon,
            'acceptance' => $acceptance,
            'currentVersion' => $current,
            'isCurrent' => $current !== '' && $current === (string) $acceptance->version,
            'platform' => [
                'brand' => (string) config('brand.name'),
                'domain' => (string) (config('app.central_domain') ?: config('brand.domain')),
                'legal_name' => PlatformSettings::get('legal_name'),
                'national_id' => PlatformSettings::get('national_id'),
            ],
            'draft' => (bool) config('legal.draft'),
            'generatedBy' => request()->user(),
        ])->render());

        app(SuperAdminAudit::class)->record('salon.contract_pdf', sprintf('دریافت PDF پذیرش «%s» نسخه‌ی %s', $acceptance->documentLabel(), $acceptance->version), $salon);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contract-'.$salon->slug.'-'.$acceptance->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
