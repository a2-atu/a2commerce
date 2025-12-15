<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ProductMeta extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_product_meta';

    protected $fillable = [
        'product_id',
        'key',
        'value',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }
}
