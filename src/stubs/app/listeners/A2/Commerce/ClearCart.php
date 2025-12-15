<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\PaymentCompleted;
use App\Models\A2\Commerce\A2ReservedStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ClearCart
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            $payment = $event->payment;
            $order = $payment->order;

            if ($order) {
                $cartId = session()->getId();

                // Clear cart from session
                Session::forget('a2_cart');

                // Release all reserved stock for this cart
                A2ReservedStock::where('cart_id', $cartId)->delete();
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cart: ' . $e->getMessage());
        }
    }
}
