<?php

namespace App\Http\Controllers;

use App\Support\CurrentSalon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * ⭐ Fix (ممیزی implicit route-model-binding روی کل پنل ادمین، ۲۰۲۶-۰۹-۲۰، کشف‌شده و اولین‌بار
     * فیکس‌شده در AdminReportExportController::download()): SubstituteBindings — که implicit route
     * parameter ها را resolve می‌کند — بخشی از گروه global middleware `web` است و همیشه *قبل از*
     * middleware سطح-route (`salon.active`/EnsureAdminSalonActive، که CurrentSalon را ست می‌کند)
     * اجرا می‌شود. یعنی در یک request واقعی و تازه، لحظه‌ی binding هنوز CurrentSalon ست نشده
     * (null) — یعنی global scope مدل‌های BelongsToSalon هیچ فیلتری اعمال نمی‌کند و هر id از هر
     * سالنی bind می‌شود. تأییدشده با تست HTTP واقعی (نه فرض) روی ۱۱ کنترلر مجزا؛ یکی از آن‌ها
     * (AdminGalleryController::destroy) حتی رکورد سالن دیگر را واقعاً حذف می‌کرد.
     *
     * این متد باید همیشه به‌عنوان *اولین خط* هر متد کنترلری که یک implicit-bound model
     * (BelongsToSalon) می‌گیرد صدا زده شود — نه به‌جای global scope، بلکه چون global scope در
     * لحظه‌ی binding قابل‌اعتماد نیست. تا این لحظه (داخل بدنه‌ی متد کنترلر)، middleware
     * `salon.active` قبلاً اجرا شده و CurrentSalon را درست ست کرده، پس این مقایسه معتبر است.
     *
     * برای مدل‌هایی که خودشان ستون salon_id ندارند (مثل SpecialistWallet/WithdrawalRequest —
     * فقط از طریق specialist_id به سالن وصل‌اند)، caller باید salon_id را خودش (با
     * withoutGlobalScopes بایاس‌نشده) resolve کند و همین‌جا پاس بدهد.
     */
    protected function ensureSalonOwnership(?int $modelSalonId): void
    {
        abort_unless(
            $modelSalonId !== null && $modelSalonId === app(CurrentSalon::class)->id(),
            404
        );
    }
}
