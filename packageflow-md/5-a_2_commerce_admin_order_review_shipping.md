# A2 Admin Transfer Cart, Order, Review & Shipping Behavior Guide

This document explains how A2 Commerce handles four major workflows that involve both customers and administrators:

1. **Admin‑assisted Cart Creation & Transfer to Customer**
2. **Order Lifecycle & Status Behavior**
3. **Product Review System (Customer + Admin generated)**
4. **Shipping Tracking & Delivery Flow**

The rules here are written so ANY developer can implement the behavior without further clarification.

---

## 🛒 1. Admin‑Assisted Cart → Customer Checkout & Order Transfer

Admins can build a cart on behalf of a customer and generate a checkout link.

### 🔹 Admin Creates Cart

Admin selects items → enters quantities → clicks **Create Checkout Link**.

System:

```
cart_session_id = UUID
items inserted into a2_ec_cart linked using cart_session_id
```

Checkout link generated:

```
/checkout/cart?sid={cart_session_id}
```

### 🔹 Customer Opens Link

| User State     | Behavior                                                |
| -------------- | ------------------------------------------------------- |
| Guest          | Proceeds as guest checkout                              |
| Logged‑in user | Cart is synced under their account (`user_id` attached) |

If a guest later logs in **after the order**, the order ownership will be updated:

```
order.user_id = logged_in_user_id
```

### 🔹 After Payment

System converts cart → order → payment normally:

* Payment triggers `PaymentCompleted` event
* Order becomes owned by customer account
* Admin who built cart is still logged for reporting

**Order Metadata Examples:**

```
created_by_admin = true
admin_id = {admin_user_id}
converted_by_customer_id = {customer_user_id or null}
order_source = "admin_assisted_checkout"
```

---

## 📦 2. Order Lifecycle & Status Behavior

Order status affects business logic, finance release, notifications & shipping.

### 🔹 Order Status Codes

| Status           | Meaning                           |
| ---------------- | --------------------------------- |
| pending          | Created but not paid              |
| awaiting_payment | Payment started but not completed |
| paid             | Payment verified (JS or Webhook)  |
| processing       | Admin/vendor preparing the order  |
| shipped          | Sent to courier                   |
| delivered        | Customer received item            |
| cancelled        | Manually cancelled                |
| refunded         | Refunded or returned              |

### 🔹 Status Rule Summary

| Event                          | New Status   |
| ------------------------------ | ------------ |
| JS or Webhook confirms payment | `paid`       |
| Admin starts order fulfillment | `processing` |
| Shipping created               | `shipped`    |
| Shipment delivered             | `delivered`  |
| Refund processed               | `refunded`   |

Each change is logged in:

```
a2_ec_order_action_log
```

Observers trigger:

* Notifications
* Vendor commission (if enabled)
* Stock conversion from reserved → sold

---

## ⭐ 3. Product Review System (Customer + Admin)

A2 uses a flexible review system that prevents abuse while supporting marketing‑style anonymous admin reviews.

### 🔹 Customer‑Generated Reviews

Requirements:

* Must have purchased the product (order verification)
* One review per product per order

Stored in:

```
a2_ec_product_reviews
```

Fields:

```
product_id | user_id | order_id | rating | comment | is_verified | created_at
```

`is_verified = true` when:

* Reviewer actually purchased the product

Rating summary is stored in product cache:

```
rating_count | rating_average
```

Updated automatically by `ReviewObserver`.

### 🔹 Admin‑Generated Reviews

Admin can add:

```
name (random), rating, comment, product_id
```

Stored in the same table with the following behavior:

```
user_id = null
admin_id = {admin_user_id}
is_verified = false
```

Admin reviews do **not** affect verification scoring.

---

## 🚚 4. Shipping Tracking & Delivery Behavior

A2 separates **order status** from **shipping progress** to prevent confusion.

### 🔹 Shipping Details Stored In

```
a2_ec_shipping_status
```

Fields:

```
order_id | courier | tracking_no | status | expected_delivery_date | last_update
```

### 🔹 Shipping Status Codes

| Status           | Meaning                       |
| ---------------- | ----------------------------- |
| pending_pickup   | Waiting for courier pickup    |
| in_transit       | Moving between locations      |
| at_destination   | Reached destination city      |
| delayed          | Unexpected delay              |
| out_for_delivery | Sent for final delivery       |
| delivered        | Customer has received package |

### 🔹 Link Between Shipping & Order Status

| Shipping Event            | Order Status |
| ------------------------- | ------------ |
| shipping created          | `shipped`    |
| shipping marked delivered | `delivered`  |

Every update is logged in:

```
a2_ec_order_action_log
```

for support dispute traceability.

---

## 🧠 Summary — Business Rules for All Flows

| Feature                 | Business Rule                                       |
| ----------------------- | --------------------------------------------------- |
| Admin‑assisted checkout | Order belongs to customer after payment             |
| Cart session ownership  | Transfers automatically when logged in              |
| Customer review         | Only if they purchased (verified)                   |
| Admin review            | Allowed, always marked non‑verified                 |
| Shipping and order      | Different states but synced on completion           |
| Logs                    | Every change is recorded for long‑term traceability |

---

## ✅ Developer Checklist

To fully implement these four systems, ensure:

* `ReviewObserver` updates rating cache
* `OrderObserver` triggers notifications + finance events
* `ShippingService` updates shipping + order state
* Admin cart creation **never bypasses** the payment pipeline
* All flows write audit records into `a2_ec_order_action_log`

---

**End of file — this guide covers everything needed for consistent A2 Commerce behavior across admin and customer interactions.**
