# A2 Commerce

Laravel 12 package for a decoupled, event-driven e-commerce stack (cart, checkout, PayPal, guest checkout, wishlist/compare) built with Vormia and Livewire Volt.

## Introduction

A2Commerce is a comprehensive Laravel e-commerce package that provides a complete, decoupled shopping experience with cart management, checkout flows, PayPal integration, and guest checkout support. Built on top of Vormia and Livewire Volt, it offers a modular, event-driven architecture that separates concerns through dedicated services, events, and listeners.

A2Commerce provides robust tools for managing shopping carts, processing payments, handling guest checkouts, and implementing wishlist and comparison features. The package is designed with a service-oriented structure, separating business logic through dedicated service classes, event-driven workflows, and Livewire Volt components for the storefront.

## Dependencies

### Required Dependencies

- **vormiaphp/vormia**: Required for core functionality and database structure

  - Used for user management, taxonomies, and meta data handling
  - Install with: `composer require vormiaphp/vormia`
  - See [Vormia documentation](https://github.com/vormiaphp/vormia) for installation instructions

- **livewire/volt**: Required for storefront components

  - Used for reactive cart, checkout, and product pages
  - Install with: `composer require livewire/volt`

- **PayPal API**: Required for payment processing
  - Sandbox credentials required for development
  - Live credentials required for production
  - Obtain credentials from [PayPal Developer Dashboard](https://developer.paypal.com/)

The package will automatically check for required dependencies during installation and provide helpful error messages if they're missing.

## Features

- **Shopping Cart Management** - Full cart functionality with guest and authenticated user support
- **Checkout Flow** - Complete checkout process with guest checkout capability
- **PayPal Integration** - Built-in PayPal payment processing with webhook support
- **Wishlist & Comparison** - Product wishlist and comparison features
- **Event-Driven Architecture** - Decoupled system using Laravel events and listeners
- **Guest Checkout** - Allow customers to checkout without creating an account
- **Livewire Volt Storefront** - Reactive frontend components for cart, checkout, and product pages
- **Service Layer** - Clean separation of business logic through dedicated service classes
- **Database Schema** - Complete schema for products, variations, orders, payments, and more

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vormia 4.2+ (must be installed first)
- Livewire Volt 1.x
- PayPal Developer Account (for sandbox/live credentials)

## Installation

Before installing A2Commerce, ensure you have Laravel and Vormia installed.

### Step 1: Install Laravel

```sh
composer create-project laravel/laravel myproject
cd myproject
```

### OR Using Laravel Installer

```sh
laravel new myproject
cd myproject
```

### Step 2: Install Vormia

A2Commerce requires Vormia to be installed first. Follow the [Vormia installation guide](https://github.com/vormiaphp/vormia) to install Vormia:

```sh
composer require vormiaphp/vormia
php artisan vormia:install
```

### Step 3: Install A2Commerce

```sh
composer require a2-atu/a2commerce
```

### Step 4: Run A2Commerce Installation

```sh
php artisan a2commerce:install
```

This will automatically install A2Commerce with all files and configurations:

**Automatically Installed:**

- ✅ All A2Commerce files and directories (services, controllers, models, events, listeners, jobs, notifications)
- ✅ All migrations copied to `database/migrations`
- ✅ All Livewire Volt views copied to `resources/views/livewire/front`
- ✅ PayPal webhook route added to `routes/api.php`
- ✅ Environment variables added to `.env` and `.env.example`

**Installation Options:**

- `--no-overwrite`: Keep existing files instead of replacing them
- `--skip-env`: Leave `.env` files untouched

**Example:**

```sh
# Install without overwriting existing files
php artisan a2commerce:install --no-overwrite

# Install without modifying .env files
php artisan a2commerce:install --skip-env
```

### Step 5: Configure Environment Variables

Review and configure the environment variables added to your `.env` file:

```env
# A2 CONFIGURATION
A2_PAYPAL_MODE=sandbox                    # PayPal environment: sandbox or live
A2_PAYPAL_SECRET=                         # PayPal API secret key
A2_PAYPAL_CLIENT_ID=                      # PayPal API client ID
A2_PAYPAL_WEBHOOK_ID=                     # PayPal webhook ID
A2_ORDER_PREFIX="SP-OD"                   # Order number prefix
A2_TAX_RATE=0                            # Default tax rate (decimal)
A2_SHIPPING_FEE=0                        # Default shipping fee (decimal)
A2_CURRENCY=USD                          # Default currency code
A2_CURRENCY_SYMBOL="$"                   # Currency symbol
A2_CURRENCY_CONVERSION_RATE=130          # Currency conversion rate
```

**Important:** Set your real PayPal credentials from the [PayPal Developer Dashboard](https://developer.paypal.com/).

### Step 6: Verify Routes

The installer automatically adds the PayPal webhook route to `routes/api.php`:

```php
Route::prefix('a2/payment')->group(function () {
    Route::post('/paypal/webhook', [\App\Http\Controllers\A2\Commerce\PaymentController::class, 'webhookPayPal'])
        ->name('api.payment.paypal.webhook');
});
```

**Endpoint:** `POST /a2/payment/paypal/webhook`

### Step 7: Run Migrations

```sh
php artisan migrate
```

This will create all A2Commerce database tables.

## Usage

### PayPal Payment Flow

A2Commerce includes built-in PayPal payment processing:

1. **Create Order**: Customer completes checkout and order is created
2. **Redirect to PayPal**: Customer is redirected to PayPal for payment approval
3. **PayPal JS SDK**: Frontend handles PayPal approval using PayPal JavaScript SDK
4. **Backend Verification**: Backend confirms payment via `PayPalPaymentService::verifyOrder`
5. **Payment Completed Event**: `PaymentCompleted` event fires automatically
6. **Order Processing**: Order is marked as paid, finance records are created, cart is cleared
7. **Redirects**: Success/failure redirects are handled in `PaymentController`

### Guest Checkout Flow

A2Commerce supports guest checkout without requiring user authentication:

1. **Checkout Page**: Guest customer fills out contact information (no login required)
2. **Order Creation**: Order is created with `user_id` set to `null`
3. **Guest Contact Info**: Guest email and phone are stored in `a2_ec_order_address`
4. **Order Lookup**: Guest can view their order at `/order/{orderNumber}?email={guest_email}`
5. **Authenticated Users**: Logged-in users are automatically redirected to their account order pages

### Service Layer

A2Commerce provides a clean service layer in `app/Services/A2/`:

- **CartService**: Shopping cart management (add, remove, update items)
- **WishlistService**: Product wishlist functionality
- **ComparisonService**: Product comparison features
- **OrderService**: Order creation and management
- **PaymentService**: Payment processing logic
- **PayPalPaymentService**: PayPal-specific payment handling

All services support both guest and authenticated users.

### Event-Driven Architecture

A2Commerce uses Laravel events for decoupled functionality:

- **CartUpdated**: Fired when cart items change
- **OrderCreated**: Fired when a new order is created
- **PaymentCompleted**: Fired when payment is successfully processed
- **OrderStatusChanged**: Fired when order status updates

Listeners handle stock management, total calculations, notifications, and cleanup automatically.

## Commands

A2Commerce provides several Artisan commands for package management:

### Installation

```sh
php artisan a2commerce:install
```

Install A2Commerce with all files and configurations.

**Options:**

- `--no-overwrite`: Keep existing files instead of replacing them
- `--skip-env`: Leave `.env` files untouched

### Update

```sh
php artisan a2commerce:update
```

Refresh all package stubs and ensure environment keys are present.

**Options:**

- `--skip-env`: Leave `.env` files untouched

**Note:** This command refreshes stubs from the package. Your custom modifications to copied files will be overwritten, but business logic in your app layer (Services, Controllers, etc.) is not affected.

### Uninstall

```sh
php artisan a2commerce:uninstall
```

Remove all A2Commerce files and configurations.

**Options:**

- `--keep-env`: Preserve environment variables
- `--force`: Skip confirmation prompts

**Note:** This command removes copied files, environment variables, and routes. It does NOT:

- Remove database tables or migrations
- Uninstall Composer packages
- Remove vendor dependencies

### Help

```sh
php artisan a2commerce:help
```

Display comprehensive help information including commands, usage examples, environment variables, and routes.

## What's Included

### Service Layer

Located in `app/Services/A2/`:

- **CartService**: Shopping cart management with guest and auth support
- **WishlistService**: Product wishlist functionality
- **ComparisonService**: Product comparison features
- **OrderService**: Order creation and management
- **PaymentService**: Payment processing logic
- **PayPalPaymentService**: PayPal-specific payment handling

### Event-Driven Flow

Complete event system with listeners:

- **CartUpdated**: Stock checks, total calculations
- **OrderCreated**: Order processing, notifications
- **PaymentCompleted**: Order status updates, finance records, cart cleanup
- **OrderStatusChanged**: Status notifications, workflow triggers

### Livewire Volt Storefront

Views located in `resources/views/livewire/front/`:

- Cart page with real-time updates
- Checkout flow with guest support
- Product pages with add-to-cart functionality
- Wishlist management
- Product comparison
- Account order history
- PayPal payment integration

### Database Schema

Complete schema covering:

- Products and variations
- Taxonomies (categories, tags)
- Reserved stock management
- Orders and order items
- Payments and payment records
- Wishlist entries
- Comparison lists
- Finance records

## Uninstallation

Run the uninstall command:

```sh
php artisan a2commerce:uninstall
```

**What gets removed automatically:**

- ✅ All A2Commerce copied files and stubs
- ✅ PayPal webhook route from `routes/api.php`
- ✅ Environment variables from `.env` and `.env.example` (unless `--keep-env` is used)

**What is NOT removed:**

- ⚠️ **Database tables**: Migrations and tables remain in your database
- ⚠️ **Composer package**: Run `composer remove a2-atu/a2commerce` separately
- ⚠️ **Vormia package**: A2Commerce uninstall does not affect Vormia

**Manual cleanup required:**

- ⚠️ **Composer package**: Run `composer remove a2-atu/a2commerce` to completely remove from composer.json
- ⚠️ **Database tables**: Manually drop A2Commerce tables if needed (migrations are not rolled back)

### Uninstall Options

```sh
# Uninstall but keep environment variables
php artisan a2commerce:uninstall --keep-env

# Uninstall without confirmation prompts
php artisan a2commerce:uninstall --force
```

## Documentation & References

A2Commerce includes comprehensive documentation in the `packageflow-md/` directory:

- **Implementation Guide**: `packageflow-md/0-a_2_commerce_implementation_guide.md`

  - Complete setup and integration guide
  - Service usage examples
  - Event listener configuration

- **Schema Documentation**: `packageflow-md/1-a_2_commerce_schema.md`

  - Database table structures
  - Relationships and foreign keys
  - Index definitions

- **Event Flow**: `packageflow-md/2-a_2_event_flow.md`

  - Event lifecycle documentation
  - Listener registration
  - Event payload structures

- **Payment Guide**: `packageflow-md/3-a_2_payment_guide.md`

  - PayPal integration details
  - Webhook configuration
  - Payment flow diagrams

- **Shipping & Admin Guides**: `packageflow-md/4-6*.md`
  - Shipping configuration
  - Admin order review
  - Shipping management

## Troubleshooting

### Common Issues

#### Installation Fails

**Problem**: Installation command fails with errors  
**Solution**: Ensure PHP 8.2+, Laravel 12+, and Vormia 4.2+ are installed and meet requirements.

#### PayPal Webhook Not Working

**Problem**: PayPal webhooks are not being received  
**Solution**:

1. Verify `A2_PAYPAL_WEBHOOK_ID` is set correctly in `.env`
2. Check that the webhook route exists in `routes/api.php`
3. Ensure your application is accessible from the internet (use ngrok for local development)
4. Verify webhook URL in PayPal Developer Dashboard matches your route

#### Guest Checkout Not Working

**Problem**: Guest checkout redirects to login  
**Solution**:

1. Ensure `a2_ec_orders.user_id` column allows `null` values
2. Check that checkout routes don't require authentication middleware
3. Verify guest order lookup route is accessible without auth

#### Services Not Found

**Problem**: Service classes not found errors  
**Solution**:

1. Run `php artisan a2commerce:update` to refresh stubs
2. Check that files exist in `app/Services/A2/`
3. Clear application cache: `php artisan cache:clear`

## Support

For issues, questions, or contributions:

- Review the documentation in `packageflow-md/` directory
- Check command help: `php artisan a2commerce:help`
- Review Vormia documentation for dependency-related issues

---

**Thank you for using A2Commerce!**
