<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\WishlistViewed;
use App\Models\A2\Commerce\A2Wishlist;
use Illuminate\Support\Facades\Log;

class SyncWishlistToUser
{
    /**
     * Handle the event.
     */
    public function handle(WishlistViewed $event): void
    {
        try {
            // Only sync if user is logged in and has session wishlist items
            if ($event->userId && $event->sessionId) {
                $sessionWishlist = A2Wishlist::where('session_id', $event->sessionId)
                    ->whereNull('user_id')
                    ->get();

                foreach ($sessionWishlist as $item) {
                    // Check if user already has this product in wishlist
                    $existing = A2Wishlist::where('user_id', $event->userId)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if (!$existing) {
                        // Migrate session wishlist to user
                        $item->update([
                            'user_id' => $event->userId,
                            'session_id' => null,
                        ]);
                    } else {
                        // Delete duplicate session item
                        $item->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync wishlist to user: ' . $e->getMessage());
        }
    }
}
