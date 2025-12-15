<?php

namespace App\Listeners\A2\Commerce;

use App\Events\A2\Commerce\ComparisonUpdated;
use Illuminate\Support\Facades\Log;

class RecordComparisonActivity
{
    /**
     * Handle the event.
     */
    public function handle(ComparisonUpdated $event): void
    {
        try {
            // TODO: Log comparison activity for analytics
            Log::info('Comparison activity', [
                'action' => $event->action,
                'product_id' => $event->productId,
                'comparison_session_id' => $event->comparisonSessionId,
                'user_id' => $event->userId,
                'session_id' => $event->sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record comparison activity: ' . $e->getMessage());
        }
    }
}
