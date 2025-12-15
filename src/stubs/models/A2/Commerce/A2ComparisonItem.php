<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ComparisonItem extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_comparison_items';

    protected $fillable = [
        'comparison_session_id',
        'product_id',
    ];

    // Relationships
    public function comparisonSession()
    {
        return $this->belongsTo(A2ComparisonSession::class, 'comparison_session_id');
    }

    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }
}
