<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet for a user
     */
    public function getWallet(User $user): Wallet
    {
        return $user->wallet ?? Wallet::create(['user_id' => $user->id]);
    }

    /**
     * Credit wallet (add money)
     */
    public function credit(Wallet $wallet, float $amount, string $referenceType, ?int $referenceId = null, string $description = ''): WalletLedger
    {
        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            $balanceBefore = $wallet->balance;
            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletLedger::create([
                'wallet_id' => $wallet->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Debit wallet (remove money)
     */
    public function debit(Wallet $wallet, float $amount, string $referenceType, ?int $referenceId = null, string $description = ''): WalletLedger
    {
        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            $balanceBefore = $wallet->balance;
            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            return WalletLedger::create([
                'wallet_id' => $wallet->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Hold amount (reserve for pending transaction)
     */
    public function hold(Wallet $wallet, float $amount, string $referenceType, ?int $referenceId = null, string $description = ''): WalletLedger
    {
        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            $availableBalance = $this->getAvailableBalance($wallet);

            if ($availableBalance < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            $balanceBefore = $wallet->balance;
            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            return WalletLedger::create([
                'wallet_id' => $wallet->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'type' => 'hold',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Release hold (return held amount)
     */
    public function releaseHold(Wallet $wallet, float $amount, ?int $referenceId = null, string $description = ''): WalletLedger
    {
        return DB::transaction(function () use ($wallet, $amount, $referenceId, $description) {
            $balanceBefore = $wallet->balance;
            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletLedger::create([
                'wallet_id' => $wallet->id,
                'reference_type' => 'recharge',
                'reference_id' => $referenceId,
                'type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description ?: "Hold released for transaction #{$referenceId}",
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Get available balance (excluding held amounts)
     */
    public function getAvailableBalance(Wallet $wallet): float
    {
        $heldAmount = WalletLedger::where('wallet_id', $wallet->id)
            ->where('type', 'hold')
            ->sum('amount');

        return max(0, $wallet->balance + $heldAmount);
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(Wallet $wallet, float $amount): bool
    {
        return $this->getAvailableBalance($wallet) >= $amount;
    }
}
