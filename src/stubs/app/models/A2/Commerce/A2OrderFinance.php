<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderFinance extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_finance';

    protected $fillable = [
        'order_id',
        'tax',
        'discount',
        'commission',
        'shipping_fee',
        'total_payable',
    ];

    protected $casts = [
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'commission' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_payable' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }
}
