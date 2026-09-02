<?php

namespace App\Services;

use App\Models\CommissionRule;
use App\Models\Transaction;
use App\Models\AdminEarning;

class CommissionService
{
    /**
     * Calculate commission rate for a given transaction
     * Priority: specific rules override global defaults
     */
    public function getCommissionRate(float $amount, ?int $operatorId = null, ?int $countryId = null, string $tier = 'bronze'): float
    {
        // Get all active rules ordered by priority (highest first)
        $rules = CommissionRule::where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('effective_until')->orWhere('effective_until', '>=', now()->toDateString());
            })
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $amount, $operatorId, $countryId, $tier)) {
                return $rule->commission_type === 'percentage'
                    ? $rule->commission_value / 100
                    : $rule->commission_value / $amount; // Convert flat to percentage equivalent
            }
        }

        // Fallback to default
        return config('platform.commission.default_rate', 2.5) / 100;
    }

    /**
     * Calculate the full pricing for a transaction
     */
    public function calculatePricing(float $dingCost, float $amount, ?int $operatorId = null, ?int $countryId = null, string $tier = 'bronze'): array
    {
        $commissionRate = $this->getCommissionRate($amount, $operatorId, $countryId, $tier);
        $commissionAmount = round($dingCost * $commissionRate, 2);
        $retailerCharged = round($dingCost + $commissionAmount, 2);

        return [
            'ding_cost' => $dingCost,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'retailer_charged' => $retailerCharged,
        ];
    }

    /**
     * Check if a commission rule matches the transaction context
     */
    protected function ruleMatches(CommissionRule $rule, float $amount, ?int $operatorId, ?int $countryId, string $tier): bool
    {
        // Amount band check
        if ($rule->scope === 'amount_band') {
            if ($rule->min_amount !== null && $amount < $rule->min_amount)
                return false;
            if ($rule->max_amount !== null && $amount > $rule->max_amount)
                return false;
        }

        // Operator check
        if ($rule->scope === 'operator' && $rule->operator_id !== null) {
            if ($operatorId !== $rule->operator_id)
                return false;
        }

        // Country check
        if ($rule->scope === 'country' && $rule->country_id !== null) {
            if ($countryId !== $rule->country_id)
                return false;
        }

        // Retailer tier check
        if ($rule->scope === 'retailer_tier' && $rule->retailer_tier !== null) {
            if ($tier !== $rule->retailer_tier)
                return false;
        }

        return true;
    }

    /**
     * Record earnings from a successful transaction
     */
    public function recordEarning(Transaction $transaction): void
    {
        AdminEarning::create([
            'transaction_id' => $transaction->id,
            'retailer_id' => $transaction->user_id,
            'ding_cost' => $transaction->ding_cost,
            'retailer_charged' => $transaction->retailer_charged,
            'commission_amount' => $transaction->commission_amount,
            'currency' => $transaction->currency,
        ]);
    }
}
