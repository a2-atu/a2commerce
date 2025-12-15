<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderItem extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(A2ProductVariation::class, 'variation_id');
    }

    public function meta()
    {
        return $this->hasMany(A2OrderItemMeta::class, 'order_item_id');
    }

    public function downloadLogs()
    {
        return $this->hasMany(A2OrderDownloadLog::class, 'order_item_id');
    }
}
