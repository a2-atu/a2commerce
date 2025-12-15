# A2 Commerce – Model Event Flow Guide

This document explains the model–event–listener lifecycle for three core customer journeys in A2 Commerce:

1. **Product → Cart → Checkout → Payment → Orders**
2. **Product → Wishlist**
3. **Product → Comparison List**

Each flow highlights **model triggers, events, listeners, observers, and resulting actions**.

---

## 🔥 1. Product → Add to Cart → Checkout → Payment → Orders Flow

### Step‑by‑Step Timeline

```
User views product
 ↓
User adds product to cart
 ↓
User updates or continues to checkout
 ↓
Order is created
 ↓
Payment is completed
 ↓
Stock is finalized / reserved stock released
 ↓
Order visible to buyer & admin
```

### 🔄 Event Flow Breakdown

| Action                                    | Trigger                                | Event Fired          | Listener Responses                        | Notes                                 |
| ----------------------------------------- | -------------------------------------- | -------------------- | ----------------------------------------- | ------------------------------------- |
| Add to cart                               | Create/Update cart entry               | `ProductAddedToCart` | Create/update reserved stock (if enabled) | Cart stored via user_id or session_id |
| Update cart                               | Modify quantity or remove item         | `CartUpdated`        | Adjust reserved stock                     | Prevent overselling                   |
| Checkout                                  | `a2_ec_orders` entry created           | `OrderCreated`       |                                           |                                       |
| • Convert reserved stock to pending stock |                                        |                      |                                           |                                       |
| • Log customer action                     |                                        |                      |                                           |                                       |
| • Send order confirmation email           | Cart remains untouched until payment   |                      |                                           |                                       |
| Payment completed                         | Payment model saved with `status=paid` | `PaymentCompleted`   |                                           |                                       |
| • Mark order as paid                      |                                        |                      |                                           |                                       |
| • Release reserved stock                  |                                        |                      |                                           |                                       |
| • Deduct real stock                       |                                        |                      |                                           |                                       |
| • Record finance split                    |                                        |                      |                                           |                                       |
| • Notify vendor & buyer                   | Most business rules triggered here     |                      |                                           |                                       |
| Order ready for delivery                  | Order status updated                   | `OrderStatusUpdated` | Notify rider/vendor                       | Optional shipping API integration     |
| Order fulfilled                           | Marked complete                        | `OrderCompleted`     | Award loyalty points, enable review form  | Links into review system              |

### 🧾 Resulting Data Visibility

| Module                        | Display                                                      |
| ----------------------------- | ------------------------------------------------------------ |
| Buyer Dashboard               | Orders history + invoice download                            |
| Admin Panel                   | Order list, finance summary, delivery status, customer notes |
| Vendor Panel (if multivendor) | Vendor earnings, commission deduction                        |

---

## 💙 2. Product → Wishlist Flow

### Timeline

```
User views product
 ↓
Add to wishlist (session or account)
 ↓
View wishlist page in account/dashboard
```

### Event Flow

| Action               | Trigger                      | Event Fired                  | Listener Responses      | Notes                              |
| -------------------- | ---------------------------- | ---------------------------- | ----------------------- | ---------------------------------- |
| Add to wishlist      | Insert to `a2_ec_wishlist`   | `ProductAddedToWishlist`     | Log marketing analytics | Supports guests (session_id based) |
| Remove from wishlist | Delete from `a2_ec_wishlist` | `ProductRemovedFromWishlist` | Adjust analytics        | No stock changes                   |
| View wishlist        | Fetch via session or user    | *no event*                   | —                       | Pure read mode                     |

### Visibility

| Type           | View                        |
| -------------- | --------------------------- |
| Guest          | Session‑based list          |
| Logged‑in user | Account‑stored wishlist     |
| Admin          | Insight only (not editable) |

---

## ⚖️ 3. Product → Comparison Flow

### Timeline

```
User opens product page
 ↓
Add to comparison list
 ↓
Open comparison table (two or more products)
 ↓
Clear/remove items
```

### Event Breakdown

| Action               | Trigger                                  | Event Fired                | Listener Responses | Notes                       |
| -------------------- | ---------------------------------------- | -------------------------- | ------------------ | --------------------------- |
| Add to comparison    | Add record to `a2_ec_comparison_items`   | `ProductAddedToComparison` | Log analytics      | Detached from cart/wishlist |
| Open comparison view | Retrieve via `a2_ec_comparison_sessions` | *no event*                 | —                  | Data only, no processing    |
| Clear comparison     | Delete items or destroy session          | `ComparisonCleared`        | Update analytics   | No effect on stock/orders   |

### Additional Notes

* A session may hold multiple products (2–10+)
* Guests and logged‑in users supported
* Expiry timestamp on comparison sessions prevents DB clutter

---

## 🧠 Summary Matrix — Which Modules Trigger What

| Action Category   | Cart | Wishlist | Comparison | Orders | Payments | Stock       | Finance  |
| ----------------- | ---- | -------- | ---------- | ------ | -------- | ----------- | -------- |
| Add product       | ✔️   | ✔️       | ✔️         | ❌      | ❌        | ❌           | ❌        |
| Checkout          | ✔️   | ❌        | ❌          | ✔️     | ❌        | ⚠️ reserved | ❌        |
| Payment           | ❌    | ❌        | ❌          | ✔️     | ✔️       | ✔️ update   | ✔️ split |
| Delivery complete | ❌    | ❌        | ❌          | ✔️     | ❌        | ❌           | ❌        |
| Clear/Remove      | ✔️   | ✔️       | ✔️         | ❌      | ❌        | ❌           | ❌        |

---

## 🔔 Final Notes

* **Stock only changes permanently after payment success.**
* **Wishlist & comparison never affect stock — only cart and orders may.**
* **All core processes are event‑driven**, meaning customization happens through listeners rather than modifying business logic.
* **Review system triggers only after order completion** to prevent fake reviews.

---

This flow document ensures any developer understands the exact lifecycle of the three most important user journeys inside **A2 Commerce**.

---

## 🔥 1. Product → Add to Cart → Checkout → Payment → Orders Flow (Hybrid View)

### Step 1 — Customer views a product

**Model involved:** `Product`

* No event yet unless view tracking enabled
* Eager loads: price, stock, thumbnail, taxonomies

### Step 2 — Add to Cart

**Action:** Customer clicks **Add to Cart**

```
Cart::add(product_id, qty)
```

**Triggered Event:** `CartUpdated`
**Listeners:**

* `ReserveStock` → inserts row into `a2_ec_reserved_stock`
* `RebuildCartSnapshot` → stores current cart state for quick UI load

### Step 3 — Go to Cart (Update / Remove)

* Increase quantity → triggers `CartUpdated` again
* Decrease quantity → triggers `CartUpdated`
* Remove item → triggers `CartItemRemoved`

**Listeners (on remove / change):**

* `ReleaseReservedStock`
* `RecalculateCartTotals`

### Step 4 — Checkout begins

```
Order::createFromCart(session)
```

**Triggered Event:** `OrderCreated`
**Listeners:**

* `CaptureReservedStock` → converts reserved → sold count
* `GenerateOrderNumber`
* `OrderNotificationCustomer`
* `OrderNotificationAdmin`

### Step 5 — Payment (M‑Pesa / Card / Bank)

```
Payment::confirm(order_id)
```

**Triggered Event:** `PaymentCompleted`
**Listeners:**

* `MarkOrderPaid`
* `OrderStatusPaid`
* `RecordFinance` → `a2_ec_order_finance`
* `ReleaseUnpaidReservations` (for abandoned carts of other users)
* `CreatePayoutsIfVendorOrder`

After successful payment:

```
Cart::clear(session_id)
ReservedStock::clear(session_id)
```

### Step 6 — View Orders (Customer)

* UI calls `Order::where(user_id)`
* Eager loads: items, finance, payments, action log

### Step 7 — View Orders (Admin)

* Admin dashboard calls `Order::with(items, payments, finance, address, actions)`
* Admin actions trigger `OrderActionLogged` event for traceability

---

## ⭐ 2. Product → Wishlist → View in Account Flow (Hybrid View)

### Step 1 — Add to Wishlist

```
Wishlist::add(product_id, user_id OR session_id)
```

**Event:** `WishlistUpdated`
**Listener:** `RebuildWishlistSnapshot` (for fast UI loading)

### Step 2 — View Wishlist

* Auto loads via snapshot
* If guest logs in, session wishlist is migrated to the user wishlist
  **Triggered Event:** `WishlistMerged`
  **Listener:** `DeleteSessionWishlist`

### Step 3 — Wishlist to Cart

* Clicking **Add to Cart** bypasses reloading product view
* Calls `Cart::add()` → flows into cart lifecycle above

---

## 🔍 3. Product → Compare → Comparison List → Clear List Flow (Hybrid View)

### Step 1 — Add to Comparison

```
Comparison::add(product_id, comparison_session_id)
```

**Event:** `ComparisonUpdated`
**Listener:** `RebuildComparisonSnapshot`

### Step 2 — View Comparison

* Snapshot returned for UI
* No database stress even for 10+ products

### Step 3 — Clear Comparison List

```
Comparison::clear(comparison_session_id)
```

**Event:** `ComparisonCleared`
**Listeners:**

* `DeleteComparisonItems`
* `RebuildComparisonSnapshot` (snapshot empties)

---

## 🔁 Cross‑System Notes & Intelligence

| Situation                | Automatic Result                                         |
| ------------------------ | -------------------------------------------------------- |
| Cart abandoned           | `ReservedStockReleaseTimer` frees stock after TTL        |
| Order cancelled          | `ReverseFinance & Restock` listener repairs counts       |
| Coupon applied           | `DiscountApplied` logged in `a2_ec_order_finance`        |
| Multi‑vendor order       | `CreateVendorFinanceSplit` listener runs on payment      |
| Auction product checkout | Only `bid winner` allowed → enforced on `PaymentAttempt` |
| Service order            | `ServiceLogEntryCreated` listener tracks hours/delivery  |

---

## 🧠 Final Philosophy of the Flow

> A2 never updates stock, finance, wishlist, or cart **manually**.
> Everything cascades from **Eloquent Events → Listeners → State Change**, ensuring traceability and reliability.

This keeps A2 scalable for single‑seller stores and giant marketplaces.

---

### End of File — ready for continued contribution.

## 💙 2. Product → Wishlist → Account View Flow (Hybrid View)

### Step 1 — Add to Wishlist

User clicks **Add to Wishlist** on a product.

```
Wishlist::add(product_id)
```

**Triggered Event:** `WishlistUpdated`
**Listeners:**

* `RecordWishlistActivity`
* `SyncWishlistToUser` (runs only when a guest later logs in)

**Database Impact:**

* Guest → insert row in `a2_ec_wishlist` with `session_id`
* Logged‑in user → insert row with `user_id`
* Product duplication rules handled in model (qty unaffected)

---

### Step 2 — View Wishlist in Customer Account

```
Wishlist::forUser(user_id)
```

**Triggered Event:** `WishlistViewed`
**Listeners:**

* `RecordWishlistView`

If the wishlist exists from a guest session:

```
MergeWishlistSessionToUser
```

→ runs once and deletes the guest session rows

---

### Step 3 — Remove From Wishlist

```
Wishlist::remove(wishlist_item_id)
```

**Triggered Event:** `WishlistUpdated`
**Listeners:**

* `RecordWishlistActivity`

---

### Admin View — Wishlist Analytics

Admin opens dashboard wishlist reports.

```
WishlistReport::generate()
```

**Triggered Event:** `WishlistAnalyticsViewed`
**Listeners:**

* `RecordWishlistInsight`

Admin does *not* mutate wishlist rows — only reads data.

---

## 🔷 3. Product → Comparison → Comparison Sheet → Clear List Flow (Hybrid View)

### Step 1 — Add a Product to Comparison

User clicks **Compare Product**

```
Comparison::add(product_id)
```

If there is no comparison session → create row in `a2_ec_comparison_sessions`.

**Triggered Event:** `ComparisonUpdated`
**Listeners:**

* `RecordComparisonActivity`

**Database Impact:**

* Insert row into `a2_ec_comparison_items` with `session_id` (and `user_id` if logged in)

---

### Step 2 — Open Comparison Sheet

User navigates to `/compare`

```
Comparison::open(session_id)
```

**Triggered Event:** `ComparisonViewed`
**Listeners:**

* `RecordComparisonView`

If user logs in and comparison was created when a guest:

```
MergeComparisonSessionToUser
```

→ attaches items to `user_id`, session retained for device tracking

---

### Step 3 — User Clears Comparison List

```
Comparison::clear(session_id)
```

**Triggered Event:** `ComparisonUpdated`
**Listeners:**

* `ClearComparisonAnalyticsCache`

**Database Impact:**

* Soft‑delete rows in `a2_ec_comparison_items`

---

### Admin View — Comparison Analytics

Admins can generate reports to understand **purchase intent**.

```
ComparisonAnalytics::generate()
```

**Triggered Event:** `ComparisonAnalyticsViewed`
**Listeners:**

* `RecordComparisonInsight`

This analytics layer helps:

* Detect most compared products
* Detect switch patterns (e.g., iPhone → Samsung)
* Detect marketing opportunities

---

### 🌟 Summary of Customer Journey Triggers

| Action                | Event Fired                                            | Examples of Listeners                          |
| --------------------- | ------------------------------------------------------ | ---------------------------------------------- |
| Add to Wishlist       | `WishlistUpdated`                                      | RecordWishlistActivity                         |
| View Wishlist         | `WishlistViewed`                                       | RecordWishlistView                             |
| Add to Comparison     | `ComparisonUpdated`                                    | RecordComparisonActivity                       |
| View Comparison       | `ComparisonViewed`                                     | RecordComparisonView                           |
| Clear Comparison      | `ComparisonUpdated`                                    | ClearComparisonAnalyticsCache                  |
| Admin opens analytics | `WishlistAnalyticsViewed`, `ComparisonAnalyticsViewed` | RecordWishlistInsight, RecordComparisonInsight |

---

All three journeys now follow the **same event‑driven contract**:

```
Model action → Event → Listener(s) → Database / Cache / Notifications
```

This guarantees A2 Commerce remains **extendable without rewriting core logic** — anyone can add a new listener at any point to extend functionality.

---

▶ Wishlist + Comparison flows added — document now matches the hybrid model format throughout.
