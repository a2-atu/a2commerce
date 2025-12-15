# A2 Commerce E-Commerce Implementation Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Event-Driven Architecture](#event-driven-architecture)
3. [Service Layer Pattern](#service-layer-pattern)
4. [Database Schema](#database-schema)
5. [Frontend Components (Livewire Volt)](#frontend-components-livewire-volt)
6. [Payment Integration](#payment-integration)
7. [Guest Checkout Flow](#guest-checkout-flow)
8. [Authentication & Authorization](#authentication--authorization)
9. [Route Organization](#route-organization)
10. [Key Patterns & Best Practices](#key-patterns--best-practices)
11. [Code Examples](#code-examples)

---

## Architecture Overview

The A2 Commerce implementation follows a **decoupled, event-driven architecture** with clear separation of concerns:

-   **Events**: Trigger actions (e.g., `CartUpdated`, `OrderCreated`)
-   **Listeners**: Handle business logic in response to events
-   **Services**: Encapsulate core business logic (Cart, Order, Payment, etc.)
-   **Models**: Eloquent models for database interaction
-   **Livewire Volt Components**: Frontend UI components with reactive state

### Key Principles

1. **Separation of Concerns**: Business logic in services, UI logic in components
2. **Event-Driven**: Actions trigger events, listeners respond asynchronously
3. **Service Layer**: All business operations go through dedicated services
4. **Guest Support**: Full e-commerce functionality without requiring authentication
5. **Session Management**: Guest sessions migrate to user accounts upon login

---

## Event-Driven Architecture

### Event-Listener Mapping

All events and listeners are registered in `app/Providers/AppServiceProvider.php`:

```php
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
    ],
];
```

### Available Events

**Cart Events:**

-   `CartUpdated` - Fired when cart items are added/updated
-   `CartItemRemoved` - Fired when an item is removed from cart

**Wishlist Events:**

-   `ProductAddedToWishlist` - Fired when product is added to wishlist
-   `ProductRemovedFromWishlist` - Fired when product is removed
-   `WishlistUpdated` - Fired when wishlist changes
-   `WishlistViewed` - Fired when user views wishlist

**Comparison Events:**

-   `ProductAddedToComparison` - Fired when product is added to comparison
-   `ComparisonUpdated` - Fired when comparison list changes
-   `ComparisonCleared` - Fired when comparison list is cleared
-   `ComparisonViewed` - Fired when user views comparison

**Order Events:**

-   `OrderCreated` - Fired when new order is created
-   `OrderStatusUpdated` - Fired when order status changes
-   `OrderCompleted` - Fired when order is completed

**Payment Events:**

-   `PaymentCompleted` - Fired when payment is successfully completed
-   `PaymentFailed` - Fired when payment fails

### Creating New Events

```php
// app/Events/A2/YourEvent.php
<?php

namespace App\Events\A2;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YourEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $data
    ) {}
}
```

### Creating New Listeners

```php
// app/Listeners/A2/YourListener.php
<?php

namespace App\Listeners\A2;

use App\Events\A2\YourEvent;
use Illuminate\Support\Facades\Log;

class YourListener
{
    public function handle(YourEvent $event): void
    {
        // Handle the event
        Log::info('Event handled', $event->data);
    }
}
```

---

## Service Layer Pattern

### Core Services

All services are located in `app/Services/A2/`:

1. **CartService** - Manages shopping cart operations
2. **WishlistService** - Manages wishlist operations
3. **ComparisonService** - Manages product comparison
4. **OrderService** - Handles order creation and management
5. **PaymentService** - Payment gateway abstraction
6. **PayPalPaymentService** - PayPal-specific implementation

### Service Usage Pattern

```php
// In Livewire Volt components or controllers
use App\Services\A2\CartService;

public function addToCart(int $productId, int $quantity = 1): void
{
    $cartService = app(CartService::class);
    $success = $cartService->add($productId, $quantity);

    if ($success) {
        $this->dispatch('product-added-to-cart', [
            'productId' => $productId,
            'quantity' => $quantity,
        ]);
    }
}
```

### CartService Methods

```php
// Add item to cart
$cartService->add(int $productId, int $quantity = 1, ?int $variationId = null): bool

// Update item quantity
$cartService->update(int $productId, int $quantity, ?int $variationId = null): bool

// Remove item from cart
$cartService->remove(int $productId, ?int $variationId = null): bool

// Clear entire cart
$cartService->clear(): bool

// Get cart items
$cartService->getItems(): array

// Get cart data with totals
$cartService->getCartData(): array
```

### OrderService Methods

```php
// Create order from cart
$orderService->createFromCart(
    array $addressData,
    ?string $paymentMethod = null
): ?A2Order

// Generate order number
$orderService->generateOrderNumber(): string
```

### Session Management

Services handle both guest and authenticated users:

```php
// CartService automatically handles:
// - Guest: Uses session key 'a2_cart_guest'
// - Authenticated: Uses session key 'a2_cart_user_{userId}'

// When user logs in, migrate guest cart to user cart
// This is handled automatically by the service
```

---

## Database Schema

### Key Tables

**Products:**

-   `a2_ec_products` - Main product table
-   `a2_ec_product_variations` - Product variations
-   `a2_ec_product_taxonomies` - Product-category relationships

**Orders:**

-   `a2_ec_orders` - Order header (user_id nullable for guests)
-   `a2_ec_order_items` - Order line items
-   `a2_ec_order_address` - Shipping/billing addresses (includes guest contact info)
-   `a2_ec_order_finance` - Financial records
-   `a2_ec_order_action_log` - Order activity log

**Cart & Wishlist:**

-   `a2_ec_reserved_stock` - Reserved stock for cart items
-   `a2_ec_wishlist` - User wishlist items

**Comparison:**

-   `a2_ec_comparison_sessions` - Comparison session tracking
-   `a2_ec_comparison_items` - Items in comparison list

**Payments:**

-   `a2_ec_payments` - Payment records

### Guest Checkout Schema

**Important:** `a2_ec_orders.user_id` is **nullable** to support guest checkout.

Guest contact information is stored in `a2_ec_order_address`:

-   `first_name`
-   `last_name`
-   `email`
-   `phone`

### Model Relationships

```php
// A2Product
public function variations()
public function productTaxonomies()
public function meta() // Via A2ProductMeta
public function slugs() // Via HasSlugs trait

// A2Order
public function user() // Nullable for guests
public function items()
public function addresses()
public function payments()

// A2OrderAddress
public function order()
```

---

## Frontend Components (Livewire Volt)

### Component Structure

All frontend components use **Livewire Volt** and are located in `resources/views/livewire/front/`:

```
front/
├── cart/
│   └── index.blade.php          # Shopping cart view
├── checkout/
│   └── index.blade.php          # Checkout form
├── product/
│   └── show.blade.php           # Single product view
├── products/
│   └── index.blade.php          # Products listing
├── favorites/
│   └── index.blade.php          # Wishlist view
├── compare/
│   └── index.blade.php          # Comparison view
├── account/
│   ├── index.blade.php          # Account dashboard
│   ├── orders.blade.php          # Order list
│   └── order-detail.blade.php   # Order details
├── auth/
│   ├── login.blade.php          # Buyer login
│   └── register.blade.php      # Buyer registration
├── payment/
│   └── paypal.blade.php         # PayPal payment page
└── order/
    └── guest-show.blade.php     # Guest order view
```

### Component Pattern

```php
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\A2\CartService;

new #[Layout('components.layouts.landing')] class extends Component {
    public array $items = [];

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $cartService = app(CartService::class);
        $cartData = $cartService->getCartData();
        $this->items = $cartData['items'];
    }

    public function removeItem(int $productId): void
    {
        $cartService = app(CartService::class);
        $cartService->remove($productId);
        $this->loadCart();
    }
}; ?>

<div>
    <!-- Component HTML -->
</div>
```

### Layout Usage

**Landing Layout** (`components.layouts.landing`):

-   Used for all public-facing buyer pages
-   Includes navigation, footer, and common UI elements

**Admin Layout** (`components.layouts.app`):

-   Used for admin/authenticated pages
-   Different navigation structure

### Event Dispatching

Components dispatch Livewire events for UI feedback:

```php
// Dispatch event after adding to cart
$this->dispatch('product-added-to-cart', [
    'productId' => $productId,
    'quantity' => $quantity,
    'product' => [
        'name' => $product->name,
        'price' => $product->price,
    ],
]);
```

**JavaScript listeners** (using SweetAlert2):

```javascript
window.addEventListener("product-added-to-cart", (event) => {
    const { productId, quantity, product } = event.detail;

    Swal.fire({
        icon: "success",
        title: "Added to Cart!",
        text: `${product.name} (${quantity}x) has been added to your cart.`,
        timer: 3000,
    });
});
```

---

## Payment Integration

### PayPal Integration

**Configuration** (`config/a2.php`):

```php
'paypal' => [
    'client_id' => env('A2_PAYPAL_CLIENT_ID'),
    'secret' => env('A2_PAYPAL_SECRET'),
    'mode' => env('A2_PAYPAL_MODE', 'sandbox'),
    'webhook_id' => env('A2_PAYPAL_WEBHOOK_ID'),
],
'currency_conversion_rate' => env('A2_CURRENCY_CONVERSION_RATE', 100),
```

### Payment Flow

1. **Order Creation** → User completes checkout
2. **Payment Initiation** → Redirect to PayPal payment page
3. **PayPal SDK** → User approves payment in PayPal
4. **Payment Confirmation** → Backend verifies payment via PayPal API
5. **Payment Completion** → Fire `PaymentCompleted` event
6. **Order Update** → Mark order as paid, clear cart

### Payment Methods

**Supported Methods:**

-   `paypal` - PayPal JavaScript SDK integration
-   `payment_on_delivery` - Cash on delivery (payment_status = 'unpaid')

### Payment Controller

```php
// app/Http/Controllers/A2/PaymentController.php

// Initialize PayPal payment
public function initPayPal(Request $request, ?int $orderId = null)

// Confirm PayPal payment (from JavaScript callback)
public function confirmPayPal(Request $request)

// Handle PayPal webhooks
public function webhookPayPal(Request $request)

// Payment success redirect
public function success(Request $request)

// Payment failure redirect
public function failed(Request $request)
```

### PayPal Frontend Integration

```javascript
// resources/views/livewire/front/payment/paypal.blade.php

paypal
    .Buttons({
        createOrder: function (data, actions) {
            return actions.order.create({
                purchase_units: [
                    {
                        amount: {
                            value: usdAmount, // Converted from local currency
                            currency_code: "USD",
                        },
                    },
                ],
            });
        },
        onApprove: function (data, actions) {
            return actions.order.capture().then(function (details) {
                // Call backend to confirm payment
                fetch("/a2/payment/paypal/confirm", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        paypal_order_id: data.orderID,
                    }),
                });
            });
        },
    })
    .render("#paypal-button-container");
```

### Payment Verification

```php
// app/Services/A2/PayPalPaymentService.php

public function verifyOrder(string $paypalOrderId): array
{
    // Call PayPal API to verify order
    // Returns ['success' => bool, 'data' => array, 'error' => string]
}
```

---

## Guest Checkout Flow

### Implementation Details

**1. Checkout Page** (`front.checkout.index`):

-   No authentication required
-   Collects guest contact info: `first_name`, `last_name`, `email`, `phone`
-   Payment method selection: PayPal or Payment on Delivery

**2. Order Creation**:

```php
// OrderService creates order with nullable user_id
$order = A2Order::create([
    'user_id' => Auth::id(), // null for guests
    'order_number' => $orderNumber,
    'status' => 'pending',
    'payment_status' => 'unpaid', // or 'paid' for PayPal
    'payment_method' => $paymentMethod,
]);

// Guest contact info stored in order address
A2OrderAddress::create([
    'order_id' => $order->id,
    'type' => 'shipping',
    'first_name' => $addressData['first_name'],
    'last_name' => $addressData['last_name'],
    'email' => $addressData['email'],
    'phone' => $addressData['phone'],
    // ... other address fields
]);
```

**3. Guest Order Viewing**:

-   Route: `order.guest.show` (`/order/{orderNumber}`)
-   Requires: `order_number` and `email` (as query parameter)
-   Verifies: Order exists and email matches

```php
// resources/views/livewire/front/order/guest-show.blade.php

public function mount(string $orderNumber): void
{
    $email = request()->query('email');

    $this->order = A2Order::where('order_number', $orderNumber)
        ->whereHas('addresses', function($q) use ($email) {
            $q->where('email', $email);
        })
        ->first();
}
```

**4. Payment Redirects**:

-   Authenticated users → `account.orders.show`
-   Guest users → `order.guest.show` with email query param

---

## Authentication & Authorization

### Buyer Authentication

**Separate from Admin Auth:**

-   Admin login: `/login` (uses `auth` middleware)
-   Buyer login: `/signin` (uses `guest` middleware)

**Buyer Registration:**

-   Route: `/signup` (`front.register`)
-   Assigns role ID `3` to new buyers
-   Uses `components.layouts.landing` layout

### Middleware

**BuyerAuth Middleware** (`app/Http/Middleware/BuyerAuth.php`):

```php
public function handle(Request $request, Closure $next): Response
{
    if (!auth()->check()) {
        return redirect()->route('front.login');
    }

    return $next($request);
}
```

**Registration** (`bootstrap/app.php`):

```php
$middleware->alias([
    'buyer-auth' => \App\Http\Middleware\BuyerAuth::class,
]);
```

**Route Protection**:

```php
// Protected buyer routes
Route::middleware('buyer-auth')->group(function () {
    Volt::route('favorites', 'front.favorites.index')->name('favorites.index');
    Volt::route('compare-list', 'front.compare.index')->name('compare.index');
    Volt::route('account', 'front.account.index')->name('account.index');
    Volt::route('account/orders', 'front.account.orders')->name('account.orders.index');
    Volt::route('account/orders/{orderNumber}', 'front.account.order-detail')->name('account.orders.show');
});
```

### Session Migration

When a guest user logs in:

-   Guest cart → User cart (automatic via CartService)
-   Guest wishlist → User wishlist (via WishlistService)
-   Guest comparison → User comparison (via ComparisonService)

---

## Route Organization

### Route Structure

```php
// Public storefront pages
Volt::route('category/{slug}', 'front.category.show')->name('category.show');
Volt::route('product/{slug}', 'front.product.show')->name('product.show');
Volt::route('products', 'front.products.index')->name('products');
Volt::route('search', 'front.search.results')->name('search.results');

// Cart and Checkout (public)
Volt::route('cart', 'front.cart.index')->name('cart.index');
Volt::route('checkout', 'front.checkout.index')->name('checkout.index');

// Buyer Authentication (guest middleware)
Route::middleware('guest')->group(function () {
    Volt::route('signin', 'front.auth.login')->name('front.login');
    Volt::route('signup', 'front.auth.register')->name('front.register');
});

// Buyer Account (buyer-auth middleware)
Route::middleware('buyer-auth')->group(function () {
    Volt::route('favorites', 'front.favorites.index')->name('favorites.index');
    Volt::route('compare-list', 'front.compare.index')->name('compare.index');
    Volt::route('account', 'front.account.index')->name('account.index');
    Volt::route('account/orders', 'front.account.orders')->name('account.orders.index');
    Volt::route('account/orders/{orderNumber}', 'front.account.order-detail')->name('account.orders.show');
});

// Guest Order View (public)
Volt::route('order/{orderNumber}', 'front.order.guest-show')->name('order.guest.show');

// Payment routes
Route::prefix('a2/payment')->group(function () {
    Volt::route('/paypal/{orderId}', 'front.payment.paypal')->name('payment.paypal');
    Route::post('/paypal/init', [PaymentController::class, 'initPayPal'])->name('payment.paypal.init');
    Route::post('/paypal/confirm', [PaymentController::class, 'confirmPayPal'])->name('payment.paypal.confirm');
    Route::get('/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/failed', [PaymentController::class, 'failed'])->name('payment.failed');
});
```

### Route Naming Convention

-   **Frontend routes**: `front.{feature}.{action}` or `{feature}.{action}`
-   **Payment routes**: `payment.{method}.{action}`
-   **Account routes**: `account.{feature}.{action}`

**Always use route names** instead of hardcoded URLs:

```php
// ✅ Correct
<a href="{{ route('product.show', $product->getSlug()) }}">

// ❌ Wrong
<a href="/product/{{ $product->getSlug() }}">
```

---

## Key Patterns & Best Practices

### 1. Service Injection

```php
// ✅ Preferred: Use app() helper or dependency injection
$cartService = app(CartService::class);

// ✅ Also valid: Constructor injection in controllers
public function __construct(private CartService $cartService) {}
```

### 2. Event Dispatching

```php
// ✅ Fire events from services
event(new CartUpdated($cartData));

// ✅ Dispatch Livewire events from components
$this->dispatch('product-added-to-cart', ['productId' => $id]);
```

### 3. Error Handling

```php
try {
    $order = $orderService->createFromCart($addressData);
    if (!$order) {
        throw new \Exception('Failed to create order');
    }
} catch (\Exception $e) {
    Log::error('Order creation failed: ' . $e->getMessage());
    return back()->with('error', 'Failed to create order');
}
```

### 4. Database Transactions

```php
DB::beginTransaction();
try {
    // Multiple database operations
    $order = A2Order::create([...]);
    foreach ($items as $item) {
        A2OrderItem::create([...]);
    }
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 5. Guest vs Authenticated User Handling

```php
// ✅ Check authentication status
$userId = Auth::id(); // null for guests

// ✅ Handle both cases
if ($userId) {
    // Authenticated user logic
} else {
    // Guest user logic
}
```

### 6. Meta Data Access

```php
// ✅ Use getMeta() method (from Vormia traits)
$image = $product->getMeta('image');
$price = $product->getMeta('sale_price') ?: $product->price;

// ✅ With default value
$brand = $product->getMeta('manufacturer', 'N/A');
```

### 7. Slug Usage

```php
// ✅ Always use getSlug() method (from HasSlugs trait)
$slug = $product->getSlug();

// ✅ In routes
route('product.show', $slug)
```

### 8. Livewire Volt Redirects

```php
// ✅ Use $this->redirect() for Livewire Volt
return $this->redirect(route('checkout.index'), navigate: true);

// ❌ Don't use return redirect() in void methods
```

---

## Code Examples

### Complete Product Listing Component

```php
<?php

use App\Models\A2\A2Product;
use App\Models\Vrm\Taxonomy;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.landing')] class extends Component {
    public array $products = [];
    public string $sortBy = 'relevance';
    public ?string $categorySlug = null;
    public int $totalProducts = 0;

    public function mount(?string $category = null): void
    {
        $this->categorySlug = $category ?? request()->query('category');
        $this->sortBy = request()->query('sort', 'relevance');
        $this->loadProducts();
    }

    public function loadProducts(): void
    {
        $query = A2Product::where('is_active', true)
            ->with(['meta', 'variations', 'slugs', 'productTaxonomies']);

        // Filter by category
        if ($this->categorySlug) {
            $category = Taxonomy::findBySlug($this->categorySlug);
            if ($category) {
                $query->whereHas('productTaxonomies', function ($q) use ($category) {
                    $q->where('type', 'category')
                      ->where('taxonomy_id', $category->id);
                });
            }
        }

        // Apply sorting
        match($this->sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'asc'),
        };

        $products = $query->get();
        $this->totalProducts = $products->count();

        $this->products = $products->map(function ($product) {
            $_thumb = $product->getMeta('main_image') ?: '/media/default_thumb.png';
            return [
                'id' => $product->id,
                'slug' => $product->getSlug(),
                'name' => $product->name,
                'image' => asset($_thumb),
                'price' => $product->getMeta('sale_price') ?: $product->price,
            ];
        })->toArray();
    }
}; ?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach ($products as $product)
            <a href="{{ route('product.show', $product['slug']) }}">
                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                <h3>{{ $product['name'] }}</h3>
                <p>KES {{ number_format($product['price'], 2) }}</p>
            </a>
        @endforeach
    </div>
</div>
```

### Complete Checkout Component

```php
<?php

use App\Services\A2\CartService;
use App\Services\A2\OrderService;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.landing')] class extends Component {
    public array $cartData = [];
    public array $formData = [];
    public string $paymentMethod = 'payment_on_delivery';

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $cartService = app(CartService::class);
        $this->cartData = $cartService->getCartData();

        if (empty($this->cartData['items'])) {
            return $this->redirect(route('cart.index'), navigate: true);
        }
    }

    public function placeOrder(): void
    {
        $this->validate([
            'formData.first_name' => 'required|string|max:255',
            'formData.last_name' => 'required|string|max:255',
            'formData.email' => 'required|email|max:255',
            'formData.phone' => 'required|string|max:20',
            'formData.address' => 'required|string|max:500',
            'formData.city' => 'required|string|max:100',
            'formData.postal_code' => 'nullable|string|max:20',
            'paymentMethod' => 'required|in:paypal,payment_on_delivery',
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($this->formData, $this->paymentMethod);

        if (!$order) {
            session()->flash('error', 'Failed to create order');
            return;
        }

        // Redirect based on payment method
        if ($this->paymentMethod === 'paypal') {
            $this->redirect(route('payment.paypal', $order->id), navigate: true);
        } else {
            // Payment on delivery - redirect to order confirmation
            if (auth()->check()) {
                $this->redirect(route('account.orders.show', $order->order_number), navigate: true);
            } else {
                $this->redirect(route('order.guest.show', $order->order_number) . '?email=' . urlencode($this->formData['email']), navigate: true);
            }
        }
    }
}; ?>

<div>
    <form wire:submit="placeOrder">
        <!-- Form fields -->
        <input type="text" wire:model="formData.first_name">
        <input type="text" wire:model="formData.last_name">
        <input type="email" wire:model="formData.email">
        <input type="tel" wire:model="formData.phone">

        <!-- Payment method selection -->
        <select wire:model="paymentMethod">
            <option value="payment_on_delivery">Payment on Delivery</option>
            <option value="paypal">PayPal</option>
        </select>

        <button type="submit">Place Order</button>
    </form>
</div>
```

### Complete Cart Service Usage

```php
<?php

use App\Services\A2\CartService;
use Livewire\Volt\Component;

new class extends Component {
    public array $cartItems = [];
    public float $total = 0;

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $cartService = app(CartService::class);
        $cartData = $cartService->getCartData();

        $this->cartItems = $cartData['items'];
        $this->total = $cartData['total'];
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $cartService = app(CartService::class);
        $cartService->update($productId, $quantity);
        $this->loadCart();
    }

    public function removeItem(int $productId): void
    {
        $cartService = app(CartService::class);
        $cartService->remove($productId);
        $this->loadCart();
    }

    public function clearCart(): void
    {
        $cartService = app(CartService::class);
        $cartService->clear();
        $this->loadCart();
    }
}; ?>
```

---

## Testing Checklist

### Cart Functionality

-   [ ] Add product to cart
-   [ ] Update item quantity
-   [ ] Remove item from cart
-   [ ] Clear entire cart
-   [ ] Cart persists across page reloads
-   [ ] Guest cart migrates to user cart on login

### Checkout Flow

-   [ ] Guest checkout works without login
-   [ ] Authenticated checkout works
-   [ ] Payment on delivery creates order with 'unpaid' status
-   [ ] PayPal payment redirects correctly
-   [ ] Order confirmation displays correctly

### Payment Integration

-   [ ] PayPal payment initializes correctly
-   [ ] PayPal payment confirmation works
-   [ ] Payment status updates correctly
-   [ ] Order status updates after payment
-   [ ] Cart clears after successful payment

### Guest Order Viewing

-   [ ] Guest can view order with order number and email
-   [ ] Invalid email shows error
-   [ ] Order details display correctly

### Authentication

-   [ ] Buyer login redirects correctly
-   [ ] Buyer registration assigns correct role
-   [ ] Protected routes redirect to login
-   [ ] Guest routes accessible without login

---

## Environment Variables

Add these to your `.env` file:

```env
# A2 Commerce Configuration
A2_ORDER_PREFIX=ORD
A2_TAX_RATE=0.20
A2_SHIPPING_FEE=0
A2_CURRENCY=KES
A2_CURRENCY_SYMBOL=KSh
A2_CURRENCY_CONVERSION_RATE=100

# PayPal Configuration
A2_PAYPAL_CLIENT_ID=your_paypal_client_id
A2_PAYPAL_SECRET=your_paypal_secret
A2_PAYPAL_MODE=sandbox
A2_PAYPAL_WEBHOOK_ID=your_webhook_id
```

---

## Common Issues & Solutions

### Issue: Cart not persisting

**Solution:** Ensure session driver is configured correctly in `config/session.php`

### Issue: Payment redirect fails

**Solution:** Check that routes are properly defined and middleware is applied correctly

### Issue: Guest order not found

**Solution:** Verify email is passed as query parameter and matches order address email

### Issue: Stock not reserving

**Solution:** Ensure `ReserveStock` listener is registered in `AppServiceProvider`

### Issue: Events not firing

**Solution:** Verify events and listeners are registered in `AppServiceProvider::$listen`

---

## Next Steps for New Projects

1. **Copy Service Layer**: Copy all services from `app/Services/A2/`
2. **Copy Events/Listeners**: Copy all events and listeners, register in `AppServiceProvider`
3. **Copy Models**: Copy all A2 models and ensure relationships are correct
4. **Copy Components**: Copy Livewire Volt components from `resources/views/livewire/front/`
5. **Copy Routes**: Copy route definitions from `routes/web.php`
6. **Copy Middleware**: Copy `BuyerAuth` middleware and register in `bootstrap/app.php`
7. **Configure**: Set up `config/a2.php` and environment variables
8. **Database**: Run migrations for all A2 Commerce tables
9. **Test**: Follow the testing checklist above

---

## Additional Resources

-   **Vormia Package Rules**: See `vormiaphp.mdc` for Vormia-specific patterns
-   **Database Schema**: See `a_2_commerce_schema.md` for complete schema documentation
-   **Event Flow**: See `a_2_event_flow.md` for detailed event flow diagrams
-   **Payment Guide**: See `a_2_payment_guide.md` for PayPal integration details

---

**Last Updated**: 2025-01-XX
**Version**: 1.0.0
