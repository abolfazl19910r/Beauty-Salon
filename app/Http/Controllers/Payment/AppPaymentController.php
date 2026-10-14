<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\GatewayRedirect;
use App\Services\Api\CustomerPresenter;
use App\Services\Payment\AppCheckout;
use App\Services\Payment\BookingPaymentFinalizer;
use App\Services\Payment\WalletChargeService;
use App\Services\PaymentService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * صفحه‌های وبِ پرداخت اپ «ماهرو» (بسته‌ی ۳؛ زیر /s/{slug}/app، بدون ورود و بدون session):
 *  - checkout: لینک امضاشده‌ای که API پرداخت می‌دهد و اپ در مرورگر بیرونی باز می‌کند → درگاه.
 *  - payment / wallet: callback کسب‌وکار تراکنش‌های اپ (GatewayReturnController با 303 به این‌جا می‌فرستد). تأیید واقعی
 *    با درگاه و مبلغ ثبت‌شده در ردیف تراکنش؛ بدون tx معتبر (همین سالن، همین نوع) هیچ کاری انجام نمی‌شود. نتیجه: صفحه‌ای که
 *    با mahru://payment?tx=... مشتری را به اپ برمی‌گرداند.
 */
class AppPaymentController extends Controller
{
    public function __construct(private readonly CurrentSalon $currentSalon) {}

    public function checkout(string $publicId, AppCheckout $checkout): RedirectResponse|Response
    {
        $data = $checkout->find($publicId);
        $transaction = PaymentTransaction::where('public_id', $publicId)->where('salon_id', $this->currentSalon->id())->first();

        if (! $data || ! $transaction || $transaction->status !== 'pending') {
            return $this->page($publicId, 'failed', 'booking', 'مهلت این پرداخت تمام شده است', 'به اپ برگردید و دوباره «پرداخت» را بزنید.');
        }

        return GatewayRedirect::to($data);
    }

    public function booking(Request $request, BookingPaymentFinalizer $finalizer): Response
    {
        $transaction = $this->transaction($request, 'booking');
        if (! $transaction) {
            return $this->page((string) $request->query('tx'), 'failed', 'booking', 'پرداخت پیدا نشد', 'به اپ برگردید و وضعیت نوبت را ببینید.');
        }

        // شناسه‌ی نوبت فقط از ردیف تراکنش (نه از آدرس) — جلوی تأیید یک نوبت با رسید پرداخت نوبت دیگر را می‌گیرد
        $request->merge(['booking' => $transaction->payable_id, 'tx' => $transaction->public_id]);

        try {
            $outcome = $finalizer->handle($request);
        } catch (\Throwable $e) {
            Log::error('App payment callback failed', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            $outcome = ['state' => 'failed', 'message' => 'خطا در تأیید پرداخت. وضعیت نوبت را در اپ ببینید.'];
        }

        $title = match ($outcome['state']) {
            'paid' => 'پرداخت انجام شد',
            'refunded' => 'نوبت ثبت نشد؛ مبلغ برگشت داده شد',
            default => 'پرداخت ناموفق بود',
        };

        return $this->page($transaction->public_id, $outcome['state'], 'booking', $title, $outcome['message']);
    }

    public function wallet(Request $request, PaymentService $paymentService, WalletChargeService $charges): Response
    {
        $transaction = $this->transaction($request, 'wallet_charge');
        $user = $transaction ? User::find($transaction->user_id) : null;
        if (! $transaction || ! $user) {
            return $this->page((string) $request->query('tx'), 'failed', 'wallet_charge', 'پرداخت پیدا نشد', 'به اپ برگردید و موجودی کیف پول را ببینید.');
        }

        $amount = intdiv((int) $transaction->amount_rial - (int) $transaction->fee_rial, 10);
        $request->merge(['tx' => $transaction->public_id]);
        $result = $paymentService->verifyWalletChargePayment($request, $amount);

        if (! $result['success']) {
            $credited = $transaction->fresh()->wallet_state === WalletChargeService::CREDITED;

            return $credited
                ? $this->page($transaction->public_id, 'paid', 'wallet_charge', 'کیف پول شارژ شد', 'این شارژ قبلاً به کیف پول شما اضافه شده است.')
                : $this->page($transaction->public_id, CustomerPresenter::paymentState($transaction->fresh()->status), 'wallet_charge', 'شارژ کیف پول ناموفق بود', $result['message'] ?? 'پرداخت ناموفق بود');
        }

        $balance = $charges->credit($user, (float) $amount, $result, $transaction);

        return $this->page($transaction->public_id, 'paid', 'wallet_charge', 'کیف پول شارژ شد', $balance === null
            ? 'این شارژ قبلاً به کیف پول شما اضافه شده است.'
            : number_format($amount).' تومان به کیف پول شما اضافه شد.');
    }

    /** پرداخت دوره‌ی درمانی از اپ (بسته‌ی ب) — همان CoursePaymentService وب */
    public function course(Request $request, \App\Services\Courses\CoursePaymentService $coursePayments): Response
    {
        $transaction = $this->transaction($request, 'course');
        if (! $transaction) {
            return $this->page((string) $request->query('tx'), 'failed', 'course', 'پرداخت پیدا نشد', 'به اپ برگردید و وضعیت دوره را ببینید.');
        }

        $request->merge(['tx' => $transaction->public_id]);

        try {
            $outcome = $coursePayments->finalize($request);
        } catch (\Throwable $e) {
            Log::error('App course payment callback failed', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            $outcome = ['state' => 'failed', 'message' => 'خطا در تأیید پرداخت. وضعیت دوره را در اپ ببینید.'];
        }

        $title = match ($outcome['state']) {
            'paid' => 'پرداخت انجام شد',
            'refunded' => 'مبلغ به کیف پول برگشت داده شد',
            default => 'پرداخت ناموفق بود',
        };

        return $this->page($transaction->public_id, $outcome['state'], 'course', $title, $outcome['message']);
    }

    private function transaction(Request $request, string $purpose): ?PaymentTransaction
    {
        $publicId = $request->query('tx');

        return is_string($publicId) && $publicId !== ''
            ? PaymentTransaction::where('public_id', $publicId)->where('salon_id', $this->currentSalon->id())->where('purpose', $purpose)->first()
            : null;
    }

    private function page(string $publicId, string $state, string $purpose, string $title, string $message): Response
    {
        return response()->view('payments.app.return', [
            'state' => $state,
            'title' => $title,
            'message' => $message,
            'salonName' => $this->currentSalon->get()?->name ?? config('brand.name'),
            'deepLink' => AppCheckout::deepLink($publicId, $state, $purpose),
        ])->header('Cache-Control', 'no-store, private');
    }
}
