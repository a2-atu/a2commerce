<?php

namespace App\Notifications\A2\Commerce;

use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public A2Order $order;
    public A2Payment $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(A2Order $order, A2Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Payment Confirmation - Order #' . $this->order->order_number)
            ->view('emails.a2.commerce.payment-confirmation-customer')
            ->with([
                'order' => $this->order,
                'payment' => $this->payment,
            ]);
    }
}
