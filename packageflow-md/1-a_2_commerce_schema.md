# A2 Commerce Database Schema (v1.0)

This document defines the **A2 Commerce** database schema — 33 tables in total — organized into functional modules. Each table includes key columns, data types, and foreign key (FK) references where applicable.

---

## 🧱 1. Product Module

### **a2_ec_products**

| Column       | Type                                           | Description    |
| ------------ | ---------------------------------------------- | -------------- |
| id           | BIGINT UNSIGNED                                | Primary key    |
| name         | VARCHAR(255)                                   | Product name   |
| price        | DECIMAL(12,2)                                  | Base price     |
| product_type | ENUM('physical','digital','service','auction') | Product type   |
| is_active    | BOOLEAN DEFAULT TRUE                           | Status flag    |
| is_auction   | BOOLEAN DEFAULT FALSE                          | Auction toggle |
| is_service   | BOOLEAN DEFAULT FALSE                          | Service toggle |
| created_at   | TIMESTAMP                                      | —              |
| updated_at   | TIMESTAMP                                      | —              |
| deleted_at   | TIMESTAMP NULL                                 | Soft delete    |

---

### **a2_ec_product_meta**

| Column     | Type         | Description                 |
| ---------- | ------------ | --------------------------- |
| id         | BIGINT       | Primary key                 |
| product_id | BIGINT       | FK → a2_ec_products.id      |
| key        | VARCHAR(255) | Meta key                    |
| value      | LONGTEXT     | Meta value (JSON or string) |
| created_at | TIMESTAMP    | —                           |

---

### **a2_ec_product_variations**

| Column      | Type                      | Description                                |
| ----------- | ------------------------- | ------------------------------------------ |
| id          | BIGINT                    | PK                                         |
| product_id  | BIGINT                    | FK → a2_ec_products.id                     |
| taxonomy_id | BIGINT NULL               | FK → vrm_taxonomies.id (e.g., color, size) |
| price       | DECIMAL(12,2)             | Variant price                              |
| sku         | VARCHAR(100) NULL         | SKU identifier                             |
| stock       | INT UNSIGNED DEFAULT 0    | Quantity available                         |
| groupno     | INT UNSIGNED DEFAULT NULL | Items Bounded Together                     |
| created_at  | TIMESTAMP                 | —                                          |
| updated_at  | TIMESTAMP                 | —                                          |

---

### **a2_ec_product_taxonomies**

| Column      | Type                                        | Description            |
| ----------- | ------------------------------------------- | ---------------------- |
| id          | BIGINT                                      | PK                     |
| product_id  | BIGINT                                      | FK → a2_ec_products.id |
| taxonomy_id | BIGINT                                      | FK → vrm_taxonomies.id |
| type        | ENUM('category','tag','brand','collection') | —                      |

---

### **a2_ec_taxonomies** _(optional)_

| Column      | Type         | Description                             |
| ----------- | ------------ | --------------------------------------- |
| id          | BIGINT       | PK                                      |
| type        | VARCHAR(100) | Taxonomy type                           |
| group       | VARCHAR(100) | Category group                          |
| for         | VARCHAR(100) | Related module (e.g., product, service) |
| taxonomy_id | BIGINT NULL  | FK → vrm_taxonomies.id                  |
| created_at  | TIMESTAMP    | —                                       |
| deleted_at  | TIMESTAMP    | —                                       |

---

### **a2_ec_product_cache** _(optional)_

| Column       | Type      | Description                             |
| ------------ | --------- | --------------------------------------- |
| id           | BIGINT    | PK                                      |
| product_id   | BIGINT    | FK → a2_ec_products.id                  |
| preview_data | LONGTEXT  | Cached JSON of frequently accessed data |
| updated_at   | TIMESTAMP | —                                       |

---

## 📦 2. Inventory & Reservation

### **a2_ec_reserved_stock**

| Column       | Type                   | Description                      |
| ------------ | ---------------------- | -------------------------------- |
| id           | BIGINT                 | PK                               |
| product_id   | BIGINT                 | FK → a2_ec_products.id           |
| variation_id | BIGINT NULL            | FK → a2_ec_product_variations.id |
| cart_id      | VARCHAR(100)           | Session/cart identifier          |
| quantity     | INT UNSIGNED DEFAULT 1 | Reserved quantity                |
| in_checkout  | BOOLEAN DEFAULT TRUE   | Flag                             |
| expire_at    | TIMESTAMP              | Expiry timestamp                 |
| created_at   | TIMESTAMP              | —                                |
| updated_at   | TIMESTAMP              | —                                |

---

## 💳 3. Orders & Transactions

### **a2_ec_orders**

| Column         | Type                                                                      | Description                   |
| -------------- | ------------------------------------------------------------------------- | ----------------------------- |
| id             | BIGINT                                                                    | PK                            |
| user_id        | BIGINT                                                                    | FK → users.id                 |
| order_number   | VARCHAR(100)                                                              | Unique identifier             |
| status         | ENUM('pending','processing','shipped','delivered','cancelled','refunded') | —                             |
| total          | DECIMAL(12,2)                                                             | Total amount                  |
| payment_status | ENUM('unpaid','paid','failed')                                            | —                             |
| payment_method | VARCHAR(100)                                                              | e.g., 'mpesa', 'card', 'bank' |
| is_multivendor | BOOLEAN DEFAULT FALSE                                                     | Flag                          |
| created_at     | TIMESTAMP                                                                 | —                             |
| updated_at     | TIMESTAMP                                                                 | —                             |

---

### **a2_ec_order_items**

| Column       | Type          | Description                      |
| ------------ | ------------- | -------------------------------- |
| id           | BIGINT        | PK                               |
| order_id     | BIGINT        | FK → a2_ec_orders.id             |
| product_id   | BIGINT        | FK → a2_ec_products.id           |
| variation_id | BIGINT NULL   | FK → a2_ec_product_variations.id |
| price        | DECIMAL(12,2) | Item price                       |
| quantity     | INT           | Units                            |
| subtotal     | DECIMAL(12,2) | Computed subtotal                |

---

### **a2_ec_order_finance**

| Column        | Type          | Description          |
| ------------- | ------------- | -------------------- |
| id            | BIGINT        | PK                   |
| order_id      | BIGINT        | FK → a2_ec_orders.id |
| tax           | DECIMAL(12,2) | —                    |
| discount      | DECIMAL(12,2) | —                    |
| commission    | DECIMAL(12,2) | Vendor commission    |
| shipping_fee  | DECIMAL(12,2) | —                    |
| total_payable | DECIMAL(12,2) | Final payable total  |

---

### **a2_ec_order_address**

| Column       | Type                       | Description          |
| ------------ | -------------------------- | -------------------- |
| id           | BIGINT                     | PK                   |
| order_id     | BIGINT                     | FK → a2_ec_orders.id |
| type         | ENUM('billing','shipping') | —                    |
| address_line | TEXT                       | —                    |
| city         | VARCHAR(100)               | —                    |
| country      | VARCHAR(100)               | —                    |
| postal_code  | VARCHAR(50)                | —                    |

---

### **a2_ec_order_action_log**

| Column     | Type         | Description             |
| ---------- | ------------ | ----------------------- |
| id         | BIGINT       | PK                      |
| order_id   | BIGINT       | FK → a2_ec_orders.id    |
| action     | VARCHAR(255) | e.g., 'Order confirmed' |
| actor_id   | BIGINT NULL  | FK → users.id           |
| created_at | TIMESTAMP    | —                       |

---

### **a2_ec_order_reviews**

| Column     | Type      | Description          |
| ---------- | --------- | -------------------- |
| id         | BIGINT    | PK                   |
| order_id   | BIGINT    | FK → a2_ec_orders.id |
| user_id    | BIGINT    | FK → users.id        |
| rating     | INT       | —                    |
| comment    | TEXT      | —                    |
| created_at | TIMESTAMP | —                    |

---

### _(Optional Order Extensions)_

-   **a2_ec_order_items_meta** – key/value item attributes.
-   **a2_ec_order_stats** – aggregate order metrics.
-   **a2_ec_order_admin_notes** – internal notes.
-   **a2_ec_order_download_log** – digital download tracking.

---

## 💰 4. Payments & Config

### **a2_ec_payments**

| Column           | Type                                 | Description           |
| ---------------- | ------------------------------------ | --------------------- |
| id               | BIGINT                               | PK                    |
| order_id         | BIGINT                               | FK → a2_ec_orders.id  |
| user_id          | BIGINT                               | FK → users.id         |
| method           | VARCHAR(100)                         | e.g., 'mpesa', 'card' |
| transaction_code | VARCHAR(100)                         | Payment reference     |
| amount           | DECIMAL(12,2)                        | —                     |
| status           | ENUM('pending','completed','failed') | —                     |
| created_at       | TIMESTAMP                            | —                     |

---

### **a2_ec_coupons**

| Column      | Type                    | Description |
| ----------- | ----------------------- | ----------- |
| id          | BIGINT                  | PK          |
| code        | VARCHAR(100)            | Coupon code |
| type        | ENUM('fixed','percent') | —           |
| value       | DECIMAL(12,2)           | —           |
| expiry_date | TIMESTAMP NULL          | —           |
| usage_limit | INT NULL                | —           |
| created_at  | TIMESTAMP               | —           |

---

### **a2_ec_settings**

| Column     | Type         | Description  |
| ---------- | ------------ | ------------ |
| id         | BIGINT       | PK           |
| key        | VARCHAR(255) | Config key   |
| value      | TEXT         | Config value |
| created_at | TIMESTAMP    | —            |

---

## 💬 5. Reviews, Wishlist & Comparison

### **a2_ec_product_reviews**

| Column     | Type      | Description            |
| ---------- | --------- | ---------------------- |
| id         | BIGINT    | PK                     |
| product_id | BIGINT    | FK → a2_ec_products.id |
| user_id    | BIGINT    | FK → users.id          |
| rating     | INT       | —                      |
| comment    | TEXT      | —                      |
| created_at | TIMESTAMP | —                      |

---

### **a2_ec_wishlist**

| Column     | Type              | Description            |
| ---------- | ----------------- | ---------------------- |
| id         | BIGINT            | PK                     |
| user_id    | BIGINT NULL       | FK → users.id          |
| session_id | VARCHAR(100) NULL | Guest wishlist         |
| product_id | BIGINT            | FK → a2_ec_products.id |
| created_at | TIMESTAMP         | —                      |

---

### **a2_ec_comparison_sessions**

| Column     | Type                 | Description   |
| ---------- | -------------------- | ------------- |
| id         | BIGINT               | PK            |
| uuid       | CHAR(36)             | Session UUID  |
| user_id    | BIGINT NULL          | FK → users.id |
| session_id | VARCHAR(100) NULL    | Guest session |
| title      | VARCHAR(255) NULL    | Optional name |
| expires_at | TIMESTAMP NULL       | Auto expiry   |
| is_active  | BOOLEAN DEFAULT TRUE | Flag          |
| created_at | TIMESTAMP            | —             |

---

### **a2_ec_comparison_items**

| Column                | Type      | Description                       |
| --------------------- | --------- | --------------------------------- |
| id                    | BIGINT    | PK                                |
| comparison_session_id | BIGINT    | FK → a2_ec_comparison_sessions.id |
| product_id            | BIGINT    | FK → a2_ec_products.id            |
| created_at            | TIMESTAMP | —                                 |

---

### **a2_ec_comparison_log** _(optional)_

| Column                | Type                                            | Description                       |
| --------------------- | ----------------------------------------------- | --------------------------------- |
| id                    | BIGINT                                          | PK                                |
| comparison_session_id | BIGINT                                          | FK → a2_ec_comparison_sessions.id |
| product_a             | BIGINT                                          | FK → a2_ec_products.id            |
| product_b             | BIGINT                                          | FK → a2_ec_products.id            |
| action                | ENUM('viewed','compared','removed','purchased') | —                                 |
| created_at            | TIMESTAMP                                       | —                                 |

---

## ⚙️ 6. Services, Logs & Utilities

### **a2_ec_service_log**

| Column       | Type         | Description            |
| ------------ | ------------ | ---------------------- |
| id           | BIGINT       | PK                     |
| order_id     | BIGINT       | FK → a2_ec_orders.id   |
| user_id      | BIGINT       | FK → users.id          |
| service_id   | BIGINT       | FK → a2_ec_products.id |
| hours_logged | DECIMAL(5,2) | —                      |
| note         | TEXT         | Optional description   |
| created_at   | TIMESTAMP    | —                      |

---

### **a2_ec_action_log** _(optional)_

| Column     | Type         | Description              |
| ---------- | ------------ | ------------------------ |
| id         | BIGINT       | PK                       |
| user_id    | BIGINT       | FK → users.id            |
| action     | VARCHAR(255) | —                        |
| entity     | VARCHAR(255) | e.g., 'product', 'order' |
| entity_id  | BIGINT       | —                        |
| created_at | TIMESTAMP    | —                        |

---

### **a2_ec_inventory_events** _(optional)_

| Column     | Type                          | Description            |
| ---------- | ----------------------------- | ---------------------- |
| id         | BIGINT                        | PK                     |
| product_id | BIGINT                        | FK → a2_ec_products.id |
| event      | ENUM('add','remove','adjust') | —                      |
| quantity   | INT                           | —                      |
| actor_id   | BIGINT                        | FK → users.id          |
| created_at | TIMESTAMP                     | —                      |

---

## 🏁 7. Auction Module

### **a2_ec_auctions**

| Column         | Type               | Description            |
| -------------- | ------------------ | ---------------------- |
| id             | BIGINT             | PK                     |
| product_id     | BIGINT             | FK → a2_ec_products.id |
| start_time     | TIMESTAMP          | —                      |
| end_time       | TIMESTAMP          | —                      |
| starting_price | DECIMAL(12,2)      | —                      |
| reserve_price  | DECIMAL(12,2) NULL | —                      |
| created_at     | TIMESTAMP          | —                      |

---

### **a2_ec_auction_bids**

| Column     | Type                  | Description            |
| ---------- | --------------------- | ---------------------- |
| id         | BIGINT                | PK                     |
| auction_id | BIGINT                | FK → a2_ec_auctions.id |
| user_id    | BIGINT                | FK → users.id          |
| amount     | DECIMAL(12,2)         | —                      |
| is_won     | BOOLEAN DEFAULT FALSE | Marked true for winner |
| created_at | TIMESTAMP             | —                      |

---

### **a2_ec_auction_log** _(optional)_

| Column     | Type         | Description                          |
| ---------- | ------------ | ------------------------------------ |
| id         | BIGINT       | PK                                   |
| auction_id | BIGINT       | FK → a2_ec_auctions.id               |
| user_id    | BIGINT       | FK → users.id                        |
| action     | VARCHAR(255) | 'bid_placed', 'auction_started' etc. |
| created_at | TIMESTAMP    | —                                    |

---

# 🧭 Example Product Journey

### Example: "iPhone 15 Pro" across modules

1. **Product Creation** — inserted into `a2_ec_products` (type: physical) → categories assigned via `a2_ec_product_taxonomies`.
2. **Wishlist** — guest adds it → record in `a2_ec_wishlist` with `session_id`.
3. **Comparison** — added to a comparison session (`a2_ec_comparison_sessions` → `a2_ec_comparison_items`).
4. **Add to Cart** — session-based cart; optional reserved stock entry in `a2_ec_reserved_stock` (5-min TTL).
5. **Checkout** — generates entry in `a2_ec_orders`, linked order items in `a2_ec_order_items`.
6. **Payment** — stored in `a2_ec_payments` (method: M-Pesa). If coupon applied → `a2_ec_coupons` reference.
7. **Delivery** — address from `a2_ec_order_address`, status updates logged in `a2_ec_order_action_log`.
8. **Service (if cleaning job)** — details logged in `a2_ec_service_log` (hours worked).
9. **Auction (if bid)** — stored in `a2_ec_auction_bids` with `is_won` flag once closed.
10. **Review** — feedback saved in `a2_ec_product_reviews` and/or `a2_ec_order_reviews`.
11. **Finance Split** — multi-vendor payout settings from `a2_ec_settings` guide commission distribution via `a2_ec_order_finance`.

---

# 🔗 References

-   `users`, `roles`, `role_users`, `vrm_user_meta` from Vormia core handle authentication & roles.
-   `vrm_taxonomies` provides categories, tags, and brand relations.

---

**Total:** 33 tables (22 core + 11 optional)  
**Schema Philosophy:** modular, prefixed, role-aware, and extendable for multi-vendor marketplaces.
