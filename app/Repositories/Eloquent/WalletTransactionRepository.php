<?php

namespace App\Repositories\Eloquent;

use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

class WalletTransactionRepository extends BaseRepository implements WalletTransactionRepositoryInterface
{
    public function __construct(WalletTransaction $model)
    {
        parent::__construct($model);
    }

    public function paginateForWalletWithFilters(int $walletId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->where('wallet_id', $walletId)->with('booking');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function getRecentForWallet(int $walletId, int $limit = 10): Collection
    {
        return $this->model->where('wallet_id', $walletId)->latest()->limit($limit)->get();
    }

    /** جمع یک نوع تراکنش در بازه‌ی [from, to) (مثلاً برداشت‌های ماه شمسی) */
    public function sumForWalletByTypeBetween(int $walletId, string $type, \DateTimeInterface $from, \DateTimeInterface $to): float
    {
        return (float) $this->model->where('wallet_id', $walletId)
            ->where('type', $type)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->sum('amount');
    }

    /**
     * درآمد خالص در بازه‌ی [from, to): درآمدها منهای برگشت سهمِ نوبت‌های لغوشده (تعدیل‌هایی که به نوبت وصل‌اند).
     * قبلاً فقط income جمع می‌شد، پس لغو نوبت «کل درآمد» را کم می‌کرد ولی «درآمد این ماه» را نه. تعدیل دستی ادمین (بدون نوبت)
     * درآمد حساب نمی‌شود. بسته‌ی ۳ ظاهری (۲۰۲۶-۱۱-۲۵): با بازه به‌جای ماه میلادی، تا «ماه» شمسی باشد.
     */
    public function sumNetIncomeForWalletBetween(int $walletId, \DateTimeInterface $from, \DateTimeInterface $to): float
    {
        return (float) $this->netIncomeQuery($walletId)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->sum('amount');
    }

    /**
     * درآمد خالص روزبه‌روز در بازه‌ی [from, to)؛ کلید Y-m-d (روز میلادی ثبت تراکنش). روزهای بی تراکنش در خروجی نیستند.
     *
     * @return array<string, float>
     */
    public function netIncomeByDayForWallet(int $walletId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->netIncomeQuery($walletId)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get(['amount', 'created_at'])
            ->groupBy(fn ($t) => $t->created_at->format('Y-m-d'))
            ->map(fn ($rows) => (float) $rows->sum('amount'))
            ->all();
    }

    private function netIncomeQuery(int $walletId)
    {
        return $this->model->where('wallet_id', $walletId)
            ->where(fn ($q) => $q->where('type', 'income')
                ->orWhere(fn ($q) => $q->where('type', 'adjustment')->whereNotNull('booking_id')));
    }

    /**
     * درآمدهای pending همه‌ی سالن‌ها (یا یک کیف پول)، تکه‌تکه به ترتیب id. در ۱۰۰۰ سالن ~۹۷ هزار ردیف است و get() یک‌جا تا ۴۵۰ MB
     * حافظه می‌گرفت. lazyById با «id بزرگ‌تر از آخرین» صفحه می‌زند، پس تسویه‌ی ردیف‌های یک تکه (که از شرط pending بیرون‌شان می‌برد)
     * ردیفی از تکه‌ی بعد را جا نمی‌اندازد. نوبت هر درآمد (برای چک «ساعت نوبت گذشته؟») برای هر تکه یک‌جا بار می‌شود، نه یک کوئری برای هر درآمد.
     */
    public function lazyPendingIncomeForSettlement(?int $walletId = null, int $chunkSize = 1000): LazyCollection
    {
        $query = $this->model->where('type', 'income')
            ->whereJsonContains('metadata->status', 'pending')
            ->with(['booking:id,booking_time,status', 'wallet']);

        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        return $query->lazyById($chunkSize);
    }
}
