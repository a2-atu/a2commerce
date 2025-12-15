<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\PaymentCompleted;
use App\Models\A2\Commerce\A2OrderFinance;
use Illuminate\Support\Facades\Log;

class RecordFinance
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        try {
            $payment = $event->payment;
            $order = $payment->order;

            if ($order && !$order->finance) {
                // Calculate finance breakdown
                $taxRate = config('a2_commerce.tax_rate', 0.20); // 20% default
                $subtotal = $order->items->sum(function ($item) {
                    return $item->subtotal;
                });
                $tax = $subtotal * $taxRate;
                $shippingFee = config('a2_commerce.shipping_fee', 0);
                $discount = 0; // TODO: Calculate from coupons
                $commission = 0; // TODO: Calculate for multivendor

                $totalPayable = $subtotal + $tax + $shippingFee - $discount;

                // Create finance record
                A2OrderFinance::create([
                    'order_id' => $order->id,
                    'tax' => $tax,
                    'discount' => $discount,
                    'commission' => $commission,
                    'shipping_fee' => $shippingFee,
                    'total_payable' => $totalPayable,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record finance: ' . $e->getMessage());
        }
    }
}
