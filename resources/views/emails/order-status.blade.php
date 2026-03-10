<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ ucfirst($status) }} — {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            padding: 40px 16px;
            color: #1a1a1a;
        }

        .wrapper {
            max-width: 580px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #2e7d32;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #1a1a1a;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            padding: 32px 40px 28px;
            border-bottom: 1px solid #f0f0f0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .badge-confirmed  { background: #e8f5e9; color: #2e7d32; }
        .badge-shipped    { background: #e3f2fd; color: #1565c0; }
        .badge-delivered  { background: #e8f5e9; color: #1b5e20; }
        .badge-cancelled  { background: #fce4ec; color: #c62828; }
        .badge-rejected   { background: #fce4ec; color: #c62828; }
        .badge-pending    { background: #fff8e1; color: #e65100; }
        .badge-placed     { background: #f3e5f5; color: #6a1b9a; }

        .card-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.3;
            margin-bottom: 8px;
        }

        .card-header p {
            font-size: 15px;
            color: #666;
            line-height: 1.6;
        }

        .card-body {
            padding: 32px 40px;
        }

        .order-summary {
            background: #f9fafb;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
            border: 1px solid #eeeeee;
        }

        .summary-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eeeeee;
            font-size: 14px;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-row:first-of-type {
            padding-top: 0;
        }

        .summary-row .label {
            color: #888;
        }

        .summary-row .value {
            font-weight: 600;
            color: #1a1a1a;
        }

        .summary-row .value.total {
            font-size: 16px;
            color: #2e7d32;
        }

        .reason-box {
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 24px;
            border-left: 4px solid #c62828;
            background: #fff5f5;
        }

        .reason-box .reason-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #c62828;
            margin-bottom: 6px;
        }

        .reason-box p {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }

        .cta-wrapper {
            text-align: center;
            margin: 28px 0 8px;
        }

        .cta-button {
            display: inline-block;
            background: #2e7d32;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .divider {
            border: none;
            border-top: 1px solid #f0f0f0;
            margin: 28px 0;
        }

        .help-text {
            font-size: 13px;
            color: #999;
            line-height: 1.6;
            text-align: center;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #bbb;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- Header --}}
        <div class="header">
            <div class="logo">🛒 Buy<span>Local</span></div>
        </div>

        <div class="card">

            {{-- Card header --}}
            <div class="card-header">
                @php
                    $badgeClass = match($status) {
                        'confirmed'  => 'badge-confirmed',
                        'shipped'    => 'badge-shipped',
                        'delivered'  => 'badge-delivered',
                        'cancelled'  => 'badge-cancelled',
                        'rejected'   => 'badge-rejected',
                        'placed'     => 'badge-placed',
                        default      => 'badge-pending',
                    };

                    $emoji = match($status) {
                        'confirmed'  => '✅',
                        'shipped'    => '📦',
                        'delivered'  => '🎉',
                        'cancelled'  => '❌',
                        'rejected'   => '🚫',
                        'placed'     => '🛍️',
                        default      => '🕐',
                    };

                    $headline = match($status) {
                        'confirmed'  => 'Your order has been confirmed',
                        'shipped'    => 'Your order is on its way',
                        'delivered'  => 'Your order has been delivered',
                        'cancelled'  => 'Order cancelled',
                        'rejected'   => 'Order rejected',
                        'placed'     => 'New order received',
                        default      => 'Order update',
                    };

                    $subtext = match($status) {
                        'confirmed'  => "Great news! The seller has confirmed your order and is preparing it.",
                        'shipped'    => "Your order is on its way. You'll receive it soon.",
                        'delivered'  => "Your order has been marked as delivered. Enjoy!",
                        'cancelled'  => "Your order has been cancelled.",
                        'rejected'   => "Unfortunately, the seller was unable to fulfill your order.",
                        'placed'     => "You have received a new order from a buyer. Review it and confirm or reject.",
                        default      => "Your order status has been updated.",
                    };
                @endphp

                <div class="status-badge {{ $badgeClass }}">
                    {{ $emoji }} {{ ucfirst($status) }}
                </div>

                <h1>{{ $headline }}</h1>
                <p>{{ $subtext }}</p>
            </div>

            {{-- Card body --}}
            <div class="card-body">

                {{-- Order summary --}}
                <div class="order-summary">
                    <div class="summary-title">Order Summary</div>

                    <div class="summary-row">
                        <span class="label">Order number</span>
                        <span class="value">{{ $order->order_number }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Status</span>
                        <span class="value">{{ ucfirst($status) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Items</span>
                        <span class="value">{{ $order->items->count() }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Total</span>
                        <span class="value total">
                            {{ number_format($order->total, 2) }} {{ $order->currency }}
                        </span>
                    </div>
                </div>

                {{-- Reason box (cancelled / rejected) --}}
                @if ($reason)
                <div class="reason-box">
                    <div class="reason-title">
                        {{ $status === 'cancelled' ? 'Cancellation reason' : 'Rejection reason' }}
                    </div>
                    <p>{{ $reason }}</p>
                </div>
                @endif

                {{-- CTA button --}}
                <div class="cta-wrapper">
                    <a href="{{ $orderUrl }}" class="cta-button">
                        View Order
                    </a>
                </div>

                <hr class="divider">

                <p class="help-text">
                    If you have any questions, please reply to this email or contact our support team.
                </p>

            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>© {{ date('Y') }} Buy Local. All rights reserved.</p>
            <p>You're receiving this email because you have an account on Buy Local.</p>
        </div>

    </div>
</body>
</html>