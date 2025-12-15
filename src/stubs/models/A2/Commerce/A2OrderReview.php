<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2OrderReview extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_order_reviews';

    protected $fillable = [
        'order_id',
        'user_id',
        'name',
        'rating',
        'title',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
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
}
