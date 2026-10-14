<?php

namespace App\Services\Courses;

use App\Models\CustomerCourse;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\Payment\BookingWalletShare;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * پرداخت آنلاین دوره‌ی درمانی — وب و اپ (بسته‌ی ب). همان الگوی پیش‌پرداخت نوبت: کیف پول اول، باقی از درگاه سالن، سهم کیف پول
 * روی ردیف تراکنش (BookingWalletShare: held → settled یا released)، صفحه‌ی بانک در مرورگر بیرونی برای اپ.
 *
 * مبلغ هر پرداخت: در انتظار پرداخت ← دست‌کم حداقل پیش‌پرداخت (یا کل)؛ فعال ← هر مقدار تا باقی‌مانده. درگاه پول را تأیید کرد ولی
 * دوره دیگر پرداخت نمی‌پذیرد (مثلاً مدیر لغوش کرد) ← کل مبلغ به کیف پول مشتری.
 *
 * خروجی start: ['state' => paid|redirect|error, 'course' => CustomerCourse, 'gateway' => ?array, 'message' => string]
 */
class CoursePaymentService
{
    public const MIN_PARTIAL = 10000;

    public function __construct(
        private readonly PaymentService $payments,
        private readonly CourseService $courses,
        private readonly BookingWalletShare $walletShare,
    ) {}

    /** @return string|null خطای مبلغ */
    public function amountError(CustomerCourse $course, float $amount): ?string
    {
        $due = $course->balanceDue();

        return match (true) {
            in_array($course->status, ['cancelled', 'completed', 'expired'], true) => 'این دوره دیگر پرداخت نمی‌پذیرد.',
            $due <= 0 => 'این دوره کامل پرداخت شده است.',
            $amount > $due + 0.5 => sprintf('بیشترین مبلغ قابل پرداخت %s تومان است.', number_format($due)),
            $course->status === 'pending_payment' && $amount + 0.5 < $course->min_prepayment => sprintf('حداقل پرداخت اول %s تومان است.', number_format($course->min_prepayment)),
            $course->status !== 'pending_payment' && $amount < min(self::MIN_PARTIAL, $due) => sprintf('حداقل مبلغ پرداخت %s تومان است.', number_format(min(self::MIN_PARTIAL, $due))),
            default => null,
        };
    }

    public function start(User $user, CustomerCourse $course, float $amount, float $walletToman, ?int $gatewayId, string $callbackUrl): array
    {
        $amount = round(min($amount, $course->balanceDue()), 2);
        if ($error = $this->amountError($course, $amount)) {
            return ['state' => 'error', 'course' => $course, 'gateway' => null, 'message' => $error];
        }

        $balance = (float) $user->getOrCreateWallet()->fresh()->balance;
        $walletToman = round(max(0.0, min($walletToman, $balance, $amount)), 2);

        if ($walletToman + 0.5 >= $amount) {
            return $this->payFromWallet($user, $course, $amount);
        }

        if ($walletToman > 0) {
            $this->deductWallet($user, $course, $walletToman, 'پرداخت بخشی از دوره از کیف پول (مابقی از درگاه)');
        }

        $result = $this->payments->createCoursePayment($course, $amount - $walletToman, $gatewayId, $walletToman, $callbackUrl);

        if (($result['success'] ?? false) && isset($result['payment_url'])) {
            return ['state' => 'redirect', 'course' => $course, 'gateway' => $result, 'message' => ''];
        }

        // درگاه در دسترس نبود: startWith سهم کیف پول را روی همان تراکنش برگردانده؛ اگر اصلاً تراکنشی ساخته نشد، این‌جا
        if ($walletToman > 0 && ($result['reason'] ?? null) === 'merchant_missing') {
            $user->getOrCreateWallet()->addRefund($walletToman, null, 'بازگشت سهم کیف پول دوره — پرداخت آنلاین در دسترس نیست', ['customer_course_id' => $course->id]);
        }

        return ['state' => 'error', 'course' => $course, 'gateway' => null, 'message' => $result['message'] ?? 'در حال حاضر امکان اتصال به درگاه بانکی وجود ندارد.'];
    }

    private function payFromWallet(User $user, CustomerCourse $course, float $amount): array
    {
        try {
            DB::transaction(function () use ($user, $course, $amount) {
                $this->deductWallet($user, $course, $amount, "پرداخت دوره‌ی «{$course->name}» از کیف پول");
                $this->courses->recordPayment($course, $amount, 'wallet', ['wallet_amount' => $amount, 'reference' => 'WALLET-C'.$course->id.'-'.time()]);
            });
        } catch (CourseException $e) {
            return ['state' => 'error', 'course' => $course->fresh(), 'gateway' => null, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['state' => 'error', 'course' => $course->fresh(), 'gateway' => null, 'message' => 'موجودی کیف پول کافی نیست.'];
        }

        return ['state' => 'paid', 'course' => $course->fresh(), 'gateway' => null, 'message' => 'پرداخت از کیف پول انجام شد.'];
    }

    private function deductWallet(User $user, CustomerCourse $course, float $amount, string $description): void
    {
        DB::transaction(function () use ($user, $course, $amount, $description) {
            $wallet = $user->getOrCreateWallet();
            $wallet = UserWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $wallet->deductPayment($amount, null, $description, ['customer_course_id' => $course->id]);
        });
    }

    /**
     * بازگشت از درگاه (وب و اپ). خروجی: ['state' => paid|refunded|failed, 'course' => ?CustomerCourse, 'message' => string]
     */
    public function finalize(Request $request): array
    {
        $result = $this->payments->verifyCoursePayment($request);
        /** @var PaymentTransaction|null $tx */
        $tx = $result['transaction'];
        $course = $tx ? CustomerCourse::withoutGlobalScopes()->find($tx->payable_id) : null;

        if (! $tx || ! $course) {
            return ['state' => 'failed', 'course' => null, 'message' => $result['message'] ?? 'پرداخت پیدا نشد.'];
        }

        if (! $result['success']) {
            $this->walletShare->release($tx, 'پرداخت دوره ناموفق بود');

            return ['state' => 'failed', 'course' => $course, 'message' => $result['message'] ?? 'پرداخت ناموفق بود'];
        }

        $gatewayToman = intdiv((int) $tx->amount_rial - (int) $tx->fee_rial, 10);

        try {
            $payment = DB::transaction(function () use ($tx, $course, $gatewayToman, $result) {
                $walletToman = $this->walletShare->settle($tx);

                // سهم کیف پولی که قبلاً settle شده (callback تکراری) در خود ردیف پرداخت هست
                return $this->courses->recordPayment($course, $gatewayToman + $walletToman, $walletToman > 0 ? 'wallet_gateway' : 'gateway', [
                    'payment_transaction_id' => $tx->id,
                    'wallet_amount' => $walletToman,
                    'reference' => $result['ref_id'] ?? $tx->ref_id,
                ]);
            });
        } catch (CourseException) {
            return $this->refundToWallet($tx, $course);
        }

        return ['state' => 'paid', 'course' => $course->fresh(), 'message' => $payment === null && ($result['already'] ?? false)
            ? 'این پرداخت قبلاً ثبت شده است.'
            : 'پرداخت انجام شد.'];
    }

    /** پول تأیید شد ولی دوره دیگر پرداخت نمی‌پذیرد → کل مبلغ (درگاه + سهم کیف پول) به کیف پول؛ یک بار */
    private function refundToWallet(PaymentTransaction $tx, CustomerCourse $course): array
    {
        if (PaymentTransaction::whereKey($tx->id)->where('status', 'paid')->toBase()->update(['status' => 'refunded', 'updated_at' => now()]) !== 1) {
            return ['state' => 'refunded', 'course' => $course, 'message' => 'مبلغ این پرداخت قبلاً به کیف پول شما برگشت داده شده است.'];
        }

        $toman = intdiv((int) $tx->amount_rial, 10);
        $wallet = $this->walletShare->claim($tx);
        User::find($tx->user_id)?->getOrCreateWallet()->addRefund($toman + $wallet, null, 'بازگشت پرداخت دوره‌ای که دیگر پرداخت نمی‌پذیرد', ['customer_course_id' => $course->id]);

        return ['state' => 'refunded', 'course' => $course, 'message' => 'این دوره دیگر پرداخت نمی‌پذیرد؛ مبلغ به کیف پول شما برگشت داده شد.'];
    }
}
