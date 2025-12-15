<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\OrderCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationAdmin
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;

            // TODO: Send notification to admin
            // Future implementation:
            // Get admin users and notify them
            // Mail::to($adminEmail)->send(new NewOrderAdminMail($order));
        } catch (\Exception $e) {
            Log::error('Failed to send admin order notification: ' . $e->getMessage());
        }
    }
}
