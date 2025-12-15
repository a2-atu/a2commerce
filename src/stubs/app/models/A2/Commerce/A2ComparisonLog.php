<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ComparisonLog extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_comparison_log';

    protected $fillable = [
        'comparison_session_id',
        'product_a',
        'product_b',
        'action',
    ];

    // Relationships
    public function comparisonSession()
    {
        return $this->belongsTo(A2ComparisonSession::class, 'comparison_session_id');
    }

    public function productA()
    {
        return $this->belongsTo(A2Product::class, 'product_a');
    }

    public function productB()
    {
        return $this->belongsTo(A2Product::class, 'product_b');
    }
}
