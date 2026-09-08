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

        $countries = Country::where('is_active', true)->get(['id', 'name', 'iso_code', 'calling_code', 'flag_emoji']);

        return Inertia::render('Retailer/Recharge/New', compact('wallet', 'availableBalance', 'countries'));
    }

    public function getOperators(Request $request)
    {
        $countryId = $request->input('country_id');

        $operators = Operator::where('is_active', true)
            ->when($countryId, fn($q) => $q->where('country_id', $countryId))
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'provider_code', 'logo_url', 'country_id']);

        return response()->json(['operators' => $operators]);
    }

    public function getProducts(Request $request, DingConnectService $dingService)
    {
        $request->validate([
            'provider_code' => 'required|string',
            'country_iso' => 'required|string|size:2',
        ]);

        $result = $dingService->getProducts($request->country_iso, $request->provider_code);

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['error']], 400);
        }

        $products = collect($result['data']['Items'] ?? [])->map(function ($item) {
            return [
                'sku_code' => $item['SkuCode'],
                'provider_code' => $item['ProviderCode'],
                'display_text' => $item['DefaultDisplayText'] ?? '',
                'receive_value' => (float) ($item['Maximum']['ReceiveValue'] ?? 0),
                'receive_currency' => $item['Maximum']['ReceiveCurrencyIso'] ?? '',
                'send_value' => (float) ($item['Maximum']['SendValue'] ?? 0),
                'send_currency' => $item['Maximum']['SendCurrencyIso'] ?? 'GBP',
                'commission_rate' => (float) ($item['CommissionRate'] ?? 0),
                'validity_period' => $item['ValidityPeriodIso'] ?? '',
                'benefits' => $item['Benefits'] ?? [],
                'payment_types' => $item['PaymentTypes'] ?? [],
                'processing_mode' => $item['ProcessingMode'] ?? 'Instant',
                'region_code' => $item['RegionCode'] ?? '',
            ];
        })->values();

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function getPricing(Request $request, DingConnectService $dingService)
    {
        $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'amount' => ['required', 'numeric', 'min:' . config('platform.pricing.min_recharge', 10), 'max:' . config('platform.pricing.max_recharge', 10000)],
        ]);

        // Pass-through pricing
        $dingCost = (float) $request->amount;
        $retailerCharged = $dingCost;

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
            'sku_code' => ['required', 'string'],
            'send_value' => ['required', 'numeric', 'min:' . config('platform.pricing.min_recharge', 10), 'max:' . config('platform.pricing.max_recharge', 10000)],
            'receive_value' => ['required', 'numeric'],
            'send_currency' => ['nullable', 'string', 'size:3'],
            'receive_currency' => ['nullable', 'string', 'size:3'],
            'display_text' => ['nullable', 'string'],
            'validity_period' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $walletService = app(WalletService::class);
        $wallet = $walletService->getWallet($user);

        $operator = Operator::findOrFail($request->operator_id);
        $country = Country::findOrFail($request->country_id);

        $retailerCharged = (float) $request->send_value;
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
                'amount' => $request->send_value,
                'currency' => $request->receive_currency ?? 'GBP',
                'sku_code' => $request->sku_code,
                'send_value' => $request->send_value,
                'receive_value' => $request->receive_value,
                'send_currency' => $request->send_currency ?? 'GBP',
                'receive_currency' => $request->receive_currency ?? 'GBP',
                'display_text' => $request->display_text,
                'validity_period' => $request->validity_period,
                'benefits' => json_encode($request->benefits ?? []),
                'ding_order_reference' => $orderReference,
                'receipt_number' => $receiptNumber,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

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
