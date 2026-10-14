<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * feature:{key} — مسیری که فقط با این امکان پلن باز است (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴). سالن از CurrentSalon (پیش‌تر در گروه مسیر
 * ست شده). وب: برگشت به صفحه‌ی صورتحساب با پیام «در پلن شما نیست»؛ API/JSON: ‏403 با {"code":"feature_unavailable","feature":"…"}.
 * بدون سالن جاری (نباید رخ دهد) کاری نمی‌کند — همان قاعده‌ی «سالن ناشناخته چیزی را نمی‌بندد».
 */
class EnsureSalonFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $feature = Feature::from($feature);
        $salon = app(CurrentSalon::class)->get();

        if (! $salon || $salon->hasFeature($feature)) {
            return $next($request);
        }

        $message = sprintf('«%s» در پلن سالن شما نیست.', $feature->label());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['code' => 'feature_unavailable', 'feature' => $feature->value, 'message' => $message], 403);
        }

        return redirect()->route('admin.billing.index')->withErrors(['error' => $message.' برای استفاده، پلن را ارتقا دهید.']);
    }
}
