<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ComparisonSession extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_comparison_sessions';

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'title',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(A2ComparisonItem::class, 'comparison_session_id');
    }

    public function logs()
    {
        return $this->hasMany(A2ComparisonLog::class, 'comparison_session_id');
    }
}
