<?php

namespace App\Http\Controllers\Retailer;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Models\WalletTopup;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);
        $availableBalance = $walletService->getAvailableBalance($wallet);

        $topups = WalletTopup::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Retailer/Wallet/Index', compact('wallet', 'availableBalance', 'topups'));
    }

    public function topUpPage(Request $request)
    {
        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);
        $availableBalance = $walletService->getAvailableBalance($wallet);

        return Inertia::render('Retailer/Wallet/TopUp', compact('wallet', 'availableBalance'));
    }

    public function initiateTopUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . config('platform.pricing.min_recharge', 10) . '|max:' . config('platform.pricing.max_recharge', 10000),
        ]);

        $paymentService = app(PaymentService::class);
        $result = $paymentService->createOrder($request->amount);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($request->user());

        $feePercentage = config('platform.wallet.load_fee_percentage', 0);
        $feeFlat = config('platform.wallet.load_fee_flat', 0);
        $feeAmount = ($request->amount * $feePercentage / 100) + $feeFlat;

        WalletTopup::create([
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'currency' => config('platform.wallet.currency', 'GBP'),
            'fee_amount' => 0,
            'fee_percentage' => 0,
            'total_charged' => $request->amount,
            'payment_method' => 'wallet',
            'payment_gateway' => 'razorpay',
            'gateway_order_id' => $result['order_id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $result['order_id'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'razorpay_key' => config('platform.payment.razorpay.key_id'),
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $paymentService = app(PaymentService::class);

        if (!$paymentService->verifyPayment($request->only('razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'))) {
            return response()->json(['success' => false, 'error' => 'Payment verification failed!'], 400);
        }

        $topup = WalletTopup::where('gateway_order_id', $request->razorpay_order_id)->first();

        if (!$topup || $topup->status === 'completed') {
            return response()->json(['success' => false, 'error' => 'Invalid or already processed payment.'], 400);
        }

        $topup->update([
            'status' => 'completed',
            'gateway_transaction_id' => $request->razorpay_payment_id,
        ]);

        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($topup->user);
        $walletService->credit($wallet, $topup->amount, 'topup', $topup->id, 'Wallet top-up via Razorpay');

        broadcast(new \App\Events\WalletCredited($topup->user, $topup->amount, $wallet->balance));

        return response()->json(['success' => true, 'message' => 'Wallet credited successfully!']);
    }

    public function ledger(Request $request)
    {
        $user = $request->user();
        $ledgers = WalletLedger::whereHas('wallet', fn($q) => $q->where('user_id', $user->id))
            ->with('wallet')
            ->latest()
            ->paginate(50);

        return Inertia::render('Retailer/Wallet/Ledger', compact('ledgers'));
    }
}
