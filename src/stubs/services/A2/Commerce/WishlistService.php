<?php

namespace App\Services\A2\Commerce;

use App\Events\A2\Commerce\ProductAddedToWishlist;
use App\Events\A2\Commerce\ProductRemovedFromWishlist;
use App\Events\A2\Commerce\WishlistUpdated;
use App\Models\A2\Commerce\A2Product;
use App\Models\A2\Commerce\A2Wishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class WishlistService
{
    /**
     * Add product to wishlist
     */
    public function add(int $productId): bool
    {
        try {
            $product = A2Product::find($productId);
            if (!$product || !$product->is_active) {
                return false;
            }

            $userId = Auth::id();
            $sessionId = $userId ? null : session()->getId();

            // Check if already in wishlist
            $existing = A2Wishlist::where('product_id', $productId)
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->when(!$userId, function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId)->whereNull('user_id');
                })
                ->first();

            if ($existing) {
                return true; // Already in wishlist
            }

            // Create wishlist entry
            $wishlist = A2Wishlist::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $productId,
            ]);

            // Fire events
            event(new ProductAddedToWishlist($product, $userId, $sessionId));
            event(new WishlistUpdated('added', $productId, $userId, $sessionId));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add product to wishlist: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove product from wishlist
     */
    public function remove(int $productId): bool
    {
        try {
            $userId = Auth::id();
            $sessionId = $userId ? null : session()->getId();

            $wishlist = A2Wishlist::where('product_id', $productId)
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->when(!$userId, function ($query) use ($sessionId) {
                    $query->where('session_id', $sessionId)->whereNull('user_id');
                })
                ->first();

            if ($wishlist) {
                $product = $wishlist->product;
                $wishlist->delete();

                // Fire events
                event(new ProductRemovedFromWishlist($product, $userId, $sessionId));
                event(new WishlistUpdated('removed', $productId, $userId, $sessionId));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to remove product from wishlist: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if product is in wishlist
     */
    public function isInWishlist(int $productId): bool
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : session()->getId();

        return A2Wishlist::where('product_id', $productId)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->exists();
    }

    /**
     * Get wishlist items
     */
    public function getItems(): \Illuminate\Database\Eloquent\Collection
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : session()->getId();

        return A2Wishlist::with(['product'])
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get wishlist count
     */
    public function getCount(): int
    {
        $userId = Auth::id();
        $sessionId = $userId ? null : session()->getId();

        return A2Wishlist::when($userId, function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->when(!$userId, function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)->whereNull('user_id');
            })
            ->count();
    }

    /**
     * Merge session wishlist to user account
     */
    public function mergeSessionToUser(int $userId, string $sessionId): void
    {
        try {
            $sessionWishlist = A2Wishlist::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->get();

            foreach ($sessionWishlist as $item) {
                // Check if user already has this product
                $existing = A2Wishlist::where('user_id', $userId)
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($existing) {
                    // Delete duplicate session item
                    $item->delete();
                } else {
                    // Migrate to user account
                    $item->update([
                        'user_id' => $userId,
                        'session_id' => null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to merge session wishlist to user: ' . $e->getMessage());
        }
    }
}
