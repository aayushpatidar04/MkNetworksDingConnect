<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
 protected $fillable = [
 'name', 'description', 'scope',
 'operator_id', 'country_id', 'retailer_tier',
 'min_amount', 'max_amount',
 'commission_type', 'commission_value',
 'priority', 'effective_from', 'effective_until', 'is_active',
 ];

 protected $casts = [
 'is_active' => 'boolean',
 'effective_from' => 'date',
 'effective_until' => 'date',
 'min_amount' => 'decimal:2',
 'max_amount' => 'decimal:2',
 'commission_value' => 'decimal:4',
 ];
}
