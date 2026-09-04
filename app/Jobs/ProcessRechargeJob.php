<?php

namespace App\Jobs;

use App\Services\DingConnectService;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRechargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    public function __construct(
        public Transaction $transaction,
    ) {
    }

    public function handle(DingConnectService $dingService): void
    {
        if ($this->transaction->status !== 'pending') {
            return; // Already processed
        }

        $this->transaction->update(['status' => 'processing']);

        // Broadcast processing status
        broadcast(new \App\Events\RechargeProcessing($this->transaction));

        // Send to DingConnect
        $result = $dingService->sendTopUp(
            $this->transaction->mobile_number,
            $this->transaction->operator->ding_operator_id,
            $this->transaction->country->iso_code,
            (float) $this->transaction->amount
        );

        if ($result['success']) {
            $data = $result['data'];
            $this->transaction->update([
                'ding_transaction_id' => $data['TransferID'] ?? $data['TransferId'],
                'ding_order_reference' => $data['OrderReference'] ?? null,
                'ding_response' => $data,
            ]);
        } else {
            $this->transaction->update([
                'status' => 'failed',
                'failure_reason' => $result['error'],
            ]);

            // Release hold
            $walletService = app(\App\Services\WalletService::class);
            $walletService->releaseHold(
                $this->transaction->user->wallet,
                $this->transaction->retailer_charged,
                $this->transaction->id,
                "API call failed: {$result['error']}"
            );

            broadcast(new \App\Events\RechargeFailed($this->transaction));
        }
    }
}
