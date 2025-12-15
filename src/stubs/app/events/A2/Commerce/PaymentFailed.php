<?php

namespace App\Events\A2\Commerce;

use App\Models\A2\Commerce\A2Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public A2Payment $payment,
        public ?string $reason = null
    ) {}
}
