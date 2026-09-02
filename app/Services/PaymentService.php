<?php

namespace App\Services;

use App\Models\WalletTopup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Razorpay;

class PaymentService
{
 protected Razorpay $razorpay;

 public function __construct()
 {
 $this->razorpay = new Razorpay(
 config('platform.payment.razorpay.key_id'),
 config('platform.payment.razorpay.key_secret')
 );
 }

 /**
 * Create a payment order for wallet top-up
 */
 public function createOrder(float $amount, string $currency = 'INR', ?int $userId = null): array
 {
 try {
 $order = $this->razorpay->order->create([
 'amount' => (int) ($amount * 100), // Razorpay uses paise
 'currency' => $currency,
 'receipt' => 'topup_' . time() . '_' . ($userId ?? 'guest'),
 'payment_capture' => 1, // Auto-capture
 ]);

 return [
 'success' => true,
 'order_id' => $order['id'],
 'amount' => $order['amount'],
 'currency' => $order['currency'],
 ];

 } catch (\Exception $e) {
 Log::error('Razorpay Order Creation Error: ' . $e->getMessage());
 return ['success' => false, 'error' => $e->getMessage()];
 }
 }

 /**
 * Verify payment signature from Razorpay
 */
 public function verifyPayment(array $data): bool
 {
 try {
 $attributes = [
 'razorpay_order_id' => $data['razorpay_order_id'],
 'razorpay_payment_id' => $data['razorpay_payment_id'],
 ];

 $this->razorpay->utility->verifyPaymentSignature($attributes);
 return true;

 } catch (\Exception $e) {
 Log::error('Payment Verification Error: ' . $e->getMessage());
 return false;
 }
 }

 /**
 * Capture/finalize a payment
 */
 public function capturePayment(string $paymentId, float $amount): array
 {
 try {
 $payment = $this->razorpay->payment->fetch($paymentId);
 return [
 'success' => true,
 'payment' => $payment->toArray(),
 ];

 } catch (\Exception $e) {
 Log::error('Payment Capture Error: ' . $e->getMessage());
 return ['success' => false, 'error' => $e->getMessage()];
 }
 }

 /**
 * Create a UPI payment link
 */
 public function createUpiLink(float $amount, string $upiId, string $name, string $note = ''): array
 {
 try {
 $customer = [
 'name' => $name,
 'email' => config('mail.from.address'),
 ];

 $order = $this->razorpay->order->create([
 'amount' => (int) ($amount * 100),
 'currency' => 'INR',
 'receipt' => 'upi_' . time(),
 'payment_capture' => 1,
 ]);

 return [
 'success' => true,
 'upi_link' => "https://api.razorpay.com/v1/payment-links/upi",
 'order_id' => $order['id'],
 ];

 } catch (\Exception $e) {
 return ['success' => false, 'error' => $e->getMessage()];
 }
 }

 /**
 * Process webhook from payment gateway
 */
 public function processWebhook(array $payload): ?WalletTopup
 {
 $event = $payload['event'] ?? null;

 if ($event === 'payment.captured') {
 $payment = $payload['payload']['payment']['entity'] ?? null;
 if (!$payment) return null;

 $topup = WalletTopup::where('gateway_order_id', $payment['order_id'])->first();

 if ($topup) {
 $topup->update([
 'status' => 'completed',
 'gateway_transaction_id' => $payment['id'],
 'payment_response' => $payload,
 ]);

 // Credit the wallet
 $walletService = app(WalletService::class);
 $walletService->credit(
 $topup->user->wallet ?? $walletService->getWallet($topup->user),
 $topup->amount,
 'topup',
 $topup->id,
 "Wallet top-up via {$topup->payment_method}"
 );

 return $topup->fresh();
 }
 }

 return null;
 }
}
