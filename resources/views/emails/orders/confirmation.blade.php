<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Green Veil Order Confirmation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f7f5f0;
            margin: 0;
            padding: 0;
            color: #2c302e;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e7e2d6;
        }
        .header {
            background-color: #2d4030;
            padding: 30px 20px;
            text-align: center;
            color: #f7f5f0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 500;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 13px;
            color: #c9d5ca;
        }
        .content {
            padding: 30px 25px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 12px;
            font-weight: 600;
            color: #1f2d22;
        }
        .order-meta {
            background-color: #f3efe6;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.6;
        }
        .instructions-box {
            background-color: #eef3ef;
            border-left: 4px solid #3c5440;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.5;
            color: #243527;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }
        .items-table th {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid #e7e2d6;
            color: #606864;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }
        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f0ecdf;
            vertical-align: top;
        }
        .totals-table {
            width: 100%;
            margin-top: 15px;
            font-size: 13px;
        }
        .totals-table td {
            padding: 6px 8px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: 700;
            border-top: 2px solid #2d4030;
            color: #1f2d22;
        }
        .address-box {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e7e2d6;
            font-size: 13px;
            line-height: 1.5;
            color: #4b524e;
        }
        .footer {
            background-color: #fbfaf8;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #8c928e;
            border-top: 1px solid #e7e2d6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Green Veil</h1>
            <p>Conscious Botanical Apothecary</p>
        </div>

        <div class="content">
            <div class="greeting">
                Dear {{ $order->customer_name }},
            </div>
            <p style="font-size: 14px; line-height: 1.6; margin: 0 0 16px;">
                Thank you for your order. We are carefully preparing your artisanal skincare and botanical wellness rituals.
            </p>

            <div class="order-meta">
                <strong>Order Reference:</strong> #{{ $order->number }}<br>
                <strong>Date:</strong> {{ $order->placed_at ? $order->placed_at->format('F j, Y, g:i a') : now()->format('F j, Y') }}<br>
                <strong>Payment Method:</strong> {{ $order->paymentOption?->name ?? 'Direct Payment' }}<br>
                <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $order->status->value)) }} ({{ ucfirst($order->payment_status->value) }})
            </div>

            @if(!empty($order->paymentOption?->instructions))
                <div class="instructions-box">
                    <strong>Payment Instructions:</strong><br>
                    {!! nl2br(e($order->paymentOption->instructions)) !!}
                </div>
            @endif

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product_name }}</strong>
                                @if(!empty($item->product_sku))
                                    <div style="font-size: 11px; color: #888;">SKU: {{ $item->product_sku }}</div>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">&euro;{{ number_format($item->unit_price, 2) }}</td>
                            <td style="text-align: right;">&euro;{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals-table">
                <tr>
                    <td style="text-align: right;">Subtotal:</td>
                    <td style="text-align: right; width: 100px;">&euro;{{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                    <tr style="color: #2e6930;">
                        <td style="text-align: right;">Botanical Discount:</td>
                        <td style="text-align: right;">-&euro;{{ number_format($order->discount_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="text-align: right;">Delivery ({{ $order->deliveryOption?->name ?? 'Standard' }}):</td>
                    <td style="text-align: right;">
                        @if($order->delivery_amount == 0)
                            FREE
                        @else
                            &euro;{{ number_format($order->delivery_amount, 2) }}
                        @endif
                    </td>
                </tr>
                <tr class="grand-total">
                    <td style="text-align: right; padding-top: 10px;">Grand Total:</td>
                    <td style="text-align: right; padding-top: 10px;">&euro;{{ number_format($order->total, 2) }}</td>
                </tr>
            </table>

            <div class="address-box">
                <strong>Delivery Destination:</strong><br>
                {{ $order->customer_name }}<br>
                {{ $order->shipping_address['line1'] ?? '' }}<br>
                @if(!empty($order->shipping_address['line2']))
                    {{ $order->shipping_address['line2'] }}<br>
                @endif
                {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['postal_code'] ?? '' }}<br>
                {{ $order->shipping_address['country'] ?? '' }}
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 6px;">Questions about your order? Reach our apothecary team at care@greenveil.test</p>
            <p style="margin: 0;">&copy; {{ date('Y') }} Green Veil Botanical Apothecary. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
