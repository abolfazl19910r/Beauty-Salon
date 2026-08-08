<?php

namespace App\Traits;

use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * ⭐ R-Traits: Consolidate the logic of converting the solar date, which before this phase was independently and
 * repeatedly (with a manual array of Persian → English digits) copied into 8+ controllers/services/Form
 * Requests (UserBookingController, UserWalletController,
 * SpecialistReviewController, SpecialistBookingManagementController,
 * SpecialistLeaveController, SpecialistWalletService, UpdateAdminBookingRequest).
 *
 * This Trait only integrates the "consumption" layer of the solar date; The conversion engine
 * ​​intentionally does not touch the underlying — the project already has two independent Jalali systems:
 * 1) `morilog/jalali` package (Jalalian) — used in most controllers/services
 * 2) Manual `gregorian_to_jalali()` function in app/Helpers/JalaliDate.php — only in
 * AdminReportService for chart labels (with Persian month names)
 * Changing these two engines to a single algorithm risks unnecessary behavior (it may give different results on
 * year/month boundary dates) and there is no real bug behind it — so
 * this trait covers both paths, not removing one.
 */
trait HasJalaliDates
{
    /**
     * Names of the solar months — previously only available in AdminReportService ($jMonths property).
     */
    private const JALALI_MONTH_NAMES = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /**
     * Convert Persian numbers to English (without any parse) — for places where only
     * raw string normalization is required (like prepareForValidation in Form Requests).
     */
    protected function normalizeToEnglishDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        static $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        static $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($persian, $english, $value);
    }

    /**
     * Pars a solar date string (with Persian or English digits) to Carbon.
     * In case of invalid format, `null` is returned instead of throwing an exception — exactly
     * the same "silence + no filtering" behavior that was previously inline in most controllers.
     *
     * @param  string|null  $context  If passed, Pars failure is logged with Log::warning
     *                                (previous behavior of SpecialistReviewController/
     *                                SpecialistBookingManagementController/
     *                                SpecialistWalletService); if null, it is completely
     *                                silent (previous behavior of BookingController/
     *                                UserWalletController).
     */
    protected function parseJalali(?string $value, string $format = 'Y/m/d', ?string $context = null): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Jalalian::fromFormat($format, $this->normalizeToEnglishDigits($value))->toCarbon();
        } catch (\Throwable $e) {
            if ($context !== null) {
                \Illuminate\Support\Facades\Log::warning("خطا در تبدیل تاریخ شمسی ({$context}): ".$e->getMessage());
            }

            return null;
        }
    }

    /**
     * "fail-fast" version of Pars Tarikh Shamsi — throws the exception as before (instead of
     * swallowing it), for places that have an outer try/catch with a separate
     * error path (like SpecialistLeaveController::store()) and expect the failure
     * Pars to abort the entire operation with a generic error message.
     */
    protected function parseJalaliOrFail(string $value, string $format = 'Y/m/d'): Carbon
    {
        return Jalalian::fromFormat($format, $this->normalizeToEnglishDigits($value))->toCarbon();
    }

    /**
     * Format a Gregorian date (Carbon/DateTime) to a solar string — the reverse of
     * parseJalali, with the same Jalalian engine.
     */
    protected function toJalali(\DateTimeInterface $date, string $format = 'Y/m/d'): string
    {
        return Jalalian::fromCarbon(Carbon::instance($date))->format($format);
    }

    /**
     * Parse a Gregorian date into its [year, month, day] solar components, with a manual engine
     * ​​gregorian_to_jalali() project (not Jalalian) — specifically for AdminReportService, which
     * was already written with this engine.
     *
     * @return array{0:int,1:int,2:int} [jalaliYear, jalaliMonth, jalaliDay]
     */
    protected function toJalaliParts(\DateTimeInterface $date): array
    {
        $carbon = Carbon::instance($date);

        return gregorian_to_jalali($carbon->year, $carbon->month, $carbon->day);
    }

    /**
     * Persian name of the solar month (1 to 12).
     */
    protected function jalaliMonthName(int $jalaliMonth): string
    {
        return self::JALALI_MONTH_NAMES[$jalaliMonth - 1] ?? '';
    }

    /**
     * ⭐ Added after R-Traits, during zip phase checking of R-Observers: equivalent
     * exact global project helper `jalali_date($date, 'Y/m/d')` — same engine
     * ​​`gregorian_to_jalali()` (not Jalalian), just without direct function call
     * global, for files like `AdminReportService::getRawBookingsForExport()`
     * that already use this Trait for the rest of their Jalalian needs.
     * The output of this method is bit-for-bit identical to `jalali_date($date, 'Y/m/d')` (same
     * `toJalaliParts()` + manual zero-pad), so replacing it is zero behavioral risk.
     */
    protected function toJalaliDateString(\DateTimeInterface $date): string
    {
        [$jy, $jm, $jd] = $this->toJalaliParts($date);

        return sprintf('%d/%02d/%02d', $jy, $jm, $jd);
    }
}
