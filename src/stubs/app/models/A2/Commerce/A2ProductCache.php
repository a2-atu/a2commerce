<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ProductCache extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_product_cache';

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'product_id',
        'preview_data',
    ];

    protected $casts = [
        'preview_data' => 'array',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }
}
