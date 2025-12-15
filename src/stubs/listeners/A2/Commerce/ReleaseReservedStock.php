<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\CartItemRemoved;
use App\Events\A2\Commerce\CartUpdated;
use App\Models\A2\Commerce\A2ReservedStock;
use Illuminate\Support\Facades\Log;

class ReleaseReservedStock
{
    /**
     * Handle the event.
     */
    public function handle(CartItemRemoved|CartUpdated $event): void
    {
        try {
            $cartId = $event->sessionId ?? session()->getId();

            if ($event instanceof CartItemRemoved) {
                // Release specific item
                A2ReservedStock::where('cart_id', $cartId)
                    ->where('product_id', $event->productId)
                    ->where('variation_id', $event->variationId)
                    ->delete();
            } else {
                // Release all items for this cart
                A2ReservedStock::where('cart_id', $cartId)->delete();
            }
        } catch (\Exception $e) {
            Log::error('Failed to release reserved stock: ' . $e->getMessage());
        }
    }
}
