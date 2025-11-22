<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }
        .otp-box {
            background-color: #fff;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #000;
            margin: 10px 0;
        }
        .expiry-text {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">MONOCHROME NEWS</div>
            <p>Email Verification</p>
        </div>

        <p>Hello,</p>
        
        <p>You have requested to verify your email address. Please use the following One-Time Password (OTP) to complete your verification:</p>

        <div class="otp-box">
            <div>Your OTP Code:</div>
            <div class="otp-code">{{ $otp }}</div>
            <div class="expiry-text">This code will expire in {{ $expiryMinutes }} minutes</div>
        </div>

        <div class="warning">
            <strong>Security Notice:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Never share this code with anyone</li>
                <li>Our team will never ask for your OTP</li>
                <li>If you didn't request this code, please ignore this email</li>
            </ul>
        </div>

        <p>If you have any questions or concerns, please contact our support team.</p>

        <p>Best regards,<br>
        <strong>Monochrome News Team</strong></p>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} Monochrome News. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

