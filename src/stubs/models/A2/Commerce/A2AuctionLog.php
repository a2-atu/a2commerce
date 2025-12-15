<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2AuctionLog extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_auction_log';

    protected $fillable = [
        'auction_id',
        'user_id',
        'action',
    ];

    // Relationships
    public function auction()
    {
        return $this->belongsTo(A2Auction::class, 'auction_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
