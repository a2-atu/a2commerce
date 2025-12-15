<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ProductReview extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_product_reviews';

    protected $fillable = [
        'product_id',
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
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
