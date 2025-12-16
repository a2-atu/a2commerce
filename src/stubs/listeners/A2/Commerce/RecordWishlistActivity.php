<?php

namespace App\Listeners\A2\Commerce;

use Illuminate\Support\Facades\Log;

class RecordWishlistActivity
{
    /**
     * Handle the event.
     * Supports multiple event types: WishlistUpdated, ProductAddedToWishlist, ProductRemovedFromWishlist
     */
    public function handle($event): void
    {
        try {
            // Extract action - may not exist on all event types, derive from event class name if needed
            $action = null;
            if (property_exists($event, 'action')) {
                $action = $event->action;
            } else {
                // Derive action from event class name
                $className = get_class($event);
                if (str_contains($className, 'ProductAddedToWishlist')) {
                    $action = 'added';
                } elseif (str_contains($className, 'ProductRemovedFromWishlist')) {
                    $action = 'removed';
                } else {
                    $action = 'updated';
                }
            }
            
            // Extract productId - may come from productId property or product object
            $productId = null;
            if (property_exists($event, 'productId')) {
                $productId = $event->productId;
            } elseif (property_exists($event, 'product') && isset($event->product->id)) {
                $productId = $event->product->id;
            }
            
            // Extract userId and sessionId - exist on all event types
            $userId = property_exists($event, 'userId') ? $event->userId : null;
            $sessionId = property_exists($event, 'sessionId') ? $event->sessionId : null;
            
            // TODO: Log wishlist activity for analytics
            // This can be stored in a2_ec_action_log or a separate analytics table
            Log::info('Wishlist activity', [
                'action' => $action,
                'product_id' => $productId,
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record wishlist activity: ' . $e->getMessage());
        }
    }
}
