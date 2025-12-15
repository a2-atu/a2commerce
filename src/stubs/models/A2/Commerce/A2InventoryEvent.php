<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2InventoryEvent extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_inventory_events';

    protected $fillable = [
        'product_id',
        'event',
        'quantity',
        'actor_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
