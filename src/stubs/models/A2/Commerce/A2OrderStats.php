<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderStats extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_stats';

    protected $fillable = [
        'order_id',
        'metrics',
    ];

    protected $casts = [
        'metrics' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }
}
