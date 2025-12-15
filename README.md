# A2 Commerce

Laravel 12 package for a decoupled, event-driven e-commerce stack (cart, checkout, PayPal, guest checkout, wishlist/compare) built with Vormia and Livewire Volt.

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vormia 4.2+
- Livewire Volt 1.x

## Install

1. Add the package: `composer require a2-atu/a2commerce`
2. Run the installer to copy stubs, add env keys, and ensure routes:
   - `php artisan a2commerce:install`
   - Flags: `--no-overwrite` (keep existing files), `--skip-env` (leave .env untouched)
3. Review the env keys the installer adds (set your real values):
   - `A2_PAYPAL_MODE` (default `sandbox`)
   - `A2_PAYPAL_SECRET`
   - `A2_PAYPAL_CLIENT_ID`
   - `A2_PAYPAL_WEBHOOK_ID`
   - `A2_ORDER_PREFIX`, `A2_TAX_RATE`, `A2_SHIPPING_FEE`
   - `A2_CURRENCY`, `A2_CURRENCY_SYMBOL`, `A2_CURRENCY_CONVERSION_RATE`
4. The installer appends the PayPal webhook route to `routes/api.php`:
   - `POST /a2/payment/paypal/webhook` → `App\Http\Controllers\A2\Commerce\PaymentController@webhookPayPal`

## Update or Uninstall

- Refresh stubs/env keys: `php artisan a2commerce:update` (flag: `--skip-env`)
- Remove stubs/env keys: `php artisan a2commerce:uninstall` (flags: `--keep-env`, `--force`)
- Command reference: `php artisan a2commerce:help`

## What’s Included

- Service layer in `app/Services/A2/` (Cart, Wishlist, Comparison, Order, Payment, PayPalPayment) with guest + auth support.
- Event-driven flow (`CartUpdated`, `OrderCreated`, `PaymentCompleted`, etc.) with listeners for stock, totals, notifications, and cleanup.
- Livewire Volt storefront views under `resources/views/livewire/front/` (cart, checkout, product pages, wishlist, compare, account, PayPal).
- Guest checkout with nullable `a2_ec_orders.user_id`; guest order view at `/order/{orderNumber}?email=...`.
- Schema covers products, variations, taxonomies, reserved stock, orders, payments, wishlist, comparison, and finance tables.

## PayPal Flow (built-in)

1. Create order → redirect to PayPal.
2. PayPal JS SDK approves → backend confirms via `PayPalPaymentService::verifyOrder`.
3. `PaymentCompleted` event fires → order marked paid, finance recorded, cart cleared.
4. Success/failure redirects handled in `PaymentController`.

## Guest Checkout Flow

- Checkout page collects guest contact info (no auth required).
- Orders store guest email/phone in `a2_ec_order_address`.
- Guest order lookup: `/order/{orderNumber}` with `email` query param.
- Auth users are redirected to their account order pages instead.

## Docs & References

- Implementation guide: `packageflow-md/0-a_2_commerce_implementation_guide.md`
- Schema: `packageflow-md/1-a_2_commerce_schema.md`
- Event flow: `packageflow-md/2-a_2_event_flow.md`
- Payment: `packageflow-md/3-a_2_payment_guide.md`
- Fixes plan & shipping guides: `packageflow-md/4-6*.md`
