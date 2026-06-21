<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SpecialistWalletController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $settings = WalletSetting::get();

        $recentTransactions = $wallet->transactions()
            ->latest()
            ->limit(10)
            ->get();

        $withdrawalRequests = $wallet->withdrawalRequests()
            ->latest()
            ->paginate(10);

        $currentMonthIncome = $wallet->transactions()
            ->where('type', 'income')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $currentMonthWithdrawals = $wallet->transactions()
            ->where('type', 'withdrawal')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return view('specialist.wallet.index', compact(
            'specialist',
            'wallet',
            'settings',
            'recentTransactions',
            'withdrawalRequests',
            'currentMonthIncome',
            'currentMonthWithdrawals'
        ));
    }

    public function editIban()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();

        return view('specialist.wallet.edit-iban', compact('specialist', 'wallet'));
    }

    public function updateIban(Request $request)
    {
        $validated = $request->validate([
            'iban' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $digits = str_replace(' ', '', $value);
                    if (!preg_match('/^[0-9]{24}$/', $digits)) {
                        $fail('لطفاً ۲۴ رقم شماره شبا را بدون IR وارد کنید.');
                    }
                },
            ],
            'account_holder_name' => 'required|string|min:3|max:255',
            'bank_name' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            $specialist = \App\Models\Specialist::where('phone', $user->phone)->first();
            $wallet = $specialist->getOrCreateWallet();

            $fullIban = 'IR' . str_replace(' ', '', $request->iban);

            $wallet->update([
                'iban' => $fullIban,
                'account_holder_name' => $request->account_holder_name,
                'bank_name' => $request->bank_name,
                'iban_verified' => false,
            ]);

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'اطلاعات بانکی با موفقیت ثبت شد.');

        } catch (Exception $e) {
            return back()->with('error', 'خطایی رخ داد.');
        }
    }

    public function createWithdrawal()
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();
        $settings = WalletSetting::get();

        if (!$wallet->iban) {
            return redirect()->route('specialist.wallet.edit-iban')
                ->with('error', 'لطفاً ابتدا شماره شبا خود را ثبت کنید.');
        }

        return view('specialist.wallet.create-withdrawal', compact('specialist', 'wallet', 'settings'));
    }

    public function storeWithdrawal(Request $request)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            abort(404, 'رکورد متخصص یافت نشد');
        }

        $wallet = $specialist->getOrCreateWallet();
        $settings = WalletSetting::get();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:' . $settings->minimum_withdrawal_amount,
            'method' => 'required|in:instant,iban',
        ], [
            'amount.required' => 'مبلغ الزامی است.',
            'amount.numeric' => 'مبلغ باید عدد باشد.',
            'amount.min' => 'حداقل مبلغ برداشت ' . number_format($settings->minimum_withdrawal_amount) . ' تومان است.',
        ]);

        try {
            DB::beginTransaction();

            $amount = $validated['amount'];
            $method = $validated['method'];

            $canWithdraw = $wallet->canWithdraw($amount);
            if (!$canWithdraw['success']) {
                return back()->with('error', $canWithdraw['message']);
            }

            $feeCalculation = $wallet->calculateWithdrawalFee($amount, $method);

            $withdrawalRequest = WithdrawalRequest::create([
                'wallet_id' => $wallet->id,
                'specialist_id' => $specialist->id,
                'amount' => $amount,
                'fee' => $feeCalculation['fee'],
                'net_amount' => $feeCalculation['net_amount'],
                'method' => $method,
                'iban' => $wallet->iban,
                'account_holder_name' => $wallet->account_holder_name,
                'status' => 'pending',
            ]);

            $wallet->recordWithdrawal($amount, $withdrawalRequest->id);

            DB::commit();

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'درخواست برداشت با موفقیت ثبت شد. کد پیگیری: ' . $withdrawalRequest->reference_code);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('خطا در ثبت درخواست برداشت', [
                'specialist_id' => $specialist->id,
                'amount' => $amount ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در ثبت درخواست: ' . $e->getMessage());
        }
    }

    public function cancelWithdrawal(WithdrawalRequest $withdrawalRequest)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist || $withdrawalRequest->specialist_id !== $specialist->id) {
            abort(403, 'شما مجاز به دسترسی به این درخواست نیستید.');
        }

        if (!$withdrawalRequest->canBeCancelled()) {
            return back()->with('error', 'این درخواست قابل لغو نیست.');
        }

        try {
            DB::beginTransaction();

            $wallet = $specialist->wallet;

            $wallet->increment('balance', $withdrawalRequest->amount);
            $wallet->decrement('total_withdrawn', $withdrawalRequest->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawalRequest->amount,
                'balance_after' => $wallet->balance,
                'description' => 'لغو درخواست برداشت - کد: ' . $withdrawalRequest->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $withdrawalRequest->id
                ]
            ]);

            $withdrawalRequest->update(['status' => 'cancelled']);

            DB::commit();

            return redirect()->route('specialist.wallet.index')
                ->with('success', 'درخواست برداشت با موفقیت لغو شد و موجودی بازگردانده شد.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('خطا در لغو درخواست برداشت', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در لغو درخواست: ' . $e->getMessage());
        }
    }

    public function transactions(Request $request)
    {
        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return view('specialist.profile-not-found');
        }

        $wallet = $specialist->getOrCreateWallet();

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
                Log::warning('خطا در تبدیل تاریخ از: ' . $e->getMessage());
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
                Log::warning('خطا در تبدیل تاریخ تا: ' . $e->getMessage());
            }
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        return view('specialist.wallet.transactions', compact('specialist', 'wallet', 'transactions'));
    }

    public function calculateFee(Request $request)
    {
        $amount = $request->input('amount', 0);
        $method = $request->input('method', 'iban');

        $user = auth()->user();
        $specialist = Specialist::where('phone', $user->phone)->first();

        if (!$specialist) {
            return response()->json(['error' => 'متخصص یافت نشد'], 404);
        }

        $wallet = $specialist->getOrCreateWallet();
        $calculation = $wallet->calculateWithdrawalFee($amount, $method);

        return response()->json([
            'gross_amount' => number_format($calculation['gross_amount']),
            'fee' => number_format($calculation['fee']),
            'net_amount' => number_format($calculation['net_amount']),
        ]);
    }
}
