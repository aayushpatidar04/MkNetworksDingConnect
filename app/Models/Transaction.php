<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'mobile_number',
        'operator_id',
        'country_id',
        'amount',
        'currency',
        'ding_transaction_id',
        'ding_order_reference',
        'ding_response',
        'status',
        'failure_reason',
        'receipt_number',
        'callback_received',
        'callback_received_at',
        'ip_address',
        'user_agent',
        'sku_code',
        'send_value',
        'receive_value',
        'send_currency',
        'receive_currency',
        'display_text',
        'receipt_text',
        'validity_period',
        'benefits',
        'redemption_type',
        'product_type',
    ];

    protected $casts = [
        'ding_response' => 'array',
        'benefits' => 'array',
        'callback_received' => 'boolean',
        'amount' => 'decimal:2',
        'send_value' => 'decimal:2',
        'receive_value' => 'decimal:2',
        'callback_received_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
