<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Coupon extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_coupons';

    protected $fillable = [
        'code',
        'type',
        'value',
        'expiry_date',
        'usage_limit',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expiry_date' => 'datetime',
        'usage_limit' => 'integer',
    ];
}
