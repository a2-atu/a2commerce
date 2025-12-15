<?php

namespace App\Events\A2\Commerce;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $productId,
        public ?int $variationId = null,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
