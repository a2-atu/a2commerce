<?php

namespace App\Listeners\A2\Commerce;

use Illuminate\Support\Facades\Log;

class RecordComparisonActivity
{
    /**
     * Handle the event.
     * Supports multiple event types: ComparisonUpdated, ComparisonCleared, ProductAddedToComparison
     */
    public function handle($event): void
    {
        try {
            // Extract action - may not exist on all event types
            $action = property_exists($event, 'action') ? $event->action : 'unknown';
            
            // Extract productId - may come from productId property or product object
            $productId = null;
            if (property_exists($event, 'productId')) {
                $productId = $event->productId;
            } elseif (property_exists($event, 'product') && isset($event->product->id)) {
                $productId = $event->product->id;
            }
            
            // Extract comparisonSessionId - may not exist on all event types
            $comparisonSessionId = property_exists($event, 'comparisonSessionId') 
                ? $event->comparisonSessionId 
                : null;
            
            // Extract userId and sessionId - exist on all event types
            $userId = property_exists($event, 'userId') ? $event->userId : null;
            $sessionId = property_exists($event, 'sessionId') ? $event->sessionId : null;
            
            // TODO: Log comparison activity for analytics
            Log::info('Comparison activity', [
                'action' => $action,
                'product_id' => $productId,
                'comparison_session_id' => $comparisonSessionId,
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record comparison activity: ' . $e->getMessage());
        }
    }
}
