<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ProductVariation extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_product_variations';

    protected $fillable = [
        'product_id',
        'taxonomy_id',
        'price',
        'sku',
        'stock',
        'groupno',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'groupno' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function taxonomy()
    {
        return $this->belongsTo(\App\Models\Vrm\Taxonomy::class, 'taxonomy_id');
    }

    public function reservedStock()
    {
        return $this->hasMany(A2ReservedStock::class, 'variation_id');
    }

    public function orderItems()
    {
        return $this->hasMany(A2OrderItem::class, 'variation_id');
    }
}
