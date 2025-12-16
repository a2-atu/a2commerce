<?php

namespace A2\A2Commerce\Providers;

use App\Events\A2\Commerce\CartItemRemoved;
use App\Events\A2\Commerce\CartUpdated;
use App\Events\A2\Commerce\ComparisonCleared;
use App\Events\A2\Commerce\ComparisonUpdated;
use App\Events\A2\Commerce\OrderCreated;
use App\Events\A2\Commerce\PaymentCompleted;
use App\Events\A2\Commerce\ProductAddedToComparison;
use App\Events\A2\Commerce\ProductAddedToWishlist;
use App\Events\A2\Commerce\ProductRemovedFromWishlist;
use App\Events\A2\Commerce\WishlistUpdated;
use App\Events\A2\Commerce\WishlistViewed;
use App\Listeners\A2\Commerce\CaptureReservedStock;
use App\Listeners\A2\Commerce\ClearCart;
use App\Listeners\A2\Commerce\GenerateOrderNumber;
use App\Listeners\A2\Commerce\MarkOrderPaid;
use App\Listeners\A2\Commerce\OrderNotificationAdmin;
use App\Listeners\A2\Commerce\OrderNotificationCustomer;
use App\Listeners\A2\Commerce\RebuildCartSnapshot;
use App\Listeners\A2\Commerce\RecalculateCartTotals;
use App\Listeners\A2\Commerce\RecordComparisonActivity;
use App\Listeners\A2\Commerce\RecordFinance;
use App\Listeners\A2\Commerce\RecordWishlistActivity;
use App\Listeners\A2\Commerce\ReleaseReservedStock;
use App\Listeners\A2\Commerce\ReserveStock;
use App\Listeners\A2\Commerce\SendPaymentConfirmationAdmin;
use App\Listeners\A2\Commerce\SendPaymentConfirmationCustomer;
use App\Listeners\A2\Commerce\SyncWishlistToUser;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class A2CommerceEventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Cart Events
        CartUpdated::class => [
            ReserveStock::class,
            RebuildCartSnapshot::class,
            RecalculateCartTotals::class,
        ],
        CartItemRemoved::class => [
            ReleaseReservedStock::class,
        ],

        // Wishlist Events
        WishlistUpdated::class => [
            RecordWishlistActivity::class,
        ],
        WishlistViewed::class => [
            SyncWishlistToUser::class,
        ],
        ProductAddedToWishlist::class => [
            RecordWishlistActivity::class,
        ],
        ProductRemovedFromWishlist::class => [
            RecordWishlistActivity::class,
        ],

        // Comparison Events
        ComparisonUpdated::class => [
            RecordComparisonActivity::class,
        ],
        ComparisonCleared::class => [
            RecordComparisonActivity::class,
        ],
        ProductAddedToComparison::class => [
            RecordComparisonActivity::class,
        ],

        // Order Events
        OrderCreated::class => [
            CaptureReservedStock::class,
            GenerateOrderNumber::class,
            OrderNotificationCustomer::class,
            OrderNotificationAdmin::class,
        ],

        // Payment Events
        PaymentCompleted::class => [
            MarkOrderPaid::class,
            RecordFinance::class,
            ClearCart::class,
            SendPaymentConfirmationCustomer::class,
            SendPaymentConfirmationAdmin::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

