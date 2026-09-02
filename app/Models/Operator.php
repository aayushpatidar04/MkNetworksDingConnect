<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operator extends Model
{
 protected $fillable = ['name', 'slug', 'ding_operator_id', 'country_id', 'logo_url', 'is_active', 'display_order'];

 protected $casts = [
 'is_active' => 'boolean',
 ];

 public function country(): BelongsTo
 {
 return $this->belongsTo(Country::class);
 }
}
