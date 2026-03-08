<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 48px 40px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .icon {
            width: 72px;
            height: 72px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
        }
        h1 { font-size: 24px; color: #1a1a1a; margin-bottom: 12px; }
        p { font-size: 15px; color: #666; line-height: 1.6; margin-bottom: 32px; }
        .btn {
            display: inline-block;
            background: #2e7d32;
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>Email Verified!</h1>
        <p>Your account has been successfully verified. You can now sign in to Buy Local.</p>
        <a href="#" class="btn" onclick="window.close()">Close this tab</a>
    </div>
</body>
</html>