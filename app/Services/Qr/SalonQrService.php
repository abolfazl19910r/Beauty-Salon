<?php

namespace App\Services\Qr;

use App\Models\Salon;
use App\Models\Specialist;
use App\Support\Pdf\ReportPdf;
use App\Support\SalonTitle;
use Illuminate\Support\Facades\Storage;

/**
 * QR سالن و QR هر متخصص (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): آدرس، عنوان‌ها و PDF چاپی.
 *
 * آدرس QR همان آدرس عمومی است با ?src=qr (بدون راز؛ فقط برای شمارش بازدید در QrScanCounter). slug سالن بعد از ساخت قفل است،
 * پس QR چاپ‌شده نمی‌شکند. PNG اینستاگرام در مرورگر ساخته می‌شود (resources/js/salon-qr-poster.js — متن فارسی را مرورگر درست
 * می‌چیند)؛ PDF چاپی اینجا با mPDF و `<barcode type="QR">` (پکیج mpdf/qrcode)، تیره روی سفید.
 */
class SalonQrService
{
    public const LAYOUTS = ['poster' => 'پوستر A5 (میز پذیرش)', 'cards' => 'برگه‌ی A4 با ۸ کارت ویزیتی'];

    /**
     * @return array{kind: string, url: string, title: string, subtitle: string, code: string, file: string, specialist_id: ?int, specialist_name: ?string}
     */
    public function target(Salon $salon, ?Specialist $specialist = null): array
    {
        if ($specialist !== null && $specialist->salon_id !== $salon->id) {
            throw new \InvalidArgumentException('Specialist does not belong to the salon.');
        }

        $url = $specialist
            ? route('specialists.show', ['salon_slug' => $salon->slug, 'specialist' => $specialist->id])
            : $salon->publicUrl();

        return [
            'kind' => $specialist ? 'specialist' : 'salon',
            'url' => $url.'?src=qr',
            'title' => SalonTitle::for($salon->name),
            'subtitle' => $specialist ? 'رزرو آنلاین نوبت با '.$specialist->name : 'رزرو آنلاین نوبت',
            'code' => $salon->slug,
            'file' => $salon->slug.($specialist ? '-specialist-'.$specialist->id : '').'-qr',
            'specialist_id' => $specialist?->id,
            'specialist_name' => $specialist?->name,
        ];
    }

    /**
     * لوگوی سالن برای چاپ و PNG (فقط اگر پلن «لوگوی اختصاصی» دارد و فایل در دسترس است). data URI تا canvas مرورگر آلوده نشود
     * (لوگو روی دیسک public است و ممکن است میزبان دیگری داشته باشد).
     *
     * @return array{path: string, data_uri: string}|null
     */
    public function logo(Salon $salon): ?array
    {
        if ($salon->logoUrl() === null || ! $salon->logo_path) {
            return null;
        }

        $disk = Storage::disk('public');
        $ext = strtolower(pathinfo($salon->logo_path, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif'][$ext] ?? null;

        if ($mime === null || ! $disk->exists($salon->logo_path) || $disk->size($salon->logo_path) > 2 * 1024 * 1024) {
            return null;
        }

        return [
            'path' => $disk->path($salon->logo_path),
            'data_uri' => 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($salon->logo_path)),
        ];
    }

    /** PDF چاپی: poster = یک پوستر A5، cards = برگه‌ی A4 با ۸ کارت ۸۵×۵۵ میلی‌متری برای برش */
    public function pdf(Salon $salon, ?Specialist $specialist, string $layout): string
    {
        $target = $this->target($salon, $specialist);
        $logo = $this->logo($salon);

        $mpdf = $layout === 'cards'
            ? ReportPdf::make(['format' => 'A4', 'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 13, 'margin_bottom' => 10, 'margin_footer' => 0])
            : ReportPdf::make(['format' => 'A5', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 14, 'margin_bottom' => 10, 'margin_footer' => 0]);

        $mpdf->SetTitle($target['title'].' — QR');
        $mpdf->SetAuthor(config('brand.name'));
        $mpdf->WriteHTML(view($layout === 'cards' ? 'admin.salon-qr.pdf-cards' : 'admin.salon-qr.pdf-poster', [
            'target' => $target,
            'logoPath' => $logo['path'] ?? null,
            'brand' => config('brand.name'),
        ])->render());

        return $mpdf->Output('', 'S');
    }
}
