<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserWalletController extends Controller
{

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
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);

        return view('user.wallet.transactions', compact('user', 'wallet', 'transactions'));
    }
}
