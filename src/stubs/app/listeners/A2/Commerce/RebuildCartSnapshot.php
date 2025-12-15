<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\CartUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RebuildCartSnapshot
{
    /**
     * Handle the event.
     */
    public function handle(CartUpdated $event): void
    {
        try {
            $cacheKey = 'cart_snapshot_' . ($event->userId ?? $event->sessionId ?? session()->getId());

            // Cache cart snapshot for 5 minutes
            Cache::put($cacheKey, $event->cartData, now()->addMinutes(5));
        } catch (\Exception $e) {
            Log::error('Failed to rebuild cart snapshot: ' . $e->getMessage());
        }
    }
}
