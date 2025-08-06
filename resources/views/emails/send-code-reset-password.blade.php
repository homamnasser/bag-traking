<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
    <style>
        body {
            background-color: #f2f3f8;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }
        .email-header {
            background-color: #20e277;
            padding: 20px;
            text-align: center;
        }
        .email-header img {
            width: 80px;
        }
        .email-body {
            padding: 30px;
            text-align: center;
        }
        .email-body h1 {
            color: #333333;
        }
        .reset-code {
            display: inline-block;
            background: #f2f3f8;
            color: #111111;
            font-size: 28px;
            letter-spacing: 4px;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #20e277;
        }
        .email-footer {
            background-color: #f2f3f8;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #888888;
        }
    </style>
</head>
<body>

<div class="email-container">
    <!-- Header -->
    <div class="email-header">
        <img src="{{ asset('images/images_1754300066_68907ea24a1cb.png') }}"
             alt="App Logo"
             style="width: 80px; height: auto; display: block; margin: 0 auto;">
    </div>

    <!-- Body -->
    <div class="email-body">
        <h1>Password Reset Request</h1>
        <p>Hello, we received a request to reset your password.</p>
        <p>Please use the code below to proceed. The code is valid for <strong>5 minutes</strong>.</p>

        <!-- Reset Code -->
        <div class="reset-code">
            {{ $code }}
        </div>

        <p>If you didn’t request this, you can ignore this email.</p>
    </div>

    <!-- Footer -->
    <div class="email-footer">
        &copy; {{ date('Y') }} BE HEALTHY. All rights reserved.
    </div>
</div>

</body>
</html>
