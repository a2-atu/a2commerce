<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Auction extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_auctions';

    protected $fillable = [
        'product_id',
        'start_time',
        'end_time',
        'starting_price',
        'reserve_price',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'starting_price' => 'decimal:2',
        'reserve_price' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(A2Product::class, 'product_id');
    }

    public function bids()
    {
        return $this->hasMany(A2AuctionBid::class, 'auction_id');
    }

    public function logs()
    {
        return $this->hasMany(A2AuctionLog::class, 'auction_id');
    }
}
