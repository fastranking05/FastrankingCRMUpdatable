<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a2e; font-size: 14px; line-height: 1.6;">
    <p>Dear {{ $contactName }},</p>

    <p>Please find attached invoice <strong>{{ $invoice->id }}</strong> issued to <strong>{{ $businessName }}</strong>.</p>

    <p>
        Total due: <strong>&pound;{{ number_format($grandTotal, 2) }}</strong> (including VAT).<br>
        @if($dueDate)
            Payment due by: <strong>{{ $dueDate }}</strong>
        @endif
    </p>

    <p>Please review the invoice and arrange payment by the due date. If you have any questions, simply reply to this email and our team will assist you.</p>

    <p>
        Best regards,<br>
        <strong>{{ $companyName }}</strong>
    </p>
</body>
</html>
