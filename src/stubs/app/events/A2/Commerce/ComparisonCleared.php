<?php

namespace App\Events\A2\Commerce;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComparisonCleared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $comparisonSessionId,
        public ?int $userId = null,
        public ?string $sessionId = null
    ) {}
}
