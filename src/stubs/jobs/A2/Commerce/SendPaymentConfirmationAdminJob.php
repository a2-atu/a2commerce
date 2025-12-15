<?php

namespace App\Jobs\A2\Commerce;

use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2Payment;
use App\Notifications\A2\Commerce\PaymentConfirmationAdmin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationAdminJob implements ShouldQueue
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

            // Get admin email from config
            $adminEmail = config('a2_commerce.admin_email', config('mail.from.address'));

            if (!$adminEmail) {
                Log::warning('Cannot send admin payment notification: No admin email configured');
                return;
            }

            // Send email
            Mail::to($adminEmail)->send(new PaymentConfirmationAdmin($this->order, $this->payment));

            Log::info('Payment confirmation email sent to admin', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email to admin: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
                'error' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to mark job as failed
        }
    }
}
