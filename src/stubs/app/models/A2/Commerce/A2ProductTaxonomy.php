<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ProductTaxonomy extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_product_taxonomies';

    protected $fillable = [
        'product_id',
        'taxonomy_id',
        'type',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function taxonomy()
    {
        return $this->belongsTo(\App\Models\Vrm\Taxonomy::class, 'taxonomy_id');
    }
}
