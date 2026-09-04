<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletCredited implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public float $amount;
    public float $newBalance;

    public function __construct(User $user, float $amount, float $newBalance)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->newBalance = $newBalance;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('retailer.' . $this->user->id);
    }

    public function broadcastAs(): string
    {
        return 'WalletCredited';
    }

    public function broadcastWith(): array
    {
        return [
            'amount' => $this->amount,
            'new_balance' => $this->newBalance,
            'message' => "Your wallet has been credited with Rs. {$this->amount}. New balance: Rs. {$this->newBalance}",
        ];
    }
}
