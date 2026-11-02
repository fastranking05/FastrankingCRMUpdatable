<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a2e; font-size: 14px; line-height: 1.6;">
    <p>Dear {{ $contactName }},</p>

    <p>Please find attached the business proposal <strong>{{ $proposal->id }}</strong> prepared for <strong>{{ $businessName }}</strong>.</p>

    <p>
        Total value: <strong>£{{ number_format($totalValue, 2) }}</strong> (including VAT).<br>
        The attached document contains the full breakdown of selected services.
    </p>

    <p>Please review the proposal and contact us if you have any questions. We look forward to working with you.</p>

    <p>
        Best regards,<br>
        <strong>{{ $companyName }}</strong>
    </p>
</body>
</html>
