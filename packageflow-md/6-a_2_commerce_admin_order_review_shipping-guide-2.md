# A2 Commerce — Admin Commerce Management Features Implementation Plan

This document defines how the admin tools for **orders, wishlists, shipping, reviews, reporting, and admin-assisted checkout (shared carts)** will be implemented inside the A2 Commerce package.  
The plan assumes no supervision — developers should follow this document step-by-step.

---

## 1. High-Level Rules

| Area              | Decision                                                 |
| ----------------- | -------------------------------------------------------- |
| Admin Cart Table  | ❌ No traditional `admin_cart_sessions` table            |
| Shared Cart Token | ✅ Use durable shareable Token + signed URLs             |
| Shipping          | ✅ Separate table: `a2_ec_shipping_status`               |
| Orders Status     | ⚠️ Avoid altering ENUM directly — use VARCHAR or DBAL    |
| Admin Reviews     | Yes — with random reviewer names allowed                 |
| Auditing          | All sensitive actions logged in `a2_ec_order_action_log` |

---

## 2. Migrations Required

### 2.1 Update: `a2_ec_product_reviews`

| Column                               | Change                                          |
| ------------------------------------ | ----------------------------------------------- |
| user_id                              | Make nullable                                   |
| reviewer_name                        | Add (VARCHAR 255)                               |
| admin_id                             | Add FK to users (nullable, set null on delete)  |
| order_id                             | Add FK to orders (nullable, set null on delete) |
| is_verified                          | Add BOOLEAN default false                       |
| Unique constraint product_id+user_id | Remove                                          |

Indexes recommended: `product_id`, `user_id`, `order_id`, `admin_id`

---

### 2.2 Update: `a2_ec_orders`

**Do not modify ENUM in place.**  
Either migrate to `VARCHAR(50)` or modify using doctrine/dbal.

Add columns:
| Column | Type |
|--------|------|
| created_by_admin | TINYINT(1) DEFAULT 0 |
| admin_id | BIGINT FK (nullable) |
| order_source | VARCHAR(100) NULL |

Indexes recommended: `status`, `order_source`, `admin_id`

---

### 2.3 New Table: `a2_ec_shipping_status`

id, order_id (FK), courier, tracking_no, status,
expected_delivery_date, last_update, meta(JSON),
timestamps
Indexes: `order_id`, `tracking_no`, `status`

---

### 2.4 Shared Cart Token Storage (Mandatory)

Although no extra **cart "session" table**, shared carts need durable storage.

Recommended small table:
`a2_ec_shared_carts`
id, token(UUID), admin_id, cart_snapshot(JSON),
cart_hash, status(active|consumed|revoked),
expires_at, consumed_at, timestamps

---

## 3. Route Requirements (web.php)

All new admin routes must be protected by:
middleware => ['auth', 'can:manage-commerce']

| Section    | Routes              |
| ---------- | ------------------- |
| Orders     | admin/a2/orders     |
| Wishlist   | admin/a2/wishlist   |
| Shipping   | admin/a2/shipping   |
| Reviews    | admin/a2/reviews    |
| Reports    | admin/a2/reports    |
| Admin Cart | admin/a2/admin-cart |

Shared cart URL format for customers (signed):

/checkout/cart?sid={token}&expires={timestamp}&signature={hash}

---

## 4. Services — Responsibilities

### 4.1 `CartService` (extend)

| Method                                    | Purpose                                      |
| ----------------------------------------- | -------------------------------------------- |
| `createAdminCartSession($items, $admin)`  | Create shareable cart token                  |
| `getCartByToken($token)`                  | Load snapshot by token                       |
| `assignCartToCustomer($token, $customer)` | Attach shared cart to customer & build order |
| `revokeSharedCart($token)`                | Stop customer from using a link              |

Rules:

-   Enforce expiry time
-   Tokens are one-time use → first consumption wins

---

### 4.2 `ShippingService`

| Method                              | Purpose                                                |
| ----------------------------------- | ------------------------------------------------------ |
| `createShipping($order, $payload)`  | Save courier + tracking                                |
| `updateStatus($id, $status, $meta)` | Update shipping row + log action                       |
| `syncToOrderStatus()`               | When status becomes _delivered_ → mark order delivered |

---

### 4.3 `OrderService`

| Method                                     | Purpose                         |
| ------------------------------------------ | ------------------------------- |
| `assignOrderToCustomer($order, $customer)` | Converts guest → customer order |
| `markComplete($order)`                     | Marks complete & log entry      |

---

## 5. Admin UI Pages to Build

| Area       | Page                                          |
| ---------- | --------------------------------------------- |
| Wishlist   | Read-only list, filters                       |
| Shipping   | Index + Show + update status                  |
| Reviews    | Index + Create + Edit (admin reviews allowed) |
| Reports    | Sales, conversions, top buyers/products       |
| Admin Cart | Create shared carts + revoke + list active    |

---

## 6. Model Updates Summary

| Model              | Update                                         |
| ------------------ | ---------------------------------------------- |
| `A2ProductReview`  | reviewer_name, admin_id, order_id, is_verified |
| `A2Order`          | created_by_admin, admin_id, order_source       |
| `A2ShippingStatus` | new model                                      |
| `A2SharedCart`     | new model if DB variant used                   |

---

## 7. Shared Cart → Checkout → Payment Flow

### Admin

1. Admin builds cart inside admin panel
2. `createAdminCartSession()` called
3. Token saved + signed URL generated
4. Admin shares link with customer

### Customer

5. Customer opens signed link
6. Cart loaded from token snapshot
7. Customer pays → order is created
8. Token marked `consumed`
9. Notification sent to customer + admin

Edge case handling:

-   Price changed → show warning and require confirmation
-   Token expired → redirect to “link expired” screen
-   Double click abuse → first consumption wins

---

## 8. Logs & Auditing

Every sensitive action MUST log a row in `a2_ec_order_action_log`:

| Column     | Value                                                                  |
| ---------- | ---------------------------------------------------------------------- |
| actor_id   | `auth()->id()`                                                         |
| actor_role | admin / user / system                                                  |
| action     | e.g., `order_mark_complete`, `shipping_update`, `shared_cart_consumed` |
| meta       | relevant JSON                                                          |

---

## 9. Testing Requirements

Developers must include feature tests for:

| Scenario               | Must Pass                       |
| ---------------------- | ------------------------------- |
| Shared cart created    | URL is signed + stored          |
| Shared cart consumed   | Cart → order conversion         |
| Shared cart double-use | Prevent second order            |
| Admin review creation  | stored without user_id          |
| Shipping update        | syncs with order status         |
| Permissions            | only admins access admin routes |
| Price drift            | requires explicit confirmation  |

---

## 10. Deployment Warnings

-   Migrations altering `orders` & `product_reviews` tables require maintenance window.
-   Always backup DB before migrating.
-   Use feature flags to enable **Admin Cart** & **Shipping** progressively.

---

## 11. Final Expected Deliverables

| Category       | Files                                               |
| -------------- | --------------------------------------------------- |
| Migrations     | 4 updates/new                                       |
| Models         | A2ShippingStatus, A2SharedCart (optional)           |
| Services       | ShippingService, updated CartService & OrderService |
| Routes         | 5 admin sections                                    |
| Livewire Views | Wishlist, Shipping, Reviews, Reports, Admin Cart    |
| Tests          | Feature + Unit + Concurrency tests                  |
| Logging        | Updated action log events                           |

---

## End of Implementation Plan

Once this plan is completed, the admin tools for commerce will function with:

-   Order + shipping control
-   Customer reviews + admin reviews
-   Wishlist management
-   Reporting
-   Secure admin → customer shared carts
