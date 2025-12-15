<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\OrderCreated;
use App\Models\A2\Commerce\A2ReservedStock;
use Illuminate\Support\Facades\Log;

class CaptureReservedStock
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $cartId = session()->getId();

            // Mark reserved stock as in checkout
            A2ReservedStock::where('cart_id', $cartId)
                ->update(['in_checkout' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to capture reserved stock: ' . $e->getMessage());
        }
    }
}
