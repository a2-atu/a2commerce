<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\OrderCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationCustomer
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;

            // TODO: Send order confirmation email to customer
            // Future implementation:
            // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
        } catch (\Exception $e) {
            Log::error('Failed to send customer order notification: ' . $e->getMessage());
        }
    }
}
