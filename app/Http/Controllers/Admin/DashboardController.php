<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\AdminEarning;
use App\Services\DingConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_retailers' => User::where('role', 'retailer')->count(),
            'active_retailers' => User::where('role', 'retailer')->where('is_active', true)->count(),
            'pending_kyc' => User::where('role', 'retailer')->where('kyc_status', 'pending')->count(),

            'today_transactions' => Transaction::whereDate('created_at', today())->count(),
            'today_success' => Transaction::whereDate('created_at', today())->where('status', 'success')->count(),
            'today_failed' => Transaction::whereDate('created_at', today())->where('status', 'failed')->count(),
            'today_amount' => Transaction::whereDate('created_at', today())->where('status', 'success')->sum('amount'),
            'today_commission' => Transaction::whereDate('created_at', today())->where('status', 'success')->sum('commission_amount'),

            'total_transactions' => Transaction::count(),
            'total_success' => Transaction::where('status', 'success')->count(),
            'total_revenue' => Transaction::where('status', 'success')->sum('commission_amount'),

            'success_rate' => Transaction::where('status', 'success')->count() > 0
                ? round((Transaction::where('status', 'success')->count() / Transaction::whereIn('status', ['success', 'failed', 'cancelled'])->count()) * 100, 1)
                : 0,

            'ding_balance' => app(DingConnectService::class)->getBalance(),
        ];

        // Top retailers this month
        $topRetailers = User::where('role', 'retailer')
            ->whereHas('transactions', fn($q) => $q->whereMonth('created_at', now()->month))
            ->withCount(['transactions as month_transactions' => fn($q) => $q->whereMonth('created_at', now()->month)])
            ->orderByDesc('month_transactions')
            ->limit(10)
            ->get(['id', 'name', 'shop_name', 'email', 'phone']);

        // Recent transactions
        $recentTransactions = Transaction::with(['user', 'operator', 'country'])
            ->latest()
            ->limit(10)
            ->get();

        // Daily chart data (last 7 days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartData[] = [
                'date' => $date,
                'transactions' => Transaction::whereDate('created_at', $date)->count(),
                'revenue' => Transaction::whereDate('created_at', $date)->where('status', 'success')->sum('commission_amount'),
            ];
        }

        return Inertia::render('Admin/Dashboard', compact('stats', 'topRetailers', 'recentTransactions', 'chartData'));
    }
}
