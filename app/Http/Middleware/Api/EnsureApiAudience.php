<?php

namespace App\Http\Middleware\Api;

use App\Exceptions\Api\ApiException;
use App\Services\Api\ApiAudienceGate;
use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * بعد از auth:sanctum روی مسیرهای واردشده‌ی /api/v1 (بسته‌ی ۱ اپلیکیشن). استفاده: 'api.audience' (هر دو اپ)
 * یا 'api.audience:staff' / 'api.audience:customer'.
 *  - فقط توکن Bearer: کوکی session وب (TransientToken سنکتوم که هر abilityای را «دارد») پذیرفته نمی‌شود.
 *  - اپ توکن (ability «customer» یا «staff») باید با مسیر بخواند؛ وگرنه wrong_app.
 *  - ApiAudienceGate در هر درخواست: متخصص حذف‌شده → wrong_app، سالن معلق/منقضی → salon_inactive.
 *  - سالن جاری پیش از route model binding ست می‌شود (در priority list پیش از SubstituteBindings).
 *  - عمر لغزان: expires_at هر بار به «اکنون + api.token_idle_days» جلو می‌رود (حداکثر روزی یک نوشتن).
 */
class EnsureApiAudience
{
    public function __construct(
        protected readonly ApiAudienceGate $gate,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function handle(Request $request, Closure $next, ?string $audience = null): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            throw ApiException::unauthenticated();
        }

        $tokenAudience = collect(ApiAudienceGate::AUDIENCES)->first(fn (string $a) => in_array($a, $token->abilities ?? [], true));

        if ($tokenAudience === null || ($audience !== null && $audience !== $tokenAudience)) {
            throw ApiException::wrongApp();
        }

        $context = $this->gate->resolve($user, $tokenAudience);
        $this->currentSalon->set($context['salon']);

        $request->attributes->set('api_audience', $tokenAudience);
        $request->attributes->set('api_specialist', $context['specialist']);

        $this->slideExpiry($token);

        return $next($request);
    }

    protected function slideExpiry(PersonalAccessToken $token): void
    {
        $idleDays = (int) config('api.token_idle_days', 90);

        if ($token->expires_at && $token->expires_at->lt(now()->addDays($idleDays)->subDay())) {
            $token->forceFill(['expires_at' => now()->addDays($idleDays)])->save();
        }
    }
}
