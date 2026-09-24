<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserWalletTransactionRepositoryInterface;
use App\Services\PaymentService;
use App\Traits\HasJalaliDates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserWalletController extends Controller
{
    use HasJalaliDates;

    public function __construct(
        protected readonly PaymentService $paymentService,
        protected readonly UserWalletTransactionRepositoryInterface $userWalletTransactionRepository,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $recentTransactions = $this->userWalletTransactionRepository->getRecentForWallet($wallet->id, 10);

        $currentMonthRefunds = $this->userWalletTransactionRepository->sumForWalletByTypeAndMonth(
            $wallet->id, 'refund', now()->month, now()->year
        );

        $currentMonthSpent = $this->userWalletTransactionRepository->sumForWalletByTypeAndMonth(
            $wallet->id, 'payment', now()->month, now()->year
        );

        return view('user.wallet.index', compact(
            'user',
            'wallet',
            'recentTransactions',
            'currentMonthRefunds',
            'currentMonthSpent'
        ));
    }

    public function transactions(Request $request): View
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $filters = [];

        if ($request->filled('type')) {
            $filters['type'] = $request->type;
        }

        if ($request->filled('date_from')) {
            if ($dateFrom = $this->parseJalali($request->date_from)) {
                $filters['date_from'] = $dateFrom->startOfDay();
            }
        }

        if ($request->filled('date_to')) {
            if ($dateTo = $this->parseJalali($request->date_to)) {
                $filters['date_to'] = $dateTo->endOfDay();
            }
        }

        $transactions = $this->userWalletTransactionRepository->paginateForWalletWithFilters($wallet->id, $filters, 20);

        return view('user.wallet.transactions', compact('user', 'wallet', 'transactions'));
    }

    public function showTransaction(\App\Models\UserWalletTransaction $transaction): View
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $this->authorize('viewTransaction', $transaction);

        $transaction->load('booking.service', 'booking.specialist');

        return view('user.wallet.transaction-show', compact('user', 'wallet', 'transaction'));
    }

    public function showCharge(): View
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $suggestedAmounts = [50000, 100000, 200000, 500000, 1000000];

        // ⭐ مرحله‌ی ۱ چند درگاه: درگاه‌های فعال سالن با کارمزدشان (مبلغ نهایی در صفحه با JS حساب می‌شه).
        $gatewayOptions = $this->paymentService->gatewayOptions(0);

        return view('user.wallet.charge', compact('user', 'wallet', 'suggestedAmounts', 'gatewayOptions'));
    }

    public function processCharge(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        try {
            $amountInput = $request->input('amount');
            $amountInput = $this->convertPersianNumbers($amountInput);
            $amountInput = preg_replace('/[^0-9]/', '', $amountInput);

            $request->merge(['amount' => $amountInput]);

            $validated = $request->validate([
                'amount' => 'required|numeric|min:10000|max:50000000',
            ], [
                'amount.required' => 'لطفاً مبلغ شارژ را وارد کنید.',
                'amount.numeric' => 'مبلغ باید عدد باشد.',
                'amount.min' => 'حداقل مبلغ شارژ 10,000 تومان است.',
                'amount.max' => 'حداکثر مبلغ شارژ 50,000,000 تومان است.',
            ]);

            $amount = (float) $amountInput;

            if ($amount < 10000) {
                return back()->withErrors(['amount' => 'حداقل مبلغ شارژ 10,000 تومان است.'])->withInput();
            }

            if ($amount > 50000000) {
                return back()->withErrors(['amount' => 'حداکثر مبلغ شارژ 50,000,000 تومان است.'])->withInput();
            }

            $user = auth()->user();

            session([
                'wallet_charge_pending' => [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'created_at' => now()->timestamp,
                ],
            ]);

            $chargeId = 'CHARGE-'.$user->id.'-'.time();

            session(['wallet_charge_id' => $chargeId]);

            $gatewayId = $request->input('gateway_id');
            $result = $this->paymentService->createWalletChargePayment($user, $amount, is_numeric($gatewayId) ? (int) $gatewayId : null);

            if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                return \App\Payments\GatewayRedirect::to($result);
            }

            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::error('❌ خطا در دریافت URL درگاه', [
                'user_id' => $user->id,
                'result' => $result,
            ]);

            return back()
                ->withErrors(['amount' => 'خطا در اتصال به درگاه پرداخت: '.($result['message'] ?? 'خطای نامشخص')])
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ خطای اعتبارسنجی شارژ', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error('💥 خطا در پردازش شارژ کیف پول', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['amount' => 'خطا در پردازش درخواست: '.$e->getMessage()])
                ->withInput();
        }
    }

    private function convertPersianNumbers($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $string = str_replace($persian, $english, $string);
        $string = str_replace($arabic, $english, $string);

        return $string;
    }

    public function chargeCallback(Request $request): RedirectResponse
    {
        try {
            $chargePending = session('wallet_charge_pending');

            if (! $chargePending) {
                return redirect()->route('wallet.index')
                    ->with('error', 'اطلاعات شارژ یافت نشد.');
            }

            $result = $this->paymentService->verifyWalletChargePayment($request, $chargePending['amount']);

            if ($result['success']) {
                return DB::transaction(function () use ($chargePending, $result) {
                    $user = $this->userRepository->findOrFail($chargePending['user_id']);
                    $wallet = $user->getOrCreateWallet();
                    $wallet->increment('balance', $chargePending['amount']);
                    $wallet->increment('total_deposited', $chargePending['amount']);
                    $wallet->transactions()->create([
                        'type' => 'deposit',
                        'amount' => $chargePending['amount'],
                        'balance_after' => $wallet->balance,
                        'description' => 'شارژ کیف پول - کد پیگیری: '.($result['ref_id'] ?? 'نامشخص'),
                        'metadata' => [
                            'payment_method' => 'gateway',
                            'gateway_ref' => $result['ref_id'] ?? null,
                            'card_pan' => $result['card_pan'] ?? null,
                            'gateway' => $result['gateway'] ?? null,
                            'gateway_fee' => $result['gateway_fee'] ?? 0,
                        ],
                    ]);

                    session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

                    return redirect()->route('wallet.charge.success')
                        ->with([
                            'success' => true,
                            'amount' => $chargePending['amount'],
                            'ref_id' => $result['ref_id'] ?? 'نامشخص',
                            'new_balance' => $wallet->balance,
                        ]);
                });
            }

            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::warning('⚠️ شارژ کیف پول ناموفق', [
                'user_id' => $chargePending['user_id'],
                'amount' => $chargePending['amount'],
                'message' => $result['message'] ?? 'نامشخص',
            ]);

            return redirect()->route('wallet.index')
                ->with('error', 'پرداخت ناموفق: '.($result['message'] ?? 'خطای نامشخص'));

        } catch (\Exception $e) {
            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::error('💥 خطا در callback شارژ کیف پول', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('wallet.index')
                ->with('error', 'خطا در تایید پرداخت: '.$e->getMessage());
        }
    }

    public function chargeSuccess(): View|RedirectResponse
    {
        if (! session('success')) {
            return redirect()->route('wallet.index');
        }

        $amount = session('amount');
        $refId = session('ref_id');
        $newBalance = session('new_balance');

        return view('user.wallet.charge-success', compact('amount', 'refId', 'newBalance'));
    }
}
