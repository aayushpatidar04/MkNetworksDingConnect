<?php

namespace App\Jobs;

use App\Services\DingConnectService;
use App\Models\Transaction;
use App\Events\RechargeSuccess;
use App\Events\RechargeFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessRechargeJob implements ShouldQueue
{
 use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 public int $timeout = 90;
 public int $tries = 3;

 public function __construct(
 public Transaction $transaction,
 ) {}

 public function handle(DingConnectService $dingService): void
 {
 if ($this->transaction->status !== 'pending') {
 return;
 }

 $this->transaction->update(['status' => 'processing']);

 broadcast(new \App\Events\RechargeProcessing($this->transaction));

 $skuCode = $this->transaction->sku_code;
 $sendValue = (float) $this->transaction->send_value;
 $accountNumber = preg_replace('/[^0-9]/', '', $this->transaction->mobile_number);
 $distributorRef = 'TXN-' . $this->transaction->id;

 if (!$skuCode) {
 $this->markFailed('Missing SKU code for transaction');
 return;
 }

 Log::info('ProcessRechargeJob: Sending transfer', [
 'sku_code' => $skuCode,
 'send_value' => $sendValue,
 'account_number' => $accountNumber,
 ]);

 // Send to DingConnect using the correct SendTransfer API
 $result = $dingService->sendTransfer(
 skuCode: $skuCode,
 sendValue: $sendValue,
 accountNumber: $accountNumber,
 distributorRef: $distributorRef,
 validateOnly: false
 );

 if ($result['success']) {
 $data = $result['data'];
 $this->transaction->update([
 'ding_transaction_id' => $data['TransferRef'] ?? $data['TransferID'] ?? null,
 'ding_order_reference' => $data['DistributorRef'] ?? null,
 'ding_response' => $data,
 ]);

 // Check if it was instant or batch
 $processingState = $data['ProcessingState'] ?? '';

 if (in_array($processingState, ['Completed', 'Complete', 'Successful'])) {
 // Instant success
 $this->transaction->update(['status' => 'success']);
 $dingService->processCallback(array_merge($data, ['Status' => 'Successful']));
 } elseif (in_array($processingState, ['Failed', 'Failure'])) {
 // Instant failure
 $this->markFailed($data['ErrorCodes'][0] ?? $data['Message'] ?? 'Transfer failed');
 } else {
 // Batch or still processing - status remains 'processing'
 // Will be updated via webhook callback or polling
 $this->transaction->update(['status' => 'processing']);

 // Try to check status after a short delay via ListTransferRecords
 Log::info('ProcessRechargeJob: Transfer is batch/processing mode', [
 'transfer_ref' => $data['TransferRef'] ?? null,
 'processing_state' => $processingState,
 ]);
 }
 } else {
 $this->markFailed($result['error']);
 }
}

protected function markFailed(string $reason): void
{
 $this->transaction->update([
 'status' => 'failed',
 'failure_reason' => $reason,
 ]);

 $walletService = app(\App\Services\WalletService::class);
 $walletService->releaseHold(
 $this->transaction->user->wallet,
 $this->transaction->send_value,
 $this->transaction->id,
 "API call failed: {$reason}"
 );

 broadcast(new \App\Events\RechargeFailed($this->transaction));
}
}
