<?php

namespace App\Services\A2\Commerce;

use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create payment record
     */
    public function createPayment(A2Order $order, string $method, float $amount): A2Payment
    {
        return A2Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'method' => $method,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark payment as completed
     */
    public function markCompleted(A2Payment $payment, array $payload = []): void
    {
        $payment->markCompleted($payload);
    }

    /**
     * Mark payment as failed
     */
    public function markFailed(A2Payment $payment, ?string $reason = null, array $payload = []): void
    {
        $payment->markFailed($reason, $payload);
    }

    /**
     * Get payment by transaction code
     */
    public function getByTransactionCode(string $transactionCode): ?A2Payment
    {
        return A2Payment::where('transaction_code', $transactionCode)->first();
    }
}
