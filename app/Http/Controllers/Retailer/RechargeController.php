<?php

namespace App\Http\Controllers\Retailer;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Jobs\ProcessRechargeJob;
use App\Models\Operator;
use App\Models\Country;
use App\Services\DingConnectService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RechargeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);
        $availableBalance = $walletService->getAvailableBalance($wallet);

        $countries = Country::where('is_active', true)->get(['id', 'name', 'iso_code', 'calling_code']);

        return Inertia::render('Retailer/Recharge/New', compact('wallet', 'availableBalance', 'countries'));
    }

    public function getOperators(Request $request)
    {
        $countryId = $request->input('country_id');

        $operators = Operator::where('is_active', true)
            ->when($countryId, fn($q) => $q->where('country_id', $countryId))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo_url']);

        return response()->json(['operators' => $operators]);
    }

    public function getPricing(Request $request, DingConnectService $dingService)
    {
        $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'amount' => ['required', 'numeric', 'min:' . config('platform.pricing.min_recharge', 10), 'max:' . config('platform.pricing.max_recharge', 10000)],
        ]);

        $operator = Operator::findOrFail($request->operator_id);

        // Pass-through pricing: retailer pays the same as recharge amount
        // In production, get actual DingConnect cost from API
        $dingCost = (float) $request->amount;
        $retailerCharged = $dingCost; // No markup - retailer pays same as Ding cost

        return response()->json([
            'ding_cost' => $dingCost,
            'retailer_charged' => $retailerCharged,
        ]);
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'mobile_number' => ['required', 'string', 'min:10', 'max:15'],
            'operator_id' => 'required|exists:operators,id',
            'country_id' => 'required|exists:countries,id',
            'amount' => ['required', 'numeric', 'min:' . config('platform.pricing.min_recharge', 10), 'max:' . config('platform.pricing.max_recharge', 10000)],
        ]);

        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);

        $operator = Operator::findOrFail($request->operator_id);
        $country = Country::findOrFail($request->country_id);

        // Pass-through pricing
        $retailerCharged = (float) $request->amount;
        $dingCost = $retailerCharged;

        // Check balance
        $availableBalance = $walletService->getAvailableBalance($wallet);
        if ($availableBalance < $retailerCharged) {
            return back()->with('error', 'Insufficient wallet balance. Please top up your wallet.');
        }

        // Generate order reference
        $orderReference = 'ORD-' . strtoupper(Str::random(12));
        $receiptNumber = 'TXN-' . now()->format('Ymd') . '-' . str_pad(Transaction::whereDate('created_at', today())->count() + 1, 5, '0', STR_PAD_LEFT);

        // Create transaction
        $transaction = DB::transaction(function () use ($user, $operator, $country, $request, $retailerCharged, $dingCost, $orderReference, $receiptNumber) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'mobile_number' => preg_replace('/[^0-9]/', '', $request->mobile_number),
                'operator_id' => $operator->id,
                'country_id' => $country->id,
                'amount' => $request->amount,
                'ding_order_reference' => $orderReference,
                'receipt_number' => $receiptNumber,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Hold the amount in wallet
            $walletService = app(WalletService::class);
            $wallet = $walletService->getWallet($user);
            $walletService->hold($wallet, $retailerCharged, 'recharge', $transaction->id, "Hold for recharge - {$request->mobile_number}");

            return $transaction;
        });

        // Dispatch job to process with DingConnect
        ProcessRechargeJob::dispatch($transaction);

        // Broadcast processing event
        broadcast(new \App\Events\RechargeProcessing($transaction));

        return redirect()->route('retailer.transactions.show', $transaction->id)
            ->with('success', 'Recharge request submitted! Processing...');
    }
}
