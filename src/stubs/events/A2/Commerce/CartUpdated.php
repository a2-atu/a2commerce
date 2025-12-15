<?php

namespace App\Events\A2\Commerce;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $cartData,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
