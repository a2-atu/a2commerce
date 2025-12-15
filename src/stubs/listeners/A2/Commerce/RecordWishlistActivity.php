<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\WishlistUpdated;
use Illuminate\Support\Facades\Log;

class RecordWishlistActivity
{
    /**
     * Handle the event.
     */
    public function handle(WishlistUpdated $event): void
    {
        try {
            // TODO: Log wishlist activity for analytics
            // This can be stored in a2_ec_action_log or a separate analytics table
            Log::info('Wishlist activity', [
                'action' => $event->action,
                'product_id' => $event->productId,
                'user_id' => $event->userId,
                'session_id' => $event->sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record wishlist activity: ' . $e->getMessage());
        }
    }
}
