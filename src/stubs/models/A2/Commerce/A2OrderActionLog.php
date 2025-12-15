<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderActionLog extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_action_log';

    protected $fillable = [
        'order_id',
        'action',
        'actor_id',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }

    public function actor()
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_id');
    }
}
