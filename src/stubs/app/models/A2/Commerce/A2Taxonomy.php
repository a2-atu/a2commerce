<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class A2Taxonomy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'a2_ec_taxonomies';

    protected $fillable = [
        'type',
        'group',
        'for',
        'taxonomy_id',
    ];

    // Relationships
    public function vrmTaxonomy()
    {
        return $this->belongsTo(\App\Models\Vrm\Taxonomy::class, 'taxonomy_id');
    }
}
