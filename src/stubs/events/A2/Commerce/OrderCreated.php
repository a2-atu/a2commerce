<?php

namespace App\Events\A2\Commerce;

use App\Models\A\Commerce\A2Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public A2Order $order
    ) {}
}
