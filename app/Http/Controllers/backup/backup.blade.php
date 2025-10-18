@extends('mail.layout')

@section('content')
<div style="padding: 20px; background-color: #f8f9fa;">
    <h2 style="color: #333;">Subscription Update for {{ $mailData['branch_name'] }}</h2>
    
    <div style="background-color: #fff; padding: 20px; border-radius: 5px; margin-top: 20px;">
        <p>Dear {{ $mailData['recipient_name'] }},</p>
        
        <p>This email is to inform you about a subscription update for the branch: <strong>{{ $mailData['branch_name'] }}</strong></p>

        <div style="margin: 20px 0;">
            <h3 style="color: #555;">Subscription Details:</h3>
            <ul style="list-style: none; padding-left: 0;">
                <li>Package: <strong>{{ $mailData['package_name'] }}</strong></li>
                <li>Start Date: <strong>{{ $mailData['start_date'] }}</strong></li>
                <li>End Date: <strong>{{ $mailData['end_date'] }}</strong></li>
                <li>Status: <strong>{{ $mailData['status'] }}</strong></li>
            </ul>
        </div>

        @if(isset($mailData['payment_details']))
        <div style="margin: 20px 0;">
            <h3 style="color: #555;">Payment Details:</h3>
            <ul style="list-style: none; padding-left: 0;">
                <li>Amount: <strong>{{ $mailData['payment_details']['amount'] }}</strong></li>
                <li>Payment Method: <strong>{{ $mailData['payment_details']['method'] }}</strong></li>
                <li>Transaction ID: <strong>{{ $mailData['payment_details']['transaction_id'] }}</strong></li>
            </ul>
        </div>
        @endif

        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>

        <p>Best regards,<br>{{ config('app.name') }} Team</p>
    </div>
</div>
@endsection


<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
        }
        .logo {
            max-width: 200px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ assetImage(readconfig('site_logo')) }}" alt="{{ config('app.name') }}" class="logo">
        </div>

        @yield('content')

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>{{ readConfig('address') }}</p>
        </div>
    </div>
</body>
</html>
