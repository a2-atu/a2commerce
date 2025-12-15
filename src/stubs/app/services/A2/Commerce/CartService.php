<?php

namespace App\Services\A2\Commerce;

use App\Events\A2\Commerce\CartItemRemoved;
use App\Events\A2\Commerce\CartUpdated;
use App\Models\A2\Commerce\A2Product;
use App\Models\A2\Commerce\A2ProductVariation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Get cart session key
     */
    protected function getCartKey(): string
    {
        $userId = Auth::id();
        return $userId ? "a2_cart_user_{$userId}" : 'a2_cart_guest';
    }

    /**
     * Get current cart items
     */
    public function getItems(): array
    {
        $cart = Session::get($this->getCartKey(), []);
        return $cart['items'] ?? [];
    }

    /**
     * Get cart data with totals
     */
    public function getCartData(): array
    {
        $items = $this->getItems();
        $subtotal = 0;
        $taxRate = config('a2_commerce.tax_rate', 0.20);
        $shippingFee = config('a2_commerce.shipping_fee', 0);

        foreach ($items as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax + $shippingFee;

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
            'item_count' => count($items),
        ];
    }

    /**
     * Add item to cart
     */
    public function add(int $productId, int $quantity = 1, ?int $variationId = null): bool
    {
        try {
            $product = A2Product::find($productId);
            if (!$product || !$product->is_active) {
                return false;
            }

            // Check stock availability
            if ($variationId) {
                $variation = A2ProductVariation::find($variationId);
                if (!$variation || $variation->stock < $quantity) {
                    return false;
                }
                $price = $variation->price ?? $product->price;
            } else {
                // Check default variation (taxonomy_id = null)
                $defaultVariation = $product->variations()->whereNull('taxonomy_id')->first();
                if ($defaultVariation && $defaultVariation->stock < $quantity) {
                    return false;
                }
                $price = $product->price;
            }

            $cartKey = $this->getCartKey();
            $cart = Session::get($cartKey, ['items' => []]);
            $items = $cart['items'] ?? [];

            // Check if item already exists
            $itemKey = $this->getItemKey($productId, $variationId);
            $existingIndex = $this->findItemIndex($items, $productId, $variationId);

            if ($existingIndex !== false) {
                // Update quantity
                $newQuantity = $items[$existingIndex]['quantity'] + $quantity;

                // Check stock again
                if ($variationId) {
                    if ($variation->stock < $newQuantity) {
                        return false;
                    }
                } else {
                    if ($defaultVariation && $defaultVariation->stock < $newQuantity) {
                        return false;
                    }
                }

                $items[$existingIndex]['quantity'] = $newQuantity;
            } else {
                // Add new item
                $items[] = [
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity' => $quantity,
                    'price' => (float) $price,
                    'name' => $product->name,
                ];
            }

            $cart['items'] = $items;
            Session::put($cartKey, $cart);

            // Fire event
            event(new CartUpdated(
                $this->getCartData(),
                Auth::id(),
                session()->getId()
            ));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add item to cart: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update item quantity
     */
    public function update(int $productId, int $quantity, ?int $variationId = null): bool
    {
        try {
            if ($quantity < 1) {
                return $this->remove($productId, $variationId);
            }

            $product = A2Product::find($productId);
            if (!$product) {
                return false;
            }

            // Check stock availability
            if ($variationId) {
                $variation = A2ProductVariation::find($variationId);
                if (!$variation || $variation->stock < $quantity) {
                    return false;
                }
            } else {
                $defaultVariation = $product->variations()->whereNull('taxonomy_id')->first();
                if ($defaultVariation && $defaultVariation->stock < $quantity) {
                    return false;
                }
            }

            $cartKey = $this->getCartKey();
            $cart = Session::get($cartKey, ['items' => []]);
            $items = $cart['items'] ?? [];

            $index = $this->findItemIndex($items, $productId, $variationId);
            if ($index !== false) {
                $items[$index]['quantity'] = $quantity;
                $cart['items'] = $items;
                Session::put($cartKey, $cart);

                // Fire event
                event(new CartUpdated(
                    $this->getCartData(),
                    Auth::id(),
                    session()->getId()
                ));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update cart item: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(int $productId, ?int $variationId = null): bool
    {
        try {
            $cartKey = $this->getCartKey();
            $cart = Session::get($cartKey, ['items' => []]);
            $items = $cart['items'] ?? [];

            $index = $this->findItemIndex($items, $productId, $variationId);
            if ($index !== false) {
                unset($items[$index]);
                $items = array_values($items); // Re-index

                $cart['items'] = $items;
                Session::put($cartKey, $cart);

                // Fire events
                event(new CartItemRemoved(
                    $productId,
                    $variationId,
                    Auth::id(),
                    session()->getId()
                ));

                event(new CartUpdated(
                    $this->getCartData(),
                    Auth::id(),
                    session()->getId()
                ));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to remove cart item: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear entire cart
     */
    public function clear(): void
    {
        $cartKey = $this->getCartKey();
        Session::forget($cartKey);

        // Fire event
        event(new CartUpdated(
            ['items' => [], 'subtotal' => 0, 'tax' => 0, 'total' => 0, 'item_count' => 0],
            Auth::id(),
            session()->getId()
        ));
    }

    /**
     * Get item count
     */
    public function getItemCount(): int
    {
        return count($this->getItems());
    }

    /**
     * Get total
     */
    public function getTotal(): float
    {
        $cartData = $this->getCartData();
        return $cartData['total'];
    }

    /**
     * Migrate guest cart to user cart on login
     */
    public function migrateGuestToUser(int $userId): void
    {
        $guestCartKey = 'a2_cart_guest';
        $userCartKey = "a2_cart_user_{$userId}";

        $guestCart = Session::get($guestCartKey, ['items' => []]);
        $userCart = Session::get($userCartKey, ['items' => []]);

        // Merge guest items into user cart
        foreach ($guestCart['items'] ?? [] as $guestItem) {
            $existingIndex = $this->findItemIndex(
                $userCart['items'] ?? [],
                $guestItem['product_id'],
                $guestItem['variation_id'] ?? null
            );

            if ($existingIndex !== false) {
                // Update quantity
                $userCart['items'][$existingIndex]['quantity'] += $guestItem['quantity'];
            } else {
                // Add new item
                $userCart['items'][] = $guestItem;
            }
        }

        Session::put($userCartKey, $userCart);
        Session::forget($guestCartKey);
    }

    /**
     * Find item index in cart
     */
    protected function findItemIndex(array $items, int $productId, ?int $variationId = null): int|false
    {
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $productId && ($item['variation_id'] ?? null) === $variationId) {
                return $index;
            }
        }
        return false;
    }

    /**
     * Get item key for identification
     */
    protected function getItemKey(int $productId, ?int $variationId = null): string
    {
        return "{$productId}_" . ($variationId ?? 'default');
    }
}
