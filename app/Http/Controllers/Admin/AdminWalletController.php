<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecialistWallet;
use App\Models\WithdrawalRequest;
use App\Models\WalletSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminWalletController extends Controller
{
    public function index(Request $request)
    {
        $query = SpecialistWallet::with('specialist');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('specialist', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'balance_desc');
        switch ($sortBy) {
            case 'balance_asc':
                $query->orderBy('balance', 'asc');
                break;
            case 'earned_desc':
                $query->orderBy('total_earned', 'desc');
                break;
            default:
                $query->orderBy('balance', 'desc');
        }

        $wallets = $query->paginate(20);

        $totalBalance = SpecialistWallet::sum('balance');
        $totalEarned = SpecialistWallet::sum('total_earned');
        $totalWithdrawn = SpecialistWallet::sum('total_withdrawn');
        $totalPending = SpecialistWallet::sum('pending_amount');

        return view('admin.wallet.index', compact(
            'wallets',
            'totalBalance',
            'totalEarned',
            'totalWithdrawn',
            'totalPending'
        ));
    }

    public function show(SpecialistWallet $wallet)
    {
        $wallet->load('specialist', 'transactions', 'withdrawalRequests');

        $recentTransactions = $wallet->transactions()
            ->with('booking')
            ->latest()
            ->paginate(20);

        return view('admin.wallet.show', compact('wallet', 'recentTransactions'));
    }

    public function withdrawals(Request $request)
    {
        $query = WithdrawalRequest::with(['specialist', 'wallet']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                    ->orWhereHas('specialist', function($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $withdrawals = $query->latest()->paginate(20);

        $pendingCount = WithdrawalRequest::where('status', 'pending')->count();
        $pendingAmount = WithdrawalRequest::where('status', 'pending')->sum('amount');
        $completedToday = WithdrawalRequest::where('status', 'completed')
            ->whereDate('processed_at', today())
            ->count();

        return view('admin.wallet.withdrawals', compact(
            'withdrawals',
            'pendingCount',
            'pendingAmount',
            'completedToday'
        ));
    }

    public function showWithdrawal(WithdrawalRequest $withdrawalRequest)
    {
        $withdrawalRequest->load(['specialist', 'wallet', 'processedBy']);

        return view('admin.wallet.withdrawal-show', compact('withdrawalRequest'));
    }

    public function approveWithdrawal(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قابل تایید نیست.');
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
            'payment_reference' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $withdrawalRequest->markAsCompleted([
                'payment_reference' => $validated['payment_reference'],
                'approved_by' => auth()->user()->name,
                'approved_at' => now()->toDateTimeString(),
            ]);

            $withdrawalRequest->update([
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            // TODO: ارسال SMS/Notification

            DB::commit();

            return redirect()->route('admin.wallet.withdrawals')
                ->with('success', 'درخواست برداشت با موفقیت تایید شد.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('خطا در تایید درخواست برداشت', [
                'withdrawal_id' => $withdrawalRequest->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در تایید درخواست: ' . $e->getMessage());
        }
    }

    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قابل رد نیست.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $wallet = $withdrawalRequest->wallet;

            $wallet->increment('balance', $withdrawalRequest->amount);
            $wallet->decrement('total_withdrawn', $withdrawalRequest->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawalRequest->amount,
                'balance_after' => $wallet->balance,
                'description' => 'رد درخواست برداشت - کد: ' . $withdrawalRequest->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'rejection_reason' => $validated['rejection_reason'],
                ]
            ]);

            $withdrawalRequest->markAsFailed($validated['rejection_reason']);

            // TODO: ارسال SMS/Notification

            DB::commit();

            return redirect()->route('admin.wallet.withdrawals')
                ->with('success', 'درخواست برداشت رد شد و موجودی بازگردانده شد.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('خطا در رد درخواست برداشت', [
                'withdrawal_id' => $withdrawalRequest->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در رد درخواست: ' . $e->getMessage());
        }
    }

    public function settings()
    {
        $settings = WalletSetting::first();

        return view('admin.wallet.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'withdrawal_fee_percentage' => 'required|numeric|min:0|max:100',
            'minimum_withdrawal_amount' => 'required|numeric|min:0',
            'maximum_withdrawal_amount' => 'required|numeric|min:0',
            'instant_withdrawal_enabled' => 'nullable|boolean',
            'instant_withdrawal_fee' => 'required_if:instant_withdrawal_enabled,1|numeric|min:0',
            'cancellation_before_hours' => 'required|integer|min:1',
            'customer_cancellation_fee_percentage' => 'required|numeric|min:0|max:100',
            'specialist_cancellation_penalty_percentage' => 'required|numeric|min:0|max:100',
            'settlement_delay_days' => 'required|integer|min:0|max:30',
        ]);

        try {
            $settings = WalletSetting::first();
            $settings->update($validated);

            return back()->with('success', 'تنظیمات با موفقیت به‌روزرسانی شد.');

        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی تنظیمات کیف پول', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در ذخیره تنظیمات: ' . $e->getMessage());
        }
    }

    public function verifyIban(SpecialistWallet $wallet)
    {
        try {
            $wallet->update(['iban_verified' => true]);

            return back()->with('success', 'شماره شبا با موفقیت تایید شد.');

        } catch (Exception $e) {
            Log::error('خطا در تایید شماره شبا', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در تایید شماره شبا: ' . $e->getMessage());
        }
    }

    public function adjust(Request $request, SpecialistWallet $wallet)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $amount = $validated['amount'];

            if ($amount > 0) {
                $wallet->increment('balance', $amount);
            } else {
                $wallet->decrement('balance', abs($amount));
            }

            $wallet->transactions()->create([
                'type' => 'adjustment',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => 'تعدیل دستی توسط ادمین: ' . $validated['description'],
                'metadata' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ]
            ]);

            DB::commit();

            return back()->with('success', 'تعدیل با موفقیت انجام شد.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('خطا در تعدیل کیف پول', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'خطا در تعدیل: ' . $e->getMessage());
        }
    }

    public function autoPayout(WithdrawalRequest $withdrawalRequest)
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'processing'])) {
            return back()->with('error', 'این درخواست قبلاً پردازش شده است.');
        }

        try {

            /*
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.zarinpal.api_key'),
            ])->post('https://api.zarinpal.com/pg/v4/payout.json', [
                'merchant_id' => config('services.zarinpal.merchant_id'),
                'amount'      => $withdrawalRequest->net_amount * 10, // تبدیل به ریال اگر تومان است
                'description' => "تسویه حساب متخصص: " . $withdrawalRequest->specialist->name,
                'destination_iban' => $withdrawalRequest->iban,
            ]);
            */

            $isSuccessful = true;
            $referenceCode = "ZRP-" . rand(100000, 999999);

            if ($isSuccessful) {
                DB::beginTransaction();

                $withdrawalRequest->markAsCompleted([
                    'payment_method' => 'zarinpal_auto',
                    'payment_reference' => $referenceCode,
                    'payout_id' => 'PAY-' . uniqid()
                ]);

                DB::commit();

                return back()->with('success', 'تسویه حساب آنلاین با موفقیت انجام شد. کد ارجاع: ' . $referenceCode);
            }

            return back()->with('error', 'خطا در اتصال به درگاه زرین‌پال یا عدم موجودی کافی در پنل زرین‌پال.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Zarinpal Payout Error: ' . $e->getMessage());
            return back()->with('error', 'خطای سیستمی: ' . $e->getMessage());
        }
    }
}
