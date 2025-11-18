<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .notification-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-top: 0;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin: 15px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="notification-container">
        <h1>Hello, {{ $to_user->det->pud_first_name }}!</h1>
        {!! $content !!}

        @if(isset($action))
            <br>
            <div style="text-align: center;">
                <a href="{{ $action }}" class="button">{{ 'View Post' }}</a>
            </div>
        @endif
    </div>
</body>

</html>