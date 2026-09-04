<?php

namespace App\Http\Controllers\Retailer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);
        $availableBalance = $walletService->getAvailableBalance($wallet);

        $stats = [
            'wallet_balance' => $wallet->balance,
            'available_balance' => $availableBalance,
            'locked_balance' => $wallet->balance - $availableBalance,

            'today_transactions' => Transaction::where('user_id', $user->id)->whereDate('created_at', today())->count(),
            'today_success' => Transaction::where('user_id', $user->id)->whereDate('created_at', today())->where('status', 'success')->count(),
            'today_failed' => Transaction::where('user_id', $user->id)->whereDate('created_at', today())->where('status', 'failed')->count(),

            'this_month_transactions' => Transaction::where('user_id', $user->id)->whereMonth('created_at', now()->month)->count(),
            'this_month_success' => Transaction::where('user_id', $user->id)->whereMonth('created_at', now()->month)->where('status', 'success')->count(),
            'this_month_volume' => Transaction::where('user_id', $user->id)->whereMonth('created_at', now()->month)->where('status', 'success')->sum('amount'),

            'total_transactions' => Transaction::where('user_id', $user->id)->count(),
            'total_success' => Transaction::where('user_id', $user->id)->where('status', 'success')->count(),
            'success_rate' => Transaction::where('user_id', $user->id)->whereIn('status', ['success', 'failed', 'cancelled'])->count() > 0
                ? round((Transaction::where('user_id', $user->id)->where('status', 'success')->count() / Transaction::where('user_id', $user->id)->whereIn('status', ['success', 'failed', 'cancelled'])->count()) * 100, 1)
                : 0,

            'low_balance' => $availableBalance < config('platform.pricing.low_balance_threshold', 500),
        ];

        // Recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['operator', 'country'])
            ->latest()
            ->limit(10)
            ->get();

        // Daily chart (last 7 days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartData[] = [
                'date' => $date,
                'count' => Transaction::where('user_id', $user->id)->whereDate('created_at', $date)->count(),
                'amount' => Transaction::where('user_id', $user->id)->whereDate('created_at', $date)->where('status', 'success')->sum('amount'),
            ];
        }

        return Inertia::render('Retailer/Dashboard', compact('stats', 'recentTransactions', 'chartData'));
    }
}
