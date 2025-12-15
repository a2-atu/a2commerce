<?php

namespace App\Events\A2\Commerce;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComparisonUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $action, // 'added', 'removed', 'cleared'
        public ?int $productId = null,
        public ?int $comparisonSessionId = null,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
