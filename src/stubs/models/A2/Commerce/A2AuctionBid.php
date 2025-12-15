<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2AuctionBid extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_auction_bids';

    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
        'is_won',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_won' => 'boolean',
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
