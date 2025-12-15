<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\CartUpdated;
use App\Models\A2\Commerce\A2ReservedStock;
use Illuminate\Support\Facades\Log;

class ReserveStock
{
    /**
     * Handle the event.
     */
    public function handle(CartUpdated $event): void
    {
        try {
            $cartId = $event->sessionId ?? session()->getId();
            $expireAt = now()->addMinutes(5); // 5 minute TTL

            // Get items from cart data
            $items = $event->cartData['items'] ?? [];

            // Process each cart item
            foreach ($items as $item) {
                if (!isset($item['product_id'])) {
                    continue;
                }

                // Check if reservation already exists
                $existing = A2ReservedStock::where('cart_id', $cartId)
                    ->where('product_id', $item['product_id'])
                    ->where('variation_id', $item['variation_id'] ?? null)
                    ->first();

                if ($existing) {
                    // Update existing reservation
                    $existing->update([
                        'quantity' => $item['quantity'] ?? 1,
                        'expire_at' => $expireAt,
                        'in_checkout' => false,
                    ]);
                } else {
                    // Create new reservation
                    A2ReservedStock::create([
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'cart_id' => $cartId,
                        'quantity' => $item['quantity'] ?? 1,
                        'in_checkout' => false,
                        'expire_at' => $expireAt,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to reserve stock: ' . $e->getMessage());
        }
    }
}
