<?php

namespace App\Services\A2\Commerce;

use App\Events\A2\Commerce\ComparisonCleared;
use App\Events\A2\Commerce\ComparisonUpdated;
use App\Events\A2\Commerce\ProductAddedToComparison;
use App\Models\A2\Commerce\A2ComparisonItem;
use App\Models\A2\Commerce\A2ComparisonSession;
use App\Models\A2\Commerce\A2Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComparisonService
{
    /**
     * Maximum products in comparison
     */
    protected const MAX_PRODUCTS = 10;

    /**
     * Get or create comparison session
     */
    protected function getOrCreateSession(): A2ComparisonSession
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Try to find existing active session
        $session = A2ComparisonSession::where('is_active', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$session) {
            // Create new session
            $session = A2ComparisonSession::create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'expires_at' => now()->addDays(7), // 7 days expiration
                'is_active' => true,
            ]);
        }

        return $session;
    }

    /**
     * Add product to comparison
     */
    public function add(int $productId): bool
    {
        try {
            $product = A2Product::find($productId);
            if (!$product || !$product->is_active) {
                return false;
            }

            $session = $this->getOrCreateSession();

            // Check if already in comparison
            $existing = A2ComparisonItem::where('comparison_session_id', $session->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                return true; // Already in comparison
            }

            // Check max products limit
            $itemCount = A2ComparisonItem::where('comparison_session_id', $session->id)->count();
            if ($itemCount >= self::MAX_PRODUCTS) {
                return false; // Max products reached
            }

            // Add to comparison
            $item = A2ComparisonItem::create([
                'comparison_session_id' => $session->id,
                'product_id' => $productId,
            ]);

            // Fire events
            event(new ProductAddedToComparison(
                $product,
                $session->id,
                $session->user_id,
                $session->session_id
            ));
            event(new ComparisonUpdated(
                'added',
                $productId,
                $session->id,
                $session->user_id,
                $session->session_id
            ));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add product to comparison: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove product from comparison
     */
    public function remove(int $productId): bool
    {
        try {
            $userId = Auth::id();
            $sessionId = session()->getId();

            $session = A2ComparisonSession::where('is_active', true)
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->when(!$userId, function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId)->whereNull('user_id');
                })
                ->first();

            if ($session) {
                $item = A2ComparisonItem::where('comparison_session_id', $session->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($item) {
                    $item->delete();

                    // Fire event
                    event(new ComparisonUpdated(
                        'removed',
                        $productId,
                        $session->id,
                        $session->user_id,
                        $session->session_id
                    ));

                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to remove product from comparison: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear comparison list
     */
    public function clear(): bool
    {
        try {
            $userId = Auth::id();
            $sessionId = session()->getId();

            $session = A2ComparisonSession::where('is_active', true)
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->when(!$userId, function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId)->whereNull('user_id');
                })
                ->first();

            if ($session) {
                A2ComparisonItem::where('comparison_session_id', $session->id)->delete();
                $session->update(['is_active' => false]);

                // Fire event
                event(new ComparisonCleared(
                    $session->id,
                    $session->user_id,
                    $session->session_id
                ));
                event(new ComparisonUpdated(
                    'cleared',
                    null,
                    $session->id,
                    $session->user_id,
                    $session->session_id
                ));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to clear comparison: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get comparison items
     */
    public function getItems(): \Illuminate\Database\Eloquent\Collection
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        // Ensure session is started for guests
        if (!$userId && !$sessionId) {
            session()->start();
            $sessionId = session()->getId();
        }

        $session = A2ComparisonSession::where('is_active', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId && $sessionId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$session) {
            return A2ComparisonItem::whereRaw('1 = 0')->get();
        }

        return A2ComparisonItem::with(['product', 'comparisonSession'])
            ->where('comparison_session_id', $session->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get comparison count
     */
    public function getCount(): int
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $session = A2ComparisonSession::where('is_active', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$session) {
            return 0;
        }

        return A2ComparisonItem::where('comparison_session_id', $session->id)->count();
    }

    /**
     * Check if product is in comparison
     */
    public function isInComparison(int $productId): bool
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        $session = A2ComparisonSession::where('is_active', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->first();

        if (!$session) {
            return false;
        }

        return A2ComparisonItem::where('comparison_session_id', $session->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Merge session comparison to user account
     */
    public function mergeSessionToUser(int $userId, string $sessionId): void
    {
        try {
            $sessionComparison = A2ComparisonSession::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->where('is_active', true)
                ->first();

            if ($sessionComparison) {
                // Update session to user account
                $sessionComparison->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to merge session comparison to user: ' . $e->getMessage());
        }
    }
}
