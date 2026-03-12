<!DOCTYPE html>
<html lang="en">
<head>
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
        <div class="header">
            <div class="logo">🛒 Buy<span>Local</span></div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="status-badge badge-confirmed">⭐ Favourite Update</div>
                <h1>A product you saved has been updated</h1>
                <p>One of your favourited products on BuyLocal has a new update.</p>
            </div>

            <div class="card-body">
                <div class="order-summary">
                    <div class="summary-title">Product Update</div>
                    <div class="summary-row">
                        <span class="label">Product</span>
                        <span class="value">{{ $product->name }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Change</span>
                        <span class="value">{{ $changeType }}</span>
                    </div>
                </div>

                <div class="cta-wrapper">
                    <a href="{{ $productUrl }}" class="cta-button">View Product</a>
                </div>

                <hr class="divider">

                <p class="help-text">
                    You're receiving this because you favourited this product and have
                    favourite notifications enabled. You can turn these off in your account settings.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Buy Local. All rights reserved.</p>
        </div>
    </div>
</body>
</html>