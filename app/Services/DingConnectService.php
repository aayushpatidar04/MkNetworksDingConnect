<?php

namespace App\Services;

use App\Models\DingCallback;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Events\RechargeSuccess;
use App\Events\RechargeFailed;

class DingConnectService
{
    protected PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::timeout(config('platform.dingconnect.timeout'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('platform.dingconnect.api_key'),
                'CustomerID' => config('platform.dingconnect.customer_id'),
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Send a top-up request to DingConnect
     */
    public function sendTopUp(string $mobileNumber, string $operatorId, string $countryCode, float $amount): array
    {
        try {
            $response = $this->http->post(config('platform.dingconnect.base_url') . '/api/v1/transfer', [
                'MobileNumber' => $mobileNumber,
                'OperatorID' => $operatorId,
                'CountryCode' => $countryCode,
                'Amount' => $amount,
                'Currency' => config('platform.wallet.currency'),
                'CallBackURL' => config('platform.dingconnect.callback_url'),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('Message') ?? 'Unknown error',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('DingConnect API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check transaction status from DingConnect
     */
    public function checkStatus(string $dingTransactionId): array
    {
        try {
            $response = $this->http->get(config('platform.dingconnect.base_url') . '/api/v1/transfer/' . $dingTransactionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('Message') ?? 'Unknown error',
            ];

        } catch (\Exception $e) {
            Log::error('DingConnect Status Check Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch operator list from DingConnect
     */
    public function getOperators(string $countryCode = null): array
    {
        try {
            $url = config('platform.dingconnect.base_url') . '/api/v1/operator/list';
            if ($countryCode) {
                $url .= '?CountryCode=' . $countryCode;
            }

            $response = $this->http->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('Operators') ?? [],
                ];
            }

            return ['success' => false, 'error' => 'Failed to fetch operators'];

        } catch (\Exception $e) {
            Log::error('DingConnect Operators Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch country list from DingConnect
     */
    public function getCountries(): array
    {
        try {
            $response = $this->http->get(config('platform.dingconnect.base_url') . '/api/v1/country/list');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('Countries') ?? [],
                ];
            }

            return ['success' => false, 'error' => 'Failed to fetch countries'];

        } catch (\Exception $e) {
            Log::error('DingConnect Countries Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check DingConnect account balance
     */
    public function getBalance(): array
    {
        try {
            $response = $this->http->get(config('platform.dingconnect.base_url') . '/api/v1/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json('Balance'),
                    'currency' => $response->json('Currency'),
                ];
            }

            return ['success' => false, 'error' => 'Failed to fetch balance'];

        } catch (\Exception $e) {
            Log::error('DingConnect Balance Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process incoming webhook callback from DingConnect
     */
    public function processCallback(array $payload): Transaction
    {
        $dingTransactionId = $payload['TransferID'] ?? $payload['TransferId'] ?? null;

        if (!$dingTransactionId) {
            throw new \InvalidArgumentException('Missing TransferID in callback');
        }

        // Find the transaction
        $transaction = Transaction::where('ding_transaction_id', $dingTransactionId)->first();

        if (!$transaction) {
            Log::warning("Ding callback for unknown transaction: {$dingTransactionId}");
            throw new \Exception("Transaction not found: {$dingTransactionId}");
        }

        // Log the callback
        DingCallback::create([
            'transaction_id' => $transaction->id,
            'ding_transaction_id' => $dingTransactionId,
            'payload' => $payload,
            'status' => 'processed',
            'received_at' => now(),
            'processed_at' => now(),
        ]);

        $status = $payload['Status'] ?? 'Unknown';

        // Map DingConnect status to our status
        $statusMap = [
            'Successful' => 'success',
            'Success' => 'success',
            'Failed' => 'failed',
            'Failure' => 'failed',
            'Pending' => 'processing',
            'Cancelled' => 'cancelled',
        ];

        $newStatus = $statusMap[$status] ?? 'failed';

        // Handle based on status
        match ($newStatus) {
            'success' => $this->handleSuccess($transaction, $payload),
            'failed' => $this->handleFailure($transaction, $payload),
            default => $transaction->update(['status' => 'processing']),
        };

        $transaction->update([
            'status' => $newStatus,
            'callback_received' => true,
            'callback_received_at' => now(),
            'ding_response' => $payload,
        ]);

        return $transaction->fresh();
    }

    protected function handleSuccess(Transaction $transaction, array $payload): void
    {
        // Convert hold to actual debit
        $wallet = $transaction->user->wallet;

        DB::transaction(function () use ($transaction, $wallet) {
            // Remove the hold and apply actual debit
            $holdLedger = WalletLedger::where('wallet_id', $wallet->id)
                ->where('reference_id', $transaction->id)
                ->where('type', 'hold')
                ->first();

            if ($holdLedger) {
                // Add back the held amount (to undo the hold)
                $wallet->increment('balance', $transaction->retailer_charged);
                $wallet->refresh();

                // Now debit the actual amount
                $wallet->decrement('balance', $transaction->retailer_charged);
                $wallet->refresh();

                // Log the final debit
                WalletLedger::create([
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'debit',
                    'amount' => $transaction->retailer_charged,
                    'balance_before' => $holdLedger->balance_after,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'recharge',
                    'reference_id' => $transaction->id,
                    'description' => "Recharge successful - {$transaction->mobile_number}",
                    'created_at' => now(),
                ]);
            }


            // Fire events for notifications
            event(new RechargeSuccess($transaction));
        });
    }

    protected function handleFailure(Transaction $transaction, array $payload): void
    {
        DB::transaction(function () use ($transaction) {
            // Release the hold - add back to balance
            $wallet = $transaction->user->wallet;

            $holdLedger = WalletLedger::where('wallet_id', $wallet->id)
                ->where('reference_id', $transaction->id)
                ->where('type', 'hold')
                ->first();

            if ($holdLedger) {
                $wallet->increment('balance', $transaction->retailer_charged);

                WalletLedger::create([
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'refund',
                    'amount' => $transaction->retailer_charged,
                    'balance_before' => $holdLedger->balance_after,
                    'balance_after' => $wallet->balance,
                    'reference_type' => 'recharge',
                    'reference_id' => $transaction->id,
                    'description' => "Recharge failed - amount refunded - {$transaction->mobile_number}",
                    'created_at' => now(),
                ]);
            }

            $transaction->update([
                'failure_reason' => $payload['FailureReason'] ?? 'Transaction failed',
            ]);

            event(new RechargeFailed($transaction));
        });
    }
}
