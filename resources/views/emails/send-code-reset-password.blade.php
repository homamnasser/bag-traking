<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color: #f2f3f8;">

<div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.06);">

    <!-- Header -->
    <div style="padding:20px; text-align:center;">
        <h1 style="color:#20e277; font-weight:700; margin:0; font-size:28px; font-family: 'Arial Black', Arial, sans-serif;">
            BE HEALTHY
        </h1>
    </div>

    <!-- Body -->
    <div style="padding:30px; text-align:center;">
        <h1 style="color:#333333; margin-top:0;">Password Reset Request</h1>
        <p style="color:#000000; margin:0;">Hello, we received a request to reset your password.</p>
        <p style="color:#000000; margin:0;">Please use the code below to proceed. The code is valid for
            <strong style="color:#20e277;">5 minutes</strong>.
        </p>

        <!-- Reset Code -->
        <div style="display:inline-block; background:#f2f3f8; color:#111111; font-size:28px; letter-spacing:4px; font-weight:bold; padding:12px 24px; border-radius:8px; margin:20px 0; border:2px dashed #20e277;">
            {{ $code }}
        </div>

        <p style="color:#000000;">If you didn’t request this, you can ignore this email.</p>
    </div>

    <!-- Footer -->
    <div style="background-color:#f2f3f8; text-align:center; padding:15px; font-size:12px; color:#000000;">
        &copy; {{ date('Y') }} BE HEALTHY. All rights reserved.
    </div>

</div>

</body>
</html>
