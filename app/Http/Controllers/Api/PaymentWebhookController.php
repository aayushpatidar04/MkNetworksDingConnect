<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Verify Razorpay signature
        if (!isset($payload['payload']) || !isset($payload['payload']['payment']['entity'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $topup = $this->paymentService->processWebhook($payload);

        if ($topup) {
            // Notify user via Pusher
            broadcast(new \App\Events\WalletCredited($topup->user, (float) $topup->amount, $topup->user->wallet->balance));

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'ignored'], 200);
    }
}
