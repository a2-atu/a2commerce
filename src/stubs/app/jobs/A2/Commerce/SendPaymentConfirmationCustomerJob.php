<?php

namespace App\Jobs\A2\Commerce;

use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2Payment;
use App\Notifications\A2\Commerce\PaymentConfirmationCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public A2Order $order;
    public A2Payment $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(A2Order $order, A2Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reload order with relationships (in case they weren't serialized)
            $this->order->load(['user', 'addresses', 'items.product']);

            // Get customer email: from user if logged in, or from shipping address if guest
            $customerEmail = null;

            if ($this->order->user_id && $this->order->user) {
                // Logged in user - use user email
                $customerEmail = $this->order->user->email;
            } else {
                // Guest order - get email from shipping address
                $shippingAddress = $this->order->addresses->where('type', 'shipping')->first();
                if ($shippingAddress && $shippingAddress->email) {
                    $customerEmail = $shippingAddress->email;
                }
            }

            if (!$customerEmail) {
                Log::warning('Cannot send payment confirmation email: No customer email found', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]);
                return;
            }

            // Send email
            Mail::to($customerEmail)->send(new PaymentConfirmationCustomer($this->order, $this->payment));

            Log::info('Payment confirmation email sent to customer', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'email' => $customerEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email to customer: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
                'error' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to mark job as failed
        }
    }
}
