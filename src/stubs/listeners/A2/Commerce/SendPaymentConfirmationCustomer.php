<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\PaymentCompleted;
use App\Jobs\A2\Commerce\SendPaymentConfirmationCustomerJob;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationCustomer
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
            SendPaymentConfirmationCustomerJob::dispatch($order, $payment);
        } catch (\Exception $e) {
            $orderId = isset($order) ? $order->id : null;
            Log::error('Failed to dispatch payment confirmation email job for customer: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getTraceAsString(),
            ]);
        }
    }
}
