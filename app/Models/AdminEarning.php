<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEarning extends Model
{
 protected $fillable = ['transaction_id', 'retailer_id', 'ding_cost', 'retailer_charged', 'commission_amount', 'currency', 'is_settled', 'settled_at'];

 protected $casts = [
 'is_settled' => 'boolean',
 'settled_at' => 'datetime',
 'created_at' => 'datetime',
 ];

 public $timestamps = false;

 protected $dates = ['created_at'];

 public function transaction(): BelongsTo
 {
 return $this->belongsTo(Transaction::class);
 }

 public function retailer(): BelongsTo
 {
 return $this->belongsTo(User::class, 'retailer_id');
 }
}
