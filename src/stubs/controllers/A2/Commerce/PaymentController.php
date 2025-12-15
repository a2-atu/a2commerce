<?php

namespace App\Http\Controllers\A2\Commerce;

use App\Http\Controllers\Controller;
use App\Models\A2\Commerce\A2Order;
use App\Models\A2\Commerce\A2Payment;
use App\Services\A2\Commerce\PaymentService;
use App\Services\A2\Commerce\PayPalPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PayPalPaymentService $paypalService;

    public function __construct(PaymentService $paymentService, PayPalPaymentService $paypalService)
    {
        $this->paymentService = $paymentService;
        $this->paypalService = $paypalService;
    }

    /**
     * Initialize PayPal payment
     */
    public function initPayPal(Request $request, ?int $orderId = null)
    {
        $orderId = $orderId ?? $request->input('order_id');

        if (!$orderId) {
            return response()->json(['error' => 'Order ID is required'], 400);
        }

        $order = A2Order::findOrFail($orderId);

        // Check if payment already exists
        $existingPayment = A2Payment::where('order_id', $order->id)
            ->where('method', 'paypal')
            ->where('status', 'completed')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'payment_id' => $existingPayment->id,
                'order_id' => $order->id,
                'message' => 'Payment already completed',
            ]);
        }

        // Create payment record
        $payment = $this->paymentService->createPayment($order, 'paypal', $order->total);

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $order->total,
            'client_id' => config('a2_commerce.paypal.client_id', env('A2_PAYPAL_CLIENT_ID')),
            'mode' => config('a2_commerce.paypal.mode', env('A2_PAYPAL_MODE', 'sandbox')),
        ]);
    }

    /**
     * Confirm PayPal payment (from JavaScript callback)
     */
    public function confirmPayPal(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:a2_ec_orders,id',
                'paypal_order_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('PayPal confirmation validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request' => $request->all(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid request: ' . $validator->errors()->first(),
                ], 400);
            }

            $order = A2Order::findOrFail($request->order_id);

            // Check for double-charge protection
            $existingPayment = A2Payment::where('transaction_code', $request->paypal_order_id)
                ->where('status', 'completed')
                ->first();

            if ($existingPayment) {
                Log::info('PayPal payment already processed', [
                    'order_id' => $order->id,
                    'payment_id' => $existingPayment->id,
                    'transaction_code' => $request->paypal_order_id,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already processed',
                    'order_id' => $order->id,
                ]);
            }

            // Verify PayPal order
            $verification = $this->paypalService->verifyOrder($request->paypal_order_id);

            if (!$verification['success']) {
                // Payment failed
                $payment = A2Payment::where('order_id', $order->id)
                    ->where('method', 'paypal')
                    ->where('status', 'pending')
                    ->first();

                if ($payment) {
                    $this->paymentService->markFailed($payment, $verification['error'] ?? 'Verification failed');
                }

                Log::warning('PayPal verification failed', [
                    'order_id' => $order->id,
                    'paypal_order_id' => $request->paypal_order_id,
                    'verification' => $verification,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $verification['error'] ?? 'Payment verification failed',
                    'verification_status' => $verification['status'] ?? null,
                ], 400);
            }

            // Get or create payment record
            $payment = A2Payment::where('order_id', $order->id)
                ->where('method', 'paypal')
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                $payment = $this->paymentService->createPayment($order, 'paypal', $order->total);
            }

            // Safely extract transaction ID from verification data
            $verificationData = $verification['data'] ?? [];
            $purchaseUnits = $verificationData['purchase_units'] ?? [];
            $firstPurchaseUnit = $purchaseUnits[0] ?? [];
            $payments = $firstPurchaseUnit['payments'] ?? [];
            $captures = $payments['captures'] ?? [];
            $firstCapture = $captures[0] ?? [];

            $transactionId = $firstCapture['id'] ?? $request->paypal_order_id;
            $invoiceId = $firstPurchaseUnit['invoice_id'] ?? null;
            $orderNumber = null;

            // Extract order number from invoice_id if it has a prefix (format: PPABC123-ORD12345)
            if ($invoiceId && strpos($invoiceId, '-') !== false) {
                $parts = explode('-', $invoiceId, 2);
                if (count($parts) === 2 && strpos($parts[0], 'PP') === 0) {
                    $orderNumber = $parts[1]; // This is the actual order number
                }
            }

            // Mark payment as completed
            $this->paymentService->markCompleted($payment, [
                'transaction_code' => $transactionId,
                'paypal_order_id' => $request->paypal_order_id,
                'paypal_invoice_id' => $invoiceId,
                'order_number' => $orderNumber, // Store extracted order number for reference
                'verification_data' => $verificationData,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal confirmation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment confirmation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PayPal webhook handler
     */
    public function webhookPayPal(Request $request)
    {
        try {
            // TODO: Verify webhook signature
            // For now, just log the webhook

            $eventType = $request->input('event_type');
            $resource = $request->input('resource', []);

            Log::info('PayPal webhook received', [
                'event_type' => $eventType,
                'resource' => $resource,
            ]);

            // Handle different event types
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                $transactionId = $resource['id'] ?? null;
                if ($transactionId) {
                    $payment = $this->paymentService->getByTransactionCode($transactionId);
                    if ($payment && $payment->status === 'pending') {
                        $this->paymentService->markCompleted($payment, [
                            'transaction_code' => $transactionId,
                            'webhook_data' => $resource,
                        ]);
                    }
                }
            } elseif ($eventType === 'PAYMENT.CAPTURE.DENIED' || $eventType === 'PAYMENT.CAPTURE.REFUNDED') {
                $transactionId = $resource['id'] ?? null;
                if ($transactionId) {
                    $payment = $this->paymentService->getByTransactionCode($transactionId);
                    if ($payment) {
                        $this->paymentService->markFailed($payment, $eventType, [
                            'transaction_code' => $transactionId,
                            'webhook_data' => $resource,
                        ]);
                    }
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Payment success redirect
     */
    public function success(Request $request)
    {
        $orderId = $request->query('order');
        $provider = $request->query('provider', 'paypal');

        if ($orderId) {
            $order = A2Order::with('addresses')->find($orderId);
            if ($order) {
                // Clear cache to ensure fresh order data
                Cache::flush();

                // Check if authenticated user order
                if ($order->user_id && Auth::check() && $order->user_id === Auth::id()) {
                    return redirect()->route('account.orders.show', ['orderNumber' => $order->order_number])
                        ->with('success', 'Payment completed successfully!');
                } else {
                    // Guest order - redirect to guest order view
                    // Try shipping address first, then billing address
                    $shippingAddress = $order->addresses->where('type', 'shipping')->first();
                    $billingAddress = $order->addresses->where('type', 'billing')->first();

                    $email = $shippingAddress->email ?? $billingAddress->email ?? null;

                    if ($email) {
                        return redirect()->route('order.guest.show', ['orderNumber' => $order->order_number, 'email' => urlencode($email)])
                            ->with('success', 'Payment completed successfully!');
                    } else {
                        // Email not found - log error but still redirect to home with success message
                        Log::warning('Payment success but email not found for guest order', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'has_shipping_address' => $shippingAddress !== null,
                            'has_billing_address' => $billingAddress !== null,
                        ]);
                        // Still redirect to home with success message since payment was successful
                        return redirect()->route('home')
                            ->with('success', 'Payment completed successfully! Your order number is: ' . $order->order_number);
                    }
                }
            } else {
                Log::warning('Payment success but order not found', [
                    'order_id' => $orderId,
                ]);
            }
        }

        return redirect()->route('home')->with('success', 'Payment completed successfully!');
    }

    /**
     * Payment failure redirect
     */
    public function failed(Request $request)
    {
        $orderId = $request->query('order');
        $provider = $request->query('provider', 'paypal');

        if ($orderId) {
            $order = A2Order::find($orderId);
            if ($order) {
                // Redirect back to checkout with error
                return redirect()->route('checkout.index')
                    ->with('error', 'Payment failed. Please try again.');
            }
        }

        return redirect()->route('checkout.index')->with('error', 'Payment failed. Please try again.');
    }
}
