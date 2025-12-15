<?php

namespace App\Events\A2\Commerce;

use App\Models\A2\Commerce\A2Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public A2Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
