<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Wishlist extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_wishlist';

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }
}
