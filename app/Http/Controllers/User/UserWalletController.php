<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserWalletController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $recentTransactions = $wallet->transactions()
            ->with('booking')
            ->latest()
            ->limit(10)
            ->get();

        $currentMonthRefunds = $wallet->transactions()
            ->where('type', 'refund')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $currentMonthSpent = $wallet->transactions()
            ->where('type', 'payment')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return view('user.wallet.index', compact(
            'user',
            'wallet',
            'recentTransactions',
            'currentMonthRefunds',
            'currentMonthSpent'
        ));
    }

    public function transactions(Request $request)
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $query = $wallet->transactions()->with('booking');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            try {
                $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $dateFrom = str_replace($persianDigits, $englishDigits, $request->date_from);

                $dateFrom = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateFrom)
                    ->toCarbon()
                    ->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } catch (\Exception $e) {

            }
        }

        if ($request->filled('date_to')) {
            try {
                $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $dateTo = str_replace($persianDigits, $englishDigits, $request->date_to);

                $dateTo = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateTo)
                    ->toCarbon()
                    ->endOfDay();
                $query->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {

            }
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        return view('user.wallet.transactions', compact('user', 'wallet', 'transactions'));
    }

    public function showTransaction(\App\Models\UserWalletTransaction $transaction)
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $this->authorize('viewTransaction', $transaction);

        $transaction->load('booking.service', 'booking.specialist');

        return view('user.wallet.transaction-show', compact('user', 'wallet', 'transaction'));
    }

    public function showCharge()
    {
        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();

        $suggestedAmounts = [50000, 100000, 200000, 500000, 1000000];

        return view('user.wallet.charge', compact('user', 'wallet', 'suggestedAmounts'));
    }

    public function processCharge(Request $request)
    {
        try {
            $amountInput = $request->input('amount');
            $amountInput = $this->convertPersianNumbers($amountInput);
            $amountInput = preg_replace('/[^0-9]/', '', $amountInput);

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
                    'created_at' => now()->timestamp
                ]
            ]);

            $chargeId = 'CHARGE-' . $user->id . '-' . time();

            session(['wallet_charge_id' => $chargeId]);

            $result = $this->paymentService->createWalletChargePayment($user, $amount);

            if (isset($result['success']) && $result['success'] && isset($result['payment_url'])) {
                return redirect($result['payment_url']);
            }

            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::error('❌ خطا در دریافت URL درگاه', [
                'user_id' => $user->id,
                'result' => $result
            ]);

            return back()
                ->withErrors(['amount' => 'خطا در اتصال به درگاه پرداخت: ' . ($result['message'] ?? 'خطای نامشخص')])
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ خطای اعتبارسنجی شارژ', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error('💥 خطا در پردازش شارژ کیف پول', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withErrors(['amount' => 'خطا در پردازش درخواست: ' . $e->getMessage()])
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

    public function chargeCallback(Request $request)
    {
        try {
            $chargePending = session('wallet_charge_pending');

            if (!$chargePending) {
                return redirect()->route('wallet.index')
                    ->with('error', 'اطلاعات شارژ یافت نشد.');
            }

            $result = $this->paymentService->verifyWalletChargePayment($request, $chargePending['amount']);

            if ($result['success']) {
                return DB::transaction(function() use ($chargePending, $result) {
                    $user = User::findOrFail($chargePending['user_id']);
                    $wallet = $user->getOrCreateWallet();
                    $wallet->increment('balance', $chargePending['amount']);
                    $wallet->increment('total_deposited', $chargePending['amount']);
                    $wallet->transactions()->create([
                        'type' => 'deposit',
                        'amount' => $chargePending['amount'],
                        'balance_after' => $wallet->balance,
                        'description' => 'شارژ کیف پول - کد پیگیری: ' . ($result['ref_id'] ?? 'نامشخص'),
                        'metadata' => [
                            'payment_method' => 'gateway',
                            'gateway_ref' => $result['ref_id'] ?? null,
                            'card_pan' => $result['card_pan'] ?? null,
                        ]
                    ]);

                    session()->forget(['wallet_charge_pending', 'wallet_charge_id']);
                    return redirect()->route('wallet.charge.success')
                        ->with([
                            'success' => true,
                            'amount' => $chargePending['amount'],
                            'ref_id' => $result['ref_id'] ?? 'نامشخص',
                            'new_balance' => $wallet->balance
                        ]);
                });
            }

            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::warning('⚠️ شارژ کیف پول ناموفق', [
                'user_id' => $chargePending['user_id'],
                'amount' => $chargePending['amount'],
                'message' => $result['message'] ?? 'نامشخص'
            ]);

            return redirect()->route('wallet.index')
                ->with('error', 'پرداخت ناموفق: ' . ($result['message'] ?? 'خطای نامشخص'));

        } catch (\Exception $e) {
            session()->forget(['wallet_charge_pending', 'wallet_charge_id']);

            Log::error('💥 خطا در callback شارژ کیف پول', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('wallet.index')
                ->with('error', 'خطا در تایید پرداخت: ' . $e->getMessage());
        }
    }

    public function chargeSuccess()
    {
        if (!session('success')) {
            return redirect()->route('wallet.index');
        }

        $amount = session('amount');
        $refId = session('ref_id');
        $newBalance = session('new_balance');

        return view('user.wallet.charge-success', compact('amount', 'refId', 'newBalance'));
    }
}
