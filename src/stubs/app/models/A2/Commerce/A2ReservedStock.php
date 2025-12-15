<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ReservedStock extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_reserved_stock';

    protected $fillable = [
        'product_id',
        'variation_id',
        'cart_id',
        'quantity',
        'in_checkout',
        'expire_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'in_checkout' => 'boolean',
        'expire_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(A2ProductVariation::class, 'variation_id');
    }
}
