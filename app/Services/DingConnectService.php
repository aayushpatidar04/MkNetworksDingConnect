<?php

namespace App\Services;

use App\Models\DingCallback;
use App\Models\Operator;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Models\Country;
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
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Send a top-up request to DingConnect
     */
    public function sendTopUp(string $mobileNumber, string $operatorId, string $countryCode, float $amount): array
    {
        try {
            $response = $this->http->post(config('platform.dingconnect.base_url') . '/api/v1/Transfer', [
                'MobileNumber' => $mobileNumber,
                'OperatorID' => $operatorId,
                'CountryCode' => $countryCode,
                'Amount' => $amount,
                'Currency' => config('platform.wallet.currency'),
                'CallBackURL' => config('platform.dingconnect.callback_url'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['ResultCode'] ?? 0) == 1) {
                    return ['success' => true, 'data' => $data];
                }
                return ['success' => false, 'error' => $data['ErrorCodes'][0] ?? $data['Message'] ?? 'Transfer failed', 'raw' => $data];
            }

            return ['success' => false, 'error' => $response->json('Message') ?? 'Unknown error', 'status_code' => $response->status()];

        } catch (\Exception $e) {
            Log::error('DingConnect API Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'API connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check transaction status from DingConnect
     */
    public function checkStatus(string $dingTransactionId): array
    {
        try {
            $response = $this->http->get(config('platform.dingconnect.base_url') . '/api/v1/Transfer/' . $dingTransactionId);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json('Message') ?? 'Unknown error'];

        } catch (\Exception $e) {
            Log::error('DingConnect Status Check Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch providers (operators) from DingConnect using GetProviders endpoint
     */
    public function getProviders(?string $countryIso = null, ?string $providerCodes = null): array
    {
        try {
            $url = config('platform.dingconnect.base_url') . '/api/v1/GetProviders';

            $params = [];
            if ($countryIso) {
                $params['countryIsos'] = $countryIso;
            }
            if ($providerCodes) {
                $params['providerCodes'] = $providerCodes;
            }

            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $response = $this->http->get($url);
            Log::info($response);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['ResultCode'] ?? 0) == 1) {
                    $items = $data['Items'] ?? [];
                    return ['success' => true, 'data' => $items];
                }
                return ['success' => false, 'error' => $data['ErrorCodes'][0] ?? 'Failed to fetch providers'];
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('DingConnect Providers Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch country list from DingConnect AND save to DB
     */
    public function getCountries(): array
    {
        try {
            $url = config('platform.dingconnect.base_url') . '/api/v1/GetCountries';
            Log::info('DingConnect: Fetching countries', ['url' => $url]);

            $response = $this->http->get($url);

            Log::info('DingConnect: Countries response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['ResultCode'] ?? 0) == 1) {
                    $items = $data['Items'] ?? [];
                    $synced = 0;

                    foreach ($items as $country) {
                        $isoCode = strtoupper($country['CountryIso'] ?? '');
                        $countryName = $country['CountryName'] ?? '';

                        if ($isoCode) {
                            Country::updateOrCreate(
                                ['iso_code' => $isoCode],
                                [
                                    'name' => $countryName,
                                    'iso_code_3' => $isoCode,
                                    'flag_emoji' => $this->getFlagEmoji($isoCode),
                                    'is_active' => true,
                                ]
                            );
                            $synced++;
                        }
                    }

                    Log::info('DingConnect: Countries synced to DB', ['count' => $synced]);
                    return [
                        'success' => true,
                        'data' => $items,
                        'synced' => $synced,
                    ];
                }

                return ['success' => false, 'error' => 'API returned error: ' . ($data['ErrorCodes'][0] ?? 'Unknown')];
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status()];

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
            $response = $this->http->get(config('platform.dingconnect.base_url') . '/api/v1/Balance');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'balance' => $data['Balance'] ?? null,
                    'currency' => $data['Currency'] ?? null,
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

        $transaction = Transaction::where('ding_transaction_id', $dingTransactionId)->first();

        if (!$transaction) {
            Log::warning("Ding callback for unknown transaction: {$dingTransactionId}");
            throw new \Exception("Transaction not found: {$dingTransactionId}");
        }

        DingCallback::create([
            'transaction_id' => $transaction->id,
            'ding_transaction_id' => $dingTransactionId,
            'payload' => $payload,
            'status' => 'processed',
            'received_at' => now(),
            'processed_at' => now(),
        ]);

        $status = $payload['Status'] ?? 'Unknown';

        $statusMap = [
            'Successful' => 'success',
            'Success' => 'success',
            'Failed' => 'failed',
            'Failure' => 'failed',
            'Pending' => 'processing',
            'Cancelled' => 'cancelled',
        ];

        $newStatus = $statusMap[$status] ?? 'failed';

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
        $wallet = $transaction->user->wallet;

        DB::transaction(function () use ($transaction, $wallet) {
            $holdLedger = WalletLedger::where('wallet_id', $wallet->id)
                ->where('reference_id', $transaction->id)
                ->where('type', 'hold')
                ->first();

            if ($holdLedger) {
                $wallet->increment('balance', $transaction->retailer_charged);
                $wallet->refresh();

                $wallet->decrement('balance', $transaction->retailer_charged);
                $wallet->refresh();

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

            event(new RechargeSuccess($transaction));
        });
    }

    protected function handleFailure(Transaction $transaction, array $payload): void
    {
        DB::transaction(function () use ($transaction) {
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

    /**
     * Helper: Convert ISO country code to flag emoji
     */
    private function getFlagEmoji(string $isoCode): string
    {
        $offset = ord('A');
        $emoji = '';
        $chars = str_split($isoCode);
        foreach ($chars as $char) {
            $emoji .= mb_chr(ord($char) - $offset + 0x1F1E6);
        }
        return $emoji;
    }
}
