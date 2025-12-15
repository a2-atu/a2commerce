<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2ActionLog extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_action_log';

    protected $fillable = [
        'user_id',
        'action',
        'entity',
        'entity_id',
    ];

    protected $casts = [
        'entity_id' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
