<?php

namespace App\Events\A2\Commerce;

use App\Models\A2\Commerce\A2Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRemovedFromWishlist
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public A2Product $product,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
