<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderItemMeta extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_items_meta';

    protected $fillable = [
        'order_item_id',
        'key',
        'value',
    ];

    // Relationships
    public function orderItem()
    {
        return $this->belongsTo(A2OrderItem::class, 'order_item_id');
    }
}
