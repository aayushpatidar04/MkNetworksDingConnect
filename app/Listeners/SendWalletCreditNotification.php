<?php

namespace App\Listeners;

use App\Events\WalletCredited;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWalletCreditNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WalletCredited $event): void
    {
        Notification::create([
            'user_id' => $event->user->id,
            'type' => 'wallet_credited',
            'title' => 'Wallet Credited',
            'message' => $event->broadcastWith()['message'],
            'data' => ['amount' => $event->amount, 'new_balance' => $event->newBalance],
        ]);
    }
}
