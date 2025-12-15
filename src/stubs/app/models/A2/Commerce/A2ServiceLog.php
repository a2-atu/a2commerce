<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ServiceLog extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_service_log';

    protected $fillable = [
        'order_id',
        'user_id',
        'service_id',
        'hours_logged',
        'note',
    ];

    protected $casts = [
        'hours_logged' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(A2Product::class, 'service_id');
    }
}
