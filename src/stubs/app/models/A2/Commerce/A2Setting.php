<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Setting extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_settings';

    protected $fillable = [
        'key',
        'value',
    ];
}
