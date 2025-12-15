<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\PaymentCompleted;
use Illuminate\Support\Facades\Log;

class MarkOrderPaid
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
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                // Log the action
                $order->actionLogs()->create([
                    'action' => 'Order marked as paid',
                    'actor_id' => $payment->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark order as paid: ' . $e->getMessage());
        }
    }
}
