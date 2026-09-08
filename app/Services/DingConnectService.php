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
    protected string $baseUrl;
    protected string $apiKey;
    protected string $customerId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('platform.dingconnect.base_url'), '/');
        $this->apiKey = config('platform.dingconnect.api_key');
        $this->customerId = config('platform.dingconnect.customer_id') ?? '';
    }

    // =========================================================================
    // HTTP HELPERS
    // =========================================================================

    protected function get(string $endpoint, array $params = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            Log::info('DingConnect GET', ['url' => $url]);

            $response = Http::timeout(30)
                ->withHeaders(['api_key' => $this->apiKey])
                ->get($url);

            Log::info('DingConnect GET response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['ResultCode'] ?? 0) == 1) {
                    return ['success' => true, 'data' => $data];
                }
                return ['success' => false, 'error' => $data['ErrorCodes'][0] ?? $data['Message'] ?? 'API error', 'raw' => $data];
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status(), 'body' => substr($response->body(), 0, 200)];

        } catch (\Exception $e) {
            Log::error('DingConnect GET Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function postForm(string $endpoint, array $formData = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            Log::info('DingConnect POST form', ['url' => $url, 'data' => $formData]);

            $response = Http::timeout(90)
                ->withHeaders(['api_key' => $this->apiKey])
                ->asForm()
                ->post($url, $formData);

            Log::info('DingConnect POST form response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['ResultCode'] ?? 0) == 1) {
                    return ['success' => true, 'data' => $data];
                }
                return ['success' => false, 'error' => $data['ErrorCodes'][0] ?? $data['Message'] ?? 'API error', 'raw' => $data];
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status(), 'body' => substr($response->body(), 0, 200)];

        } catch (\Exception $e) {
            Log::error('DingConnect POST Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // PUBLIC API METHODS
    // =========================================================================

    public function getCountries(): array
    {
        return $this->get('/api/V1/GetCountries');
    }

    public function getProviders(?string $countryIso = null, ?string $providerCodes = null): array
    {
        $params = [];
        if ($countryIso)
            $params['countryIsos'] = $countryIso;
        if ($providerCodes)
            $params['providerCodes'] = $providerCodes;
        return $this->get('/api/V1/GetProviders', $params);
    }

    public function getProducts(string $countryIso, string $providerCode): array
    {
        return $this->get('/api/V1/GetProducts', [
            'countryIsos' => $countryIso,
            'providerCodes' => $providerCode,
        ]);
    }

    public function getProviderStatus(?string $providerCodes = null): array
    {
        $params = [];
        if ($providerCodes)
            $params['providerCodes'] = $providerCodes;
        return $this->get('/api/V1/GetProviderStatus', $params);
    }

    public function getProductDescriptions(array $skuCodes, ?string $languageCode = 'en'): array
    {
        if (empty($skuCodes))
            return ['success' => true, 'data' => []];
        return $this->get('/api/V1/GetProductDescriptions', [
            'languageCodes' => $languageCode,
            'skuCodes' => implode(',', array_slice($skuCodes, 0, 50)),
        ]);
    }

    public function getBalance(): array
    {
        return $this->get('/api/V1/GetBalance');
    }

    public function getPromotions(?string $countryIsos = null, ?string $providerCodes = null): array
    {
        $params = [];
        if ($countryIsos)
            $params['countryIsos'] = $countryIsos;
        if ($providerCodes)
            $params['providerCodes'] = $providerCodes;
        return $this->get('/api/V1/GetPromotions', $params);
    }

    public function getAccountLookup(string $accountNumber): array
    {
        return $this->get('/api/V1/GetAccountLookup', [
            'accountNumber' => $accountNumber,
        ]);
    }

    public function estimatePrices(string $skuCode, float $sendValue, ?string $sendCurrencyIso = null, ?float $receiveValue = null): array
    {
        $data = [
            'SkuCode' => $skuCode,
            'SendValue' => $sendValue,
        ];
        if ($sendCurrencyIso)
            $data['SendCurrencyIso'] = $sendCurrencyIso;
        if ($receiveValue)
            $data['ReceiveValue'] = $receiveValue;
        return $this->postForm('/api/V1/EstimatePrices', $data);
    }

    /**
     * SendTransfer - Send a top-up / recharge
     * POST /api/V1/SendTransfer (form-urlencoded)
     *
     * Required: SkuCode, SendValue, AccountNumber, DistributorRef, ValidateOnly
     */
    public function sendTransfer(string $skuCode, float $sendValue, string $accountNumber, string $distributorRef, bool $validateOnly = false, ?string $sendCurrencyIso = null, ?array $settings = null): array
    {
        $payload = [
            'SkuCode' => $skuCode,
            'SendValue' => number_format($sendValue, 2, '.', ''),
            'AccountNumber' => $accountNumber,
            'DistributorRef' => $distributorRef,
            'ValidateOnly' => $validateOnly ? 'true' : 'false',
        ];

        if ($sendCurrencyIso) {
            $payload['SendCurrencyIso'] = $sendCurrencyIso;
        }

        if ($settings && count($settings) > 0) {
            $payload['Settings'] = json_encode($settings);
        }

        return $this->postForm('/api/V1/SendTransfer', $payload);
    }

    /**
     * ListTransferRecords - Query transfer status
     * POST /api/V1/ListTransferRecords (form-urlencoded)
     */
    public function listTransferRecords(?string $transferRef = null, ?string $distributorRef = null, ?string $accountNumber = null, int $take = 10, int $skip = 0): array
    {
        $payload = [
            'Take' => $take,
            'Skip' => $skip,
        ];
        if ($transferRef)
            $payload['TransferRef'] = $transferRef;
        if ($distributorRef)
            $payload['DistributorRef'] = $distributorRef;
        if ($accountNumber)
            $payload['AccountNumber'] = $accountNumber;

        return $this->postForm('/api/V1/ListTransferRecords', $payload);
    }

    /**
     * CancelTransfers - Cancel a transfer
     * POST /api/V1/CancelTransfers (form-urlencoded)
     */
    public function cancelTransfers(string $transferRef, string $distributorRef): array
    {
        $payload = [
            'TransferId' => json_encode(['TransferRef' => $transferRef, 'DistributorRef' => $distributorRef]),
        ];
        return $this->postForm('/api/V1/CancelTransfers', $payload);
    }

    /**
     * Check transaction status (legacy - use listTransferRecords instead)
     * GET /api/V1/Transfer/{id}
     */
    public function checkStatus(string $dingTransactionId): array
    {
        return $this->get('/api/V1/Transfer/' . $dingTransactionId);
    }

    // =========================================================================
    // CALLBACK PROCESSING
    // =========================================================================

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

        if ($newStatus === 'success') {
            $this->handleSuccess($transaction, $payload);
        } elseif ($newStatus === 'failed') {
            $this->handleFailure($transaction, $payload);
        } else {
            $transaction->update(['status' => 'processing']);
        }

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
                $wallet->increment('balance', $transaction->send_value);
                $wallet->refresh();

                $wallet->decrement('balance', $transaction->send_value);
                $wallet->refresh();

                WalletLedger::create([
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'debit',
                    'amount' => $transaction->send_value,
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
                $wallet->increment('balance', $transaction->send_value);

                WalletLedger::create([
                    'wallet_id' => $wallet->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'refund',
                    'amount' => $transaction->send_value,
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

    private function getFlagEmoji(string $isoCode): string
    {
        $offset = ord('A');
        $emoji = '';
        foreach (str_split($isoCode) as $char) {
            $emoji .= mb_chr(ord($char) - $offset + 0x1F1E6);
        }
        return $emoji;
    }
}
