# A2 Commerce — Universal Payment Integration Guide

This document explains how to integrate **PayPal, M‑Pesa, Pesapal, Lemon Squeezy, and Cash on Delivery** using a **single unified A2 payment pipeline**.

It includes:

- Webhook URL structure for each gateway (success + failed)
- Eloquent model & service skeletons
- Sample test cases for the `PaymentCompleted` event

---

## 🔗 Webhook & Callback URL Structure

> ⚠️ Payment authority mode for current development phase: **WINS‑JS**
> (JavaScript confirmation marks payment as completed instantly and webhook is secondary for now.)
> When more gateways are added, this can switch to **HYBRID** without breaking compatibility.

> **Update: PayPal now uses BOTH JavaScript confirmation + Webhook validation.**
> Webhook becomes the source of truth — JS confirm only _initiates_ verification.
> & Callback URL Structure
> All payment gateways must target the **same internal entry points** so the business logic does not change.

### **Base Payment Routes**

```
POST /a2/commerce/payment/{provider}/init        → optional
POST /a2/commerce/payment/{provider}/confirm     → used for PayPal JS button
POST /a2/commerce/payment/{provider}/callback    → for redirect-based gateways
POST /a2/commerce/payment/{provider}/webhook     → for server‑to‑server notifications
```

### **Example Routes (expanded)**

| Provider         | Frontend            | Verification               | Server webhook          |
| ---------------- | ------------------- | -------------------------- | ----------------------- |
| PayPal           | JS SDK → `/confirm` | `confirm` verifies via API | optional webhook        |
| M‑Pesa           | STK push            | callback → `/callback`     | `/webhook` confirms txn |
| Pesapal          | redirect            | `/callback`                | `/webhook` confirms txn |
| Lemon Squeezy    | none                | none                       | `/webhook` only         |
| Cash on Delivery | manual              | none                       | none                    |

### **Success & Failure Convention**

Each return URL MUST indicate result and order for logging:

```
/success?order={id}&provider={paypal|mpesa|pesapal}
/failed?order={id}&provider={paypal|mpesa|pesapal}
```

These URLs are for **user redirection** only — they do **not** update payment state.
The **only thing that updates payment state** is verification + webhook.

---

## 🧱 Eloquent Model Skeletons

### **Payment model (a2_ec_payments)**

```
class Payment extends Model {
    protected $table = 'a2_ec_payments';

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function markCompleted($payload) {
        $this->update([
            'status' => 'completed',
            'payload' => $payload,
            'paid_at' => now(),
        ]);
        event(new PaymentCompleted($this));
    }

    public function markFailed($payload) {
        $this->update([
            'status' => 'failed',
            'payload' => $payload,
        ]);
        event(new PaymentFailed($this));
    }
}
```

### **Order model (a2_ec_orders)**

```
class Order extends Model {
    protected $table = 'a2_ec_orders';

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function markPaid() {
        $this->update(['status' => 'paid']);
    }
}
```

---

## 🧩 Payment Service Layer Skeleton

Each gateway has its own service but uses a uniform interface.

```
interface PaymentGateway {
    public function verify(array $data): PaymentVerificationResult;
}
```

### **Example: PayPalPaymentService.php**

```
class PayPalPaymentService implements PaymentGateway {
    public function verify(array $data): PaymentVerificationResult {
        $paypalOrderId = $data['paypal_order_id'];

        // Call PayPal API...
        $response = Http::withToken(config('a2.paypal.secret'))
            ->get("https://api.paypal.com/v2/checkout/orders/{$paypalOrderId}");

        if ($response->json('status') === 'COMPLETED') {
            return PaymentVerificationResult::success(
                $response->json('id'),
                $response->json()
            );
        }

        return PaymentVerificationResult::failed($response->json());
    }
}
```

### **Result DTO used by all providers**

```
class PaymentVerificationResult {
    public function __construct(
        public bool $success,
        public string|null $providerRef,
        public array $payload
    ) {}

    public static function success($ref, $payload) {
        return new static(true, $ref, $payload);
    }

    public static function failed($payload) {
        return new static(false, null, $payload);
    }
}
```

---

## 🔄 Controller Flow (Universal)

```
public function confirm($provider) {
    $order = Order::find(request('order_id'));
    $payment = Payment::firstOrCreate([
        'order_id' => $order->id,
        'provider' => $provider,
    ], ['status' => 'pending']);

    $service = PaymentFactory::make($provider);
    $result = $service->verify(request()->all());

    if ($result->success) {
        $payment->markCompleted($result->payload);
        return response()->json(['success' => true]);
    }

    $payment->markFailed($result->payload);
    return response()->json(['success' => false]);
}
```

---

## 🔥 `PaymentCompleted` Event Listener Chain

Triggered automatically from `$payment->markCompleted()`

| Listener                       | Purpose                          |
| ------------------------------ | -------------------------------- |
| `MarkOrderPaid`                | sets order status → `paid`       |
| `ReleaseReservedStock`         | commits reserved → sold          |
| `RecordFinance`                | writes `a2_ec_order_finance` row |
| `SendOrderReceiptNotification` | email/SMS/WhatsApp               |
| `ClearCart`                    | purge session + reserved stock   |

No gateway code touches business logic — events handle everything.

---

## 🧪 Sample Test Cases — `PaymentCompleted`

```
test('Order marked paid when PaymentCompleted event fired', function() {
    $order = Order::factory()->create(['status' => 'pending']);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status'   => 'completed',
    ]);

    event(new PaymentCompleted($payment));

    expect($order->fresh()->status)->toBe('paid');
});
```

```
test('Stock released on payment completion', function() {
    $payment = Payment::factory()->create(['status' => 'completed']);

    event(new PaymentCompleted($payment));

    expect(ReservedStock::count())->toBe(0);
});
```

```
test('Finance entry recorded after payment completion', function() {
    $payment = Payment::factory()->create(['status' => 'completed']);

    event(new PaymentCompleted($payment));

    expect(OrderFinance::where('order_id', $payment->order_id)->exists())->toBeTrue();
});
```

---

## 💡 Why this design scales

| Gateway          | Integration work required | Business logic changes |
| ---------------- | ------------------------- | ---------------------- |
| PayPal           | write service             | none                   |
| M‑Pesa           | write service             | none                   |
| Pesapal          | write service             | none                   |
| Lemon Squeezy    | write service             | none                   |
| Cash on Delivery | simple manual call        | none                   |

**A2 never changes when a new payment provider is added — only the gateway verifier is added.**

---

### End of File — Safe for implementation and extension.

---

## 🧩 WINS‑JS Payment Authority Specifications (Current Mode)

A2 currently treats **JavaScript confirmation as the immediate authority** for marking a payment as `completed`. The webhook is **secondary** and used only for reconciliation.

### 🔥 State Rules in WINS‑JS Mode

| Condition                     | Payment Status                        | Order Status               |
| ----------------------------- | ------------------------------------- | -------------------------- |
| JS confirms + no webhook yet  | `completed`                           | `paid`                     |
| Webhook later confirms        | No change                             | No change                  |
| Webhook later disputes/denies | `disputed`                            | `suspended_payment_review` |
| Webhook arrives before JS     | Mark as `processing` until JS arrives |                            |

### 🚫 Double‑charge protection

Before marking payment `completed`, check:

```
Payment::where('provider_ref', $transactionId)->exists();
```

If exists → **skip JS confirmation** and return order summary.

---

## 🔥 PayPal Webhook Handler (WINS‑JS Mode)

Webhook endpoint receives `paypal_webhook_id` and payload.

### Validation

1. Verify signature from headers
2. Verify webhook `event_type`
3. Verify order amount matches A2 record
4. Verify currency matches A2 order

### Webhook Flow

```
if payment already completed (via JS):
    log webhook and exit 200
elseif webhook says COMPLETED before JS:
    set payment status = processing
    notify user: "Payment pending confirmation"
elseif webhook says FAILED:
    set payment status = failed
    set order.status = payment_failed
```

### Retry‑Safe Design

Webhooks may be sent multiple times.

```
use provider_ref as idempotency key
ignore duplicate events
log all attempts in a2_ec_payment_logs
```

---

## 📦 Queue Recommendations

All heavy payment automation must run via queue workers.

| Task                      | Mode   |
| ------------------------- | ------ |
| Deduct reserved stock     | queued |
| Clear session cart        | queued |
| Send receipt / SMS        | queued |
| Vendor payout calculation | queued |
| Analytics + reporting     | queued |

Do **not** process these synchronously in the webhook route.

> If queue workers are down → payment still marked but automation is delayed → event listeners will run once queue resumes.

---

## 🔒 Security Checklist

| Requirement                             | Status            |
| --------------------------------------- | ----------------- |
| Provider reference must be unique       | REQUIRED          |
| Payload must be stored                  | REQUIRED          |
| Signature verification for each webhook | REQUIRED          |
| Webhook must support retries            | REQUIRED          |
| Manual dispute override in admin        | OPTIONAL (future) |

Never trust frontend payment payloads — only JS ID + webhook verification against PayPal.

---

## 🔄 Migration to HYBRID Mode (Future)

WINS‑JS → HYBRID can occur without refactoring because:

- `a2_ec_payments` already tracks statuses
- `a2_ec_payment_logs` already preserves lifecycle
- Events already handle finance/stock/notifications

Upgrade path:

```
JS sets status = processing
Webhook sets status = completed
```

No API signature changes needed.

---

### 🎯 Final Summary

A2 is now correctly positioned to:

- Support **PayPal immediately using JS checkout**
- Support webhooks safely without breaking flow
- Support multi‑gateway expansion with a unified payment pipeline

This chapter is now **complete and production‑ready for PayPal rollout** and can later support **M‑Pesa, Pesapal, Lemon Squeezy, Cash on Delivery** with zero structural changes.

---
