<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Payment extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_payments';

    protected $fillable = [
        'order_id',
        'user_id',
        'method',
        'transaction_code',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(A2Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Mark payment as completed
     */
    public function markCompleted(array $payload = []): void
    {
        $updateData = [
            'status' => 'completed',
        ];

        if (isset($payload['transaction_code'])) {
            $updateData['transaction_code'] = $payload['transaction_code'];
        } elseif ($this->transaction_code) {
            $updateData['transaction_code'] = $this->transaction_code;
        }

        $this->update($updateData);

        // Fire event
        event(new \App\Events\A2\Commerce\PaymentCompleted($this));
    }

    /**
     * Mark payment as failed
     */
    public function markFailed(?string $reason = null, array $payload = []): void
    {
        $updateData = [
            'status' => 'failed',
        ];

        if (isset($payload['transaction_code'])) {
            $updateData['transaction_code'] = $payload['transaction_code'];
        } elseif ($this->transaction_code) {
            $updateData['transaction_code'] = $this->transaction_code;
        }

        $this->update($updateData);

        // Fire event
        event(new \App\Events\A2\Commerce\PaymentFailed($this, $reason));
    }
}
