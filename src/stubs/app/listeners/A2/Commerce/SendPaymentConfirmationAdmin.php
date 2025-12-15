<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\PaymentCompleted;
use App\Jobs\A2\Commerce\SendPaymentConfirmationAdminJob;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationAdmin
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            $payment = $event->payment;
            $order = $payment->order;

            if (!$order) {
                return;
            }

            // Dispatch job to send email
            SendPaymentConfirmationAdminJob::dispatch($order, $payment);
        } catch (\Exception $e) {
            $orderId = isset($order) ? $order->id : null;
            Log::error('Failed to dispatch payment confirmation email job for admin: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getTraceAsString(),
            ]);
        }
    }
}
