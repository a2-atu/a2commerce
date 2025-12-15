<?php

namespace App\Models\A2\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class A2Order extends Model
{
    use HasFactory;

    protected $table = 'a2_ec_orders';

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'total',
        'payment_status',
        'payment_method',
        'is_multivendor',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'is_multivendor' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(A2OrderItem::class, 'order_id');
    }

    public function finance()
    {
        return $this->hasOne(A2OrderFinance::class, 'order_id');
    }

    public function addresses()
    {
        return $this->hasMany(A2OrderAddress::class, 'order_id');
    }

    public function actionLogs()
    {
        return $this->hasMany(A2OrderActionLog::class, 'order_id');
    }

    public function reviews()
    {
        return $this->hasMany(A2OrderReview::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(A2Payment::class, 'order_id');
    }

    public function stats()
    {
        return $this->hasOne(A2OrderStats::class, 'order_id');
    }

    public function adminNotes()
    {
        return $this->hasMany(A2OrderAdminNote::class, 'order_id');
    }

    public function downloadLogs()
    {
        return $this->hasMany(A2OrderDownloadLog::class, 'order_id');
    }

    public function serviceLogs()
    {
        return $this->hasMany(A2ServiceLog::class, 'order_id');
    }

    /**
     * Mark order as paid
     */
    public function markPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        // Log the action
        $this->actionLogs()->create([
            'action' => 'Order marked as paid',
            'actor_id' => auth()->id(),
        ]);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = config('a2_commerce.order_prefix', 'ORD');
        $orderNumber = $prefix . '-' . strtoupper(uniqid());

        // Ensure uniqueness
        while (static::where('order_number', $orderNumber)->exists()) {
            $orderNumber = $prefix . '-' . strtoupper(uniqid());
        }

        return $orderNumber;
    }
}
