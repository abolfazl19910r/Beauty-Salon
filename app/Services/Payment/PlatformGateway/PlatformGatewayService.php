<?php

namespace App\Services\Payment\PlatformGateway;

use App\Models\PlatformGatewayRequest;
use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\User;
use App\Payments\PlatformGateway;
use App\Services\SMSService;
use App\Services\SuperAdmin\SuperAdminAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * چرخه‌ی درخواست «درگاه پرداخت پلتفرم» (۲۰۲۶-۱۱-۲۲): مالک درخواست می‌دهد ← ابرمدیر با درصد کارمزد تأیید یا با دلیل رد می‌کند ←
 * با تأیید، ردیف درگاه سالن با driver = platform روشن می‌شود (اولویت اول) و با لغو، خاموش (برای verify پرداخت‌های در جریان می‌ماند).
 * هر تصمیم در «گزارش کارهای ابرمدیر» و با پیامک به مالک.
 */
class PlatformGatewayService
{
    public function latestFor(Salon $salon): ?PlatformGatewayRequest
    {
        return PlatformGatewayRequest::query()->where('salon_id', $salon->id)->latest('id')->first();
    }

    public function activeFor(Salon $salon): ?PlatformGatewayRequest
    {
        return PlatformGatewayRequest::query()->where('salon_id', $salon->id)->where('status', 'approved')->latest('id')->first();
    }

    public function hasOpenRequest(Salon $salon): bool
    {
        return PlatformGatewayRequest::query()->where('salon_id', $salon->id)->whereIn('status', ['pending', 'approved'])->exists();
    }

    /** @param  array{payout_method: string, destination: string, payout_bank?: ?string, account_holder: string, owner_note?: ?string}  $data */
    public function submit(Salon $salon, User $by, array $data): PlatformGatewayRequest
    {
        return DB::transaction(function () use ($salon, $by, $data) {
            Salon::withoutGlobalScopes()->whereKey($salon->id)->lockForUpdate()->first();
            abort_if($this->hasOpenRequest($salon), 409, 'درخواست باز دیگری هست.');

            return PlatformGatewayRequest::create([
                'salon_id' => $salon->id,
                'requested_by' => $by->id,
                'status' => 'pending',
                'payout_method' => $data['payout_method'],
                'payout_iban' => $data['payout_method'] === 'iban' ? $data['destination'] : null,
                'payout_card' => $data['payout_method'] === 'card' ? $data['destination'] : null,
                'payout_account' => $data['payout_method'] === 'account' ? $data['destination'] : null,
                'payout_bank' => $data['payout_bank'] ?? null,
                'account_holder' => $data['account_holder'],
                'owner_note' => $data['owner_note'] ?? null,
            ]);
        });
    }

    public function approve(PlatformGatewayRequest $request, User $reviewer, float $feePercent, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $feePercent, $note) {
            $request->update([
                'status' => 'approved', 'fee_percent' => $feePercent, 'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(), 'review_note' => $note,
            ]);
            $this->gatewayRow($request->salon_id)->fill(['is_active' => true, 'label' => PlatformGateway::label()])->save();
        });

        app(SuperAdminAudit::class)->record('platform_gateway.approve', sprintf('تأیید درگاه پلتفرم با کارمزد %s٪', $this->percent($feePercent)), $request->salon_id);
        $this->tellOwner($request, sprintf('درخواست درگاه پرداخت %s برای سالن «%s» تأیید شد (کارمزد %s٪). %s',
            config('brand.name'), $request->salon->name, $this->percent($feePercent), config('brand.name')));
    }

    public function reject(PlatformGatewayRequest $request, User $reviewer, string $reason): void
    {
        $request->update(['status' => 'rejected', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_note' => $reason]);

        app(SuperAdminAudit::class)->record('platform_gateway.reject', 'رد درخواست درگاه پلتفرم: '.$reason, $request->salon_id);
        $this->tellOwner($request, sprintf('درخواست درگاه پرداخت %s برای سالن «%s» تأیید نشد: %s %s',
            config('brand.name'), $request->salon->name, Str::limit($reason, 80), config('brand.name')));
    }

    /** درصد تازه فقط برای پرداخت‌های بعدی؛ تراکنش‌های گذشته درصد خودشان را نگه داشته‌اند */
    public function changeFee(PlatformGatewayRequest $request, User $by, float $feePercent): void
    {
        $old = (float) $request->fee_percent;
        $request->update(['fee_percent' => $feePercent]);

        app(SuperAdminAudit::class)->record('platform_gateway.fee', sprintf('تغییر کارمزد درگاه پلتفرم از %s٪ به %s٪ (فقط پرداخت‌های بعدی)',
            $this->percent($old), $this->percent($feePercent)), $request->salon_id, ['fee_percent' => [$old, $feePercent]]);
    }

    /** لغو: درگاه پلتفرم برای پرداخت تازه خاموش؛ مانده‌ی تسویه‌نشده همچنان قابل تسویه است */
    public function revoke(PlatformGatewayRequest $request, User $by, string $reason): void
    {
        DB::transaction(function () use ($request, $by, $reason) {
            $request->update(['status' => 'revoked', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $reason]);
            SalonPaymentGateway::query()->where('salon_id', $request->salon_id)->where('driver', PlatformGateway::DRIVER)->update(['is_active' => false]);
        });

        app(SuperAdminAudit::class)->record('platform_gateway.revoke', 'لغو درگاه پلتفرم: '.$reason, $request->salon_id);
        $this->tellOwner($request, sprintf('درگاه پرداخت %s برای سالن «%s» غیرفعال شد: %s %s',
            config('brand.name'), $request->salon->name, Str::limit($reason, 80), config('brand.name')));
    }

    private function gatewayRow(int $salonId): SalonPaymentGateway
    {
        return SalonPaymentGateway::query()->firstOrNew(
            ['salon_id' => $salonId, 'driver' => PlatformGateway::DRIVER],
            // اولویت اول: سالن خودش این درگاه را خواسته؛ بدون کارمزد اضافه روی مبلغ مشتری
            ['credentials' => [], 'priority' => 0, 'fee_percent' => 0, 'fee_fixed_toman' => 0],
        );
    }

    private function percent(float $value): string
    {
        return to_persian_num(rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'));
    }

    private function tellOwner(PlatformGatewayRequest $request, string $text): void
    {
        try {
            if ($phone = $request->salon?->owner()?->phone) {
                app(SMSService::class)->send($phone, $text);
            }
        } catch (\Throwable $e) {
            Log::channel('payments')->warning('platform gateway SMS failed', ['salon_id' => $request->salon_id, 'error' => $e->getMessage()]);
        }
    }
}
