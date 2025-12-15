<?php

namespace App\Events\A2\Commerce;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WishlistUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $action, // 'added' or 'removed'
        public int $productId,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
