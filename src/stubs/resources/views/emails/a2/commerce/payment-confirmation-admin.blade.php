<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>New Order Paid - Order #{{ $order->order_number }}</title>
	</head>

	<body
		style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 700px; margin: 0 auto; padding: 20px;">
		<div style="background-color: #dc2626; color: #ffffff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
			<h1 style="margin-top: 0; color: #ffffff;">New Order Paid!</h1>
			<p style="font-size: 16px; margin-bottom: 0;">A customer has completed payment for their order.</p>
		</div>

		<div
			style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
			<h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Order Information
			</h2>

			<table style="width: 100%; margin-bottom: 20px;">
				<tr>
					<td style="padding: 8px 0; color: #666; width: 40%;"><strong>Order Number:</strong></td>
					<td style="padding: 8px 0;"><strong>{{ $order->order_number }}</strong></td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Order Date:</strong></td>
					<td style="padding: 8px 0;">{{ $order->created_at->format('F d, Y h:i A') }}</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Order Status:</strong></td>
					<td style="padding: 8px 0;"><span
							style="background-color: #10b981; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase;">{{ ucfirst($order->status) }}</span>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Payment Status:</strong></td>
					<td style="padding: 8px 0;"><span
							style="background-color: #10b981; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase;">{{ ucfirst($order->payment_status) }}</span>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Payment Method:</strong></td>
					<td style="padding: 8px 0;">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Transaction ID:</strong></td>
					<td style="padding: 8px 0; font-family: monospace; font-size: 12px;">{{ $payment->transaction_code ?? 'N/A' }}</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Total Amount:</strong></td>
					<td style="padding: 8px 0; font-size: 18px; font-weight: bold; color: #dc2626;">
						{{ config('a2_commerce.currency_symbol') }} {{ number_format($order->total, 2) }}</td>
				</tr>
			</table>
		</div>

		@php
			$shippingAddress = $order->addresses->where('type', 'shipping')->first();
			$customerEmail = $order->user ? $order->user->email : ($shippingAddress ? $shippingAddress->email : 'N/A');
			$customerName = $order->user
			    ? $order->user->name
			    : ($shippingAddress
			        ? $shippingAddress->first_name . ' ' . $shippingAddress->last_name
			        : 'Guest Customer');
		@endphp

		<div
			style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
			<h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Customer
				Information</h2>

			<table style="width: 100%;">
				<tr>
					<td style="padding: 8px 0; color: #666; width: 40%;"><strong>Customer Name:</strong></td>
					<td style="padding: 8px 0;">{{ $customerName }}</td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Customer Email:</strong></td>
					<td style="padding: 8px 0;"><a href="mailto:{{ $customerEmail }}"
							style="color: #3b82f6;">{{ $customerEmail }}</a></td>
				</tr>
				<tr>
					<td style="padding: 8px 0; color: #666;"><strong>Account Type:</strong></td>
					<td style="padding: 8px 0;">{{ $order->user_id ? 'Registered User' : 'Guest Checkout' }}</td>
				</tr>
			</table>
		</div>

		@if ($shippingAddress)
			<div
				style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
				<h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Shipping Address
				</h2>
				<p style="margin: 5px 0;">
					{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}<br>
					{{ $shippingAddress->address_line }}<br>
					{{ $shippingAddress->city }}, {{ $shippingAddress->country }}<br>
					@if ($shippingAddress->postal_code)
						{{ $shippingAddress->postal_code }}<br>
					@endif
					Phone: {{ $shippingAddress->phone }}<br>
					Email: {{ $shippingAddress->email }}
				</p>
			</div>
		@endif

		@if ($order->items && $order->items->count() > 0)
			<div
				style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
				<h2 style="color: #1a1a1a; margin-top: 0; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">Order Items</h2>

				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background-color: #f8f9fa;">
							<th style="padding: 10px; text-align: left; border-bottom: 1px solid #e0e0e0;">Product</th>
							<th style="padding: 10px; text-align: center; border-bottom: 1px solid #e0e0e0;">Quantity</th>
							<th style="padding: 10px; text-align: right; border-bottom: 1px solid #e0e0e0;">Unit Price</th>
							<th style="padding: 10px; text-align: right; border-bottom: 1px solid #e0e0e0;">Total</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($order->items as $item)
							<tr>
								<td style="padding: 10px; border-bottom: 1px solid #f0f0f0;">{{ $item->product->name ?? 'Product' }}</td>
								<td style="padding: 10px; text-align: center; border-bottom: 1px solid #f0f0f0;">{{ $item->quantity }}</td>
								<td style="padding: 10px; text-align: right; border-bottom: 1px solid #f0f0f0;">
									{{ config('a2_commerce.currency_symbol') }} {{ number_format($item->price, 2) }}</td>
								<td style="padding: 10px; text-align: right; border-bottom: 1px solid #f0f0f0;">
									{{ config('a2_commerce.currency_symbol') }} {{ number_format($item->price * $item->quantity, 2) }}</td>
							</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td colspan="3" style="padding: 10px; text-align: right; font-weight: bold; border-top: 2px solid #e0e0e0;">
								Total:</td>
							<td
								style="padding: 10px; text-align: right; font-weight: bold; font-size: 16px; color: #dc2626; border-top: 2px solid #e0e0e0;">
								{{ config('a2_commerce.currency_symbol') }} {{ number_format($order->total, 2) }}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		@endif

		<div
			style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
			<p style="margin: 0; color: #92400e;">
				<strong>Action Required:</strong><br>
				Please process this order and prepare it for shipping. The customer has already completed payment.
			</p>
		</div>

		<div style="text-align: center; margin-top: 30px;">
			<a href="{{ url('/admin/orders/' . $order->id) }}"
				style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
				View Order in Admin Panel
			</a>
		</div>

		<div
			style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px;">
			<p>This is an automated notification. Please do not reply to this email.</p>
		</div>
	</body>

</html>
