<?php

namespace App\Services\A2\Commerce;

use App\Events\A2\Commerce\OrderCreated;
use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2OrderAddress;
use App\Models\A2\Commerce\A2OrderItem;
use App\Models\A2\Commerce\A2Product;
use App\Models\A2\Commerce\A2ProductVariation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create order from cart
     */
    public function createFromCart(array $addressData, ?string $paymentMethod = null): ?A2Order
    {
        try {
            $cartService = app(CartService::class);
            $cartData = $cartService->getCartData();

            if (empty($cartData['items'])) {
                return null;
            }

            $userId = Auth::id(); // Can be null for guest checkout

            DB::beginTransaction();

            // Calculate totals
            $subtotal = $cartData['subtotal'];
            $taxRate = config('a2_commerce.tax_rate', 0.20);
            $tax = $subtotal * $taxRate;
            $shippingFee = config('a2_commerce.shipping_fee', 0);
            $total = $subtotal + $tax + $shippingFee;

            // Determine payment status based on payment method
            // Note: payment_status enum only allows 'unpaid', 'paid', 'failed'
            // For payment on delivery, we use 'unpaid' since payment hasn't been received yet
            $paymentStatus = 'unpaid';

            // Generate order number before creating order
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $order = A2Order::create([
                'user_id' => $userId, // Can be null for guest checkout
                'order_number' => $orderNumber,
                'status' => 'pending',
                'total' => $total,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod ?? 'payment_on_delivery',
                'is_multivendor' => false,
            ]);

            // Create order items
            foreach ($cartData['items'] as $item) {
                $product = A2Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                $price = $item['price'];
                $quantity = $item['quantity'];
                $subtotal = $price * $quantity;

                // Get variation if exists
                $variation = null;
                if (!empty($item['variation_id'])) {
                    $variation = A2ProductVariation::find($item['variation_id']);
                    if ($variation) {
                        $price = $variation->price ?? $price;
                    }
                }

                A2OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variation_id' => $variation?->id,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);

                // Deduct stock if variation exists
                if ($variation && $variation->stock !== null) {
                    $variation->decrement('stock', $quantity);
                } elseif (!$variation) {
                    // Check default variation
                    $defaultVariation = $product->variations()->whereNull('taxonomy_id')->first();
                    if ($defaultVariation && $defaultVariation->stock !== null) {
                        $defaultVariation->decrement('stock', $quantity);
                    }
                }
            }

            // Create shipping address
            A2OrderAddress::create([
                'order_id' => $order->id,
                'type' => 'shipping',
                'first_name' => $addressData['first_name'] ?? null,
                'last_name' => $addressData['last_name'] ?? null,
                'email' => $addressData['email'] ?? null,
                'phone' => $addressData['phone'] ?? null,
                'address_line' => $addressData['address'] ?? '',
                'city' => $addressData['city'] ?? '',
                'country' => $addressData['country'] ?? '',
                'postal_code' => $addressData['postal_code'] ?? '',
            ]);

            // Create billing address (if different)
            if (isset($addressData['same_as_shipping']) && !$addressData['same_as_shipping']) {
                A2OrderAddress::create([
                    'order_id' => $order->id,
                    'type' => 'billing',
                    'first_name' => $addressData['billing_first_name'] ?? $addressData['first_name'] ?? null,
                    'last_name' => $addressData['billing_last_name'] ?? $addressData['last_name'] ?? null,
                    'email' => $addressData['billing_email'] ?? $addressData['email'] ?? null,
                    'phone' => $addressData['billing_phone'] ?? $addressData['phone'] ?? null,
                    'address_line' => $addressData['billing_address'] ?? '',
                    'city' => $addressData['billing_city'] ?? '',
                    'country' => $addressData['billing_country'] ?? '',
                    'postal_code' => $addressData['billing_postal_code'] ?? '',
                ]);
            } else {
                // Copy shipping address as billing
                A2OrderAddress::create([
                    'order_id' => $order->id,
                    'type' => 'billing',
                    'first_name' => $addressData['first_name'] ?? null,
                    'last_name' => $addressData['last_name'] ?? null,
                    'email' => $addressData['email'] ?? null,
                    'phone' => $addressData['phone'] ?? null,
                    'address_line' => $addressData['address'] ?? '',
                    'city' => $addressData['city'] ?? '',
                    'country' => $addressData['country'] ?? '',
                    'postal_code' => $addressData['postal_code'] ?? '',
                ]);
            }

            // Log order creation action (actor_id can be null for guest orders)
            $order->actionLogs()->create([
                'action' => 'Order created' . ($userId ? '' : ' (Guest checkout)'),
                'actor_id' => $userId,
            ]);

            DB::commit();

            // Fire event
            event(new OrderCreated($order));

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(A2Order $order, string $status, ?int $actorId = null): bool
    {
        try {
            $oldStatus = $order->status;

            $order->update(['status' => $status]);

            // Log status change
            $order->actionLogs()->create([
                'action' => "Order status changed from {$oldStatus} to {$status}",
                'actor_id' => $actorId ?? Auth::id(),
            ]);

            // Fire event if status changed
            if ($oldStatus !== $status) {
                event(new \App\Events\A2\OrderStatusUpdated($order, $oldStatus, $status));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update order status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        $prefix = config('a2_commerce.order_prefix', 'ORD');
        $orderNumber = $prefix . '-' . strtoupper(uniqid());

        // Ensure uniqueness
        while (A2Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = $prefix . '-' . strtoupper(uniqid());
        }

        return $orderNumber;
    }
}
