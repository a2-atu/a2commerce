<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - Order #{{ $order->order_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h1 style="color: #1a1a1a; margin-top: 0;">Payment Confirmed!</h1>
        <p style="font-size: 16px; margin-bottom: 0;">Thank you for your payment. Your order has been successfully processed.</p>
    </div>

    <div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Order Details</h2>
        
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="padding: 8px 0; color: #666;"><strong>Order Number:</strong></td>
                <td style="padding: 8px 0; text-align: right;"><strong>{{ $order->order_number }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;"><strong>Order Date:</strong></td>
                <td style="padding: 8px 0; text-align: right;">{{ $order->created_at->format('F d, Y h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;"><strong>Payment Method:</strong></td>
                <td style="padding: 8px 0; text-align: right;">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;"><strong>Transaction ID:</strong></td>
                <td style="padding: 8px 0; text-align: right; font-family: monospace; font-size: 12px;">{{ $payment->transaction_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;"><strong>Total Amount:</strong></td>
                <td style="padding: 8px 0; text-align: right; font-size: 18px; font-weight: bold; color: #dc2626;">{{ config('a2.currency_symbol') }} {{ number_format($order->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($order->items && $order->items->count() > 0)
    <div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Order Items</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #e0e0e0;">Product</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #e0e0e0;">Quantity</th>
                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #e0e0e0;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #f0f0f0;">{{ $item->product->name ?? 'Product' }}</td>
                    <td style="padding: 10px; text-align: center; border-bottom: 1px solid #f0f0f0;">{{ $item->quantity }}</td>
                    <td style="padding: 10px; text-align: right; border-bottom: 1px solid #f0f0f0;">{{ config('a2.currency_symbol') }} {{ number_format($item->price * $item->quantity, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @php
        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
    @endphp
    @if($shippingAddress)
    <div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Shipping Address</h2>
        <p style="margin: 5px 0;">
            {{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}<br>
            {{ $shippingAddress->address_line }}<br>
            {{ $shippingAddress->city }}, {{ $shippingAddress->country }}<br>
            @if($shippingAddress->postal_code){{ $shippingAddress->postal_code }}<br>@endif
            Phone: {{ $shippingAddress->phone }}
        </p>
    </div>
    @endif

    <div style="background-color: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <p style="margin: 0; color: #1e40af;">
            <strong>What's next?</strong><br>
            Your order is being processed and will be shipped soon. You will receive another email with tracking information once your order ships.
        </p>
    </div>

    @if($order->user_id)
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" 
           style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
            View Order Details
        </a>
    </div>
    @else
    @php
        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
        $email = $shippingAddress->email ?? '';
    @endphp
    @if($email)
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ route('order.guest.show', ['orderNumber' => $order->order_number, 'email' => urlencode($email)]) }}" 
           style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
            View Order Details
        </a>
    </div>
    @endif
    @endif

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px;">
        <p>If you have any questions about your order, please contact our support team.</p>
        <p style="margin-top: 10px;">Thank you for shopping with us!</p>
    </div>
</body>
</html>

