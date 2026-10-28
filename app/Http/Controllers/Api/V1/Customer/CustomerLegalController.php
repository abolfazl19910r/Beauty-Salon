<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «قوانین به‌روز شد» در اپ مشتری (پذیرش دوباره، ۲۰۲۶-۱۰-۲۸). همان فهرست legal_pending پاسخ ورود و GET me.
 */
class CustomerLegalController extends Controller
{
    public function __construct(
        private readonly LegalStatus $status,
        private readonly LegalAcceptanceRecorder $recorder,
        private readonly CurrentSalon $currentSalon,
    ) {}

    /** GET customer/legal */
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(['pending' => self::present($this->status, $request->user())]);
    }

    /** POST customer/legal/accept {accept_terms: true} — همه‌ی سندهای در انتظار با نسخه‌ی فعلی، منبع app و نسخه‌ی اپ */
    public function accept(Request $request): JsonResponse
    {
        $request->validate(['accept_terms' => ['accepted']], ['accept_terms.accepted' => 'برای ادامه باید نسخه‌ی تازه را بپذیرید.']);

        $user = $request->user();
        $pending = $this->status->pending($user, LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS);
        if ($pending !== []) {
            $this->recorder->acceptNow($user, $this->currentSalon->get(), array_column($pending, 'document'), $request, 'app');
        }

        return ApiResponse::success(['pending' => []]);
    }

    /** @return list<array<string, mixed>> */
    public static function present(LegalStatus $status, \App\Models\User $user): array
    {
        $urls = ['terms' => 'legal.terms', 'privacy' => 'legal.privacy'];

        return array_map(fn (array $doc) => $doc + [
            'url' => isset($urls[$doc['document']]) ? \App\Support\CentralUrl::route($urls[$doc['document']]) : null,
        ], $status->pending($user, LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS));
    }
}
