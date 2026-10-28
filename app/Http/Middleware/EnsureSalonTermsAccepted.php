<?php

namespace App\Http\Middleware;

use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * پذیرش دوباره‌ی قوانین سالن‌ها در پنل مدیر (۲۰۲۶-۱۰-۲۸)، بعد از salon.active.
 *
 * مالک سالن تا تغییر «مهم» قوانین سالن‌ها (salon_terms، برای همین سالن) یا حریم خصوصی (خودش) را نپذیرد پنل قفل است و به صفحه‌ی
 * «قوانین به‌روز شد» می‌رود؛ فقط همان صفحه و پشتیبانی (تیکت به ماهرو) باز است و خروج و «سالن‌های من» بیرون از این گروه‌اند.
 * منشی و کادر قفل نمی‌شوند (قرارداد با مالک است)؛ فقط نوار «منتظر پذیرش مالک» می‌بینند. رزرو عمومی سالن ادامه دارد
 * (تصمیم ابوالفضل، ۲۰۲۶-۱۰-۲۸).
 */
class EnsureSalonTermsAccepted
{
    /** مسیرهایی که برای مالک در انتظار هم باز است */
    public const OPEN = ['admin.terms-update.*', 'admin.support-tickets.*'];

    public function __construct(
        private readonly LegalStatus $status,
        private readonly CurrentSalon $currentSalon,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $salon = $this->currentSalon->get();

        if (! $user || ! $salon || $user->hasRole('super-admin')
            || ($this->status->requiredVersion('salon_terms') === null && $this->status->requiredVersion('privacy') === null)) {
            return $next($request);
        }

        $isOwner = $salon->admins()->wherePivot('user_id', $user->id)->wherePivot('role', 'owner')->exists();

        if (! $isOwner) {
            view()->share('salonTermsAwaitingOwner', $this->status->pending($user, ['salon_terms'], $salon) !== []);

            return $next($request);
        }

        if ($this->status->pending($user, LegalAcceptanceRecorder::SALON_DOCUMENTS, $salon) === [] || $request->routeIs(...self::OPEN)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false, 'code' => EnsureLegalTermsAccepted::CODE,
                'message' => 'قوانین سالن‌ها به‌روز شده است؛ برای ادامه، مالک باید نسخه‌ی تازه را بپذیرد.',
                'redirect' => route('admin.terms-update.show'),
            ], 409);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('legal.admin_intended', $request->fullUrl());
        }

        return redirect()->route('admin.terms-update.show');
    }
}
