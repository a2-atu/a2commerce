# A2 Commerce Fixes Plan

## Issues Found

### 1. **Guest Checkout Not Supported**
- **Problem**: Checkout requires authentication, OrderService returns null for guest users
- **Location**: `app/Services/A2/OrderService.php` line 30-34, `resources/views/livewire/front/checkout/index.blade.php` line 32-34
- **Impact**: Guests cannot complete orders

### 2. **Payment on Delivery Not Implemented**
- **Problem**: Only PayPal payment method available, no "payment on delivery" option
- **Location**: `resources/views/livewire/front/checkout/index.blade.php` line 27, 194-207
- **Impact**: Cannot select payment on delivery

### 3. **PayPal Page Requires Authentication**
- **Problem**: PayPal payment page checks `Auth::id()` which fails for guest orders
- **Location**: `resources/views/livewire/front/payment/paypal.blade.php` line 25
- **Impact**: Guest orders cannot access PayPal payment page

### 4. **Order Views Require Authentication**
- **Problem**: Order detail views filter by `user_id = Auth::id()` which fails for guest orders
- **Location**: 
  - `resources/views/livewire/front/account/orders.blade.php` line 14
  - `resources/views/livewire/front/account/order-detail.blade.php` line 21
- **Impact**: Guest orders cannot be viewed

### 5. **Payment Success Redirects to Auth-Required Page**
- **Problem**: Payment success redirects to `account.orders.show` which requires auth
- **Location**: `app/Http/Controllers/A2/PaymentController.php` line 202
- **Impact**: Guest orders redirected to inaccessible page after payment

### 6. **Database Constraint Issue**
- **Problem**: `user_id` in `a2_ec_orders` table has foreign key constraint (cannot be null)
- **Location**: `database/migrations/2025_11_12_120407_create_a2_ec_orders_table.php` line 16
- **Impact**: Cannot create orders without user_id

### 7. **Test Account Missing**
- **Problem**: No test shopper account exists
- **Impact**: Cannot test authenticated checkout flow

## Fix Plan

### Phase 1: Database Migration
1. ✅ Create migration to make `user_id` nullable in `a2_ec_orders` table
2. ✅ Add `guest_email` field to `a2_ec_order_address` table (or use existing email in addresses)

### Phase 2: Order Service Updates
3. ✅ Update `OrderService::createFromCart()` to accept guest email and allow null user_id
4. ✅ Update order creation to store guest email in shipping address

### Phase 3: Checkout Component
5. ✅ Remove authentication requirement from checkout `mount()` method
6. ✅ Add "Payment on Delivery" payment method option
7. ✅ Handle guest checkout flow (store email from form)

### Phase 4: Payment Handling
8. ✅ Update PayPal payment page to work with guest orders (remove Auth::id() check)
9. ✅ Update payment success/failure redirects to handle guest orders
10. ✅ Add payment_on_delivery handling in OrderService (mark as pending payment)

### Phase 5: Order Views
11. ✅ Update order list view to show guest orders (filter by email if guest)
12. ✅ Update order detail view to allow access by order_number + email for guests
13. ✅ Create guest order lookup method

### Phase 6: Test Account
14. ✅ Create seeder/migration to create test shopper account

## Implementation Order

1. Database migration (make user_id nullable)
2. OrderService updates (support guest)
3. Checkout component (remove auth, add payment on delivery)
4. Payment pages (remove auth checks)
5. Order views (support guest lookup)
6. Test account creation

