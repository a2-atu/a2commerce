<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\OrderCreated;
use App\Models\A2\Commerce\A2Order;
use Illuminate\Support\Facades\Log;

class GenerateOrderNumber
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;

            // Generate order number if not already set
            if (empty($order->order_number)) {
                $prefix = config('a2_commerce.order_prefix', 'ORD');
                $orderNumber = $prefix . '-' . strtoupper(uniqid());

                // Ensure uniqueness
                while (A2Order::where('order_number', $orderNumber)->exists()) {
                    $orderNumber = $prefix . '-' . strtoupper(uniqid());
                }

                $order->update(['order_number' => $orderNumber]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate order number: ' . $e->getMessage());
        }
    }
}
