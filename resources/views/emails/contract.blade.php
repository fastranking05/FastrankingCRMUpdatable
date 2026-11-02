<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1a1a2e; font-size: 14px; line-height: 1.6;">
    @if($showGreeting)
        <p>Dear {{ $contactName }},</p>
    @endif

    <p>{!! nl2br(e($emailMessage)) !!}</p>

    @if($signUrl)
        <p>
            <a href="{{ $signUrl }}" target="_blank"
               style="display:inline-block; background:#1a0533; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold; font-size:14px;">
                &#128196; Review &amp; E-Sign Contract
            </a>
        </p>
        <p style="font-size:12px; color:#8a8494;">
            Or open this link in your browser:<br>
            <a href="{{ $signUrl }}" style="color:#a922e9; word-break:break-all;">{{ $signUrl }}</a>
        </p>
    @elseif($pdfPath)
        <p>The contract document is attached to this email as a PDF.</p>
    @endif

    @if($showSignOff)
        <p>
            Best regards,<br>
            <strong>{{ $companyName }}</strong>
        </p>
    @endif
</body>
</html>
