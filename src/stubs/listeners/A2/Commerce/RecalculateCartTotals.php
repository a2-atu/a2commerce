<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\CartUpdated;
use Illuminate\Support\Facades\Log;

class RecalculateCartTotals
{
    /**
     * Handle the event.
     */
    public function handle(CartUpdated $event): void
    {
        try {
            // Totals are calculated in CartService
            // This listener can be used for additional calculations or logging
            // For now, it's a placeholder for future enhancements
        } catch (\Exception $e) {
            Log::error('Failed to recalculate cart totals: ' . $e->getMessage());
        }
    }
}
