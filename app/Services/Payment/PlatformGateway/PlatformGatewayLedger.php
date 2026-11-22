<?php

namespace App\Services\Payment\PlatformGateway;

use App\Models\PaymentTransaction;
use App\Models\PlatformGatewayRequest;
use App\Models\PlatformSettlement;
use App\Models\Salon;
use App\Models\User;
use App\Payments\PlatformGateway;
use App\Services\SuperAdmin\SuperAdminAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * حساب درگاه پلتفرم هر سالن، جدا از سالن‌های دیگر (۲۰۲۶-۱۱-۲۲). همه‌ی جمع‌ها از payment_transactions با driver = platform و
 * salon_id همان سالن، به ریال:
 * - gross: کل مبلغ دریافتی از مشتری‌ها (وضعیت paid یا refunded)
 * - platform: سهمی که نزد پلتفرم می‌ماند (درصد عکس‌برداری‌شده‌ی هر تراکنش)
 * - salon: سهم سالن = gross − platform
 * - settled: سهم سالن در تسویه‌های «واریز شد» · pending: در تسویه‌های «منتظر واریز» · outstanding: هنوز در هیچ تسویه‌ای نیست
 */
class PlatformGatewayLedger
{
    public function transactions(int $salonId): Builder
    {
        return PaymentTransaction::query()->where('driver', PlatformGateway::DRIVER)->where('salon_id', $salonId);
    }

    /** @return array{gross: int, platform: int, salon: int, settled: int, pending: int, outstanding: int, count: int, unsettled_count: int} */
    public function summary(int $salonId): array
    {
        return $this->summaries([$salonId])[$salonId] ?? $this->empty();
    }

    /**
     * @param  list<int>|null  $salonIds  null = همه‌ی سالن‌هایی که تراکنش درگاه پلتفرم دارند
     * @return array<int, array{gross: int, platform: int, salon: int, settled: int, pending: int, outstanding: int, count: int, unsettled_count: int}>
     */
    public function summaries(?array $salonIds = null): array
    {
        $rows = PaymentTransaction::query()
            ->leftJoin('platform_settlements as s', 's.id', '=', 'payment_transactions.platform_settlement_id')
            ->where('payment_transactions.driver', PlatformGateway::DRIVER)
            ->whereIn('payment_transactions.status', PlatformGateway::SETTLEABLE_STATUSES)
            ->when($salonIds !== null, fn ($q) => $q->whereIn('payment_transactions.salon_id', $salonIds))
            ->groupBy('payment_transactions.salon_id')
            ->selectRaw('payment_transactions.salon_id as salon_id')
            ->selectRaw('count(*) as tx_count')
            ->selectRaw('sum(payment_transactions.amount_rial) as gross')
            ->selectRaw('sum(coalesce(payment_transactions.platform_share_rial, 0)) as platform')
            ->selectRaw("sum(case when s.status = 'paid' then payment_transactions.amount_rial - coalesce(payment_transactions.platform_share_rial, 0) else 0 end) as settled")
            ->selectRaw("sum(case when s.status = 'pending' then payment_transactions.amount_rial - coalesce(payment_transactions.platform_share_rial, 0) else 0 end) as pending")
            ->selectRaw('sum(case when payment_transactions.platform_settlement_id is null then payment_transactions.amount_rial - coalesce(payment_transactions.platform_share_rial, 0) else 0 end) as outstanding')
            ->selectRaw('sum(case when payment_transactions.platform_settlement_id is null then 1 else 0 end) as unsettled_count')
            ->toBase()->get();

        $out = [];
        foreach ($rows as $r) {
            $gross = (int) $r->gross;
            $platform = (int) $r->platform;
            $out[(int) $r->salon_id] = [
                'gross' => $gross, 'platform' => $platform, 'salon' => $gross - $platform,
                'settled' => (int) $r->settled, 'pending' => (int) $r->pending, 'outstanding' => (int) $r->outstanding,
                'count' => (int) $r->tx_count, 'unsettled_count' => (int) $r->unsettled_count,
            ];
        }

        return $out;
    }

    /** @return array{gross: int, platform: int, salon: int, settled: int, pending: int, outstanding: int, count: int, unsettled_count: int} */
    public function empty(): array
    {
        return ['gross' => 0, 'platform' => 0, 'salon' => 0, 'settled' => 0, 'pending' => 0, 'outstanding' => 0, 'count' => 0, 'unsettled_count' => 0];
    }

    /**
     * یک تسویه از همه‌ی تراکنش‌های تسویه‌نشده‌ی سالن (تا cutoff) با مقصد بانکی آخرین درخواست تأییدشده یا لغوشده. null = چیزی برای
     * تسویه نیست (یا سالن مقصد بانکی ندارد). دو اجرای هم‌زمان یک تراکنش را دو بار تسویه نمی‌کنند (lockForUpdate روی همان ردیف‌ها).
     */
    public function createSettlement(Salon $salon, ?User $by, bool $automatic = false, ?Carbon $cutoff = null, ?string $note = null): ?PlatformSettlement
    {
        $destination = PlatformGatewayRequest::query()->where('salon_id', $salon->id)->whereIn('status', ['approved', 'revoked'])
            ->latest('id')->first();
        if (! $destination) {
            return null;
        }

        $settlement = DB::transaction(function () use ($salon, $by, $automatic, $cutoff, $note, $destination) {
            $txs = $this->transactions($salon->id)
                ->whereIn('status', PlatformGateway::SETTLEABLE_STATUSES)
                ->whereNull('platform_settlement_id')
                ->when($cutoff, fn ($q) => $q->where('updated_at', '<=', $cutoff))
                ->orderBy('id')->lockForUpdate()->get();

            if ($txs->isEmpty()) {
                return null;
            }

            $gross = (int) $txs->sum('amount_rial');
            $platform = (int) $txs->sum(fn (PaymentTransaction $t) => (int) $t->platform_share_rial);

            $settlement = PlatformSettlement::create([
                'salon_id' => $salon->id,
                'status' => 'pending',
                'transactions_count' => $txs->count(),
                'gross_rial' => $gross,
                'platform_share_rial' => $platform,
                'salon_share_rial' => $gross - $platform,
                'period_from' => $txs->min('created_at'),
                'period_to' => $txs->max('created_at'),
                'payout_method' => $destination->payout_method,
                'payout_destination' => $destination->destination(),
                'account_holder' => $destination->account_holder,
                'automatic' => $automatic,
                'created_by' => $by?->id,
                'note' => $note,
            ]);

            PaymentTransaction::query()->whereIn('id', $txs->pluck('id'))->whereNull('platform_settlement_id')
                ->toBase()->update(['platform_settlement_id' => $settlement->id]);

            return $settlement;
        });

        if ($settlement) {
            app(SuperAdminAudit::class)->record('platform_gateway.settlement', sprintf('ساخت تسویه‌ی #%d درگاه پلتفرم%s: %s تومان برای سالن',
                $settlement->id, $automatic ? ' (خودکار)' : '', number_format(intdiv($settlement->salon_share_rial, 10))), $salon->id);
        }

        return $settlement;
    }

    public function markPaid(PlatformSettlement $settlement, User $by, string $bankReference, ?Carbon $paidAt = null): void
    {
        abort_unless($settlement->status === 'pending', 409);
        $settlement->update(['status' => 'paid', 'bank_reference' => $bankReference, 'paid_at' => $paidAt ?? now(), 'paid_by' => $by->id]);

        app(SuperAdminAudit::class)->record('platform_gateway.settlement', sprintf('واریز تسویه‌ی #%d درگاه پلتفرم (پیگیری %s)', $settlement->id, $bankReference), $settlement->salon_id);
    }

    /** لغو تسویه‌ی منتظر واریز: تراکنش‌هایش دوباره «تسویه‌نشده» می‌شوند */
    public function cancel(PlatformSettlement $settlement, User $by, ?string $reason = null): void
    {
        DB::transaction(function () use ($settlement, $reason) {
            $locked = PlatformSettlement::query()->whereKey($settlement->id)->lockForUpdate()->first();
            abort_unless($locked && $locked->status === 'pending', 409);
            PaymentTransaction::query()->where('platform_settlement_id', $locked->id)->toBase()->update(['platform_settlement_id' => null]);
            $locked->update(['status' => 'cancelled', 'note' => trim(($locked->note ? $locked->note.' — ' : '').($reason ?? ''), ' —') ?: null]);
        });

        app(SuperAdminAudit::class)->record('platform_gateway.settlement', sprintf('لغو تسویه‌ی #%d درگاه پلتفرم', $settlement->id), $settlement->salon_id);
    }

    /**
     * تسویه‌ی خودکار هفتگی: برای هر سالن با درخواست تأییدشده یا لغوشده که مانده‌اش دست‌کم $minRial است یک تسویه‌ی «منتظر واریز».
     *
     * @return list<PlatformSettlement>
     */
    public function createDue(int $minRial, ?Carbon $cutoff = null): array
    {
        $salonIds = PlatformGatewayRequest::query()->whereIn('status', ['approved', 'revoked'])->distinct()->pluck('salon_id')->all();
        $created = [];

        foreach ($this->summaries($salonIds) as $salonId => $sum) {
            if ($sum['outstanding'] < max(1, $minRial)) {
                continue;
            }
            $salon = Salon::withoutGlobalScopes()->find($salonId);
            if ($salon && ($s = $this->createSettlement($salon, null, true, $cutoff))) {
                $created[] = $s;
            }
        }

        return $created;
    }
}
