<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Access Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            padding: 0;
            max-width: 600px;
            margin: 20px;
            overflow: hidden;
        }

        .error-header {
            background: #dc3545;
            color: white;
            padding: 40px;
            text-align: center;
        }

        .error-header i {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .error-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .error-body {
            padding: 40px;
            text-align: center;
        }

        .error-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }

        .btn-return {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }

        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .support-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #999;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="error-container fade-in">
        <div class="error-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>{{ $message ?? 'Access Error' }}</h1>
        </div>

        <div class="error-body">
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">
                {{ $details ?? $error ?? 'The attendance code you are trying to access is not valid or has expired.' }}
            </p>

            <div class="error-details">
                <p><strong>Possible reasons:</strong></p>
                <ul class="list-unstyled mt-3">
                    <li><i class="fas fa-times-circle text-danger me-2"></i> The QR code has expired</li>
                    <li><i class="fas fa-times-circle text-danger me-2"></i> The code has been deactivated</li>
                    <li><i class="fas fa-times-circle text-danger me-2"></i> Invalid QR code scanned</li>
                    <li><i class="fas fa-times-circle text-danger me-2"></i> The link is incorrect</li>
                </ul>
            </div>

            @if(isset($session_date) && isset($current_date))
                <div class="alert alert-warning text-start">
                    <strong><i class="fas fa-calendar-times me-2"></i>Session Details:</strong><br>
                    Session Date: {{ $session_date }}<br>
                    Current Date: {{ $current_date }}
                </div>
            @endif

            @if(isset($current_time))
                <div class="alert alert-info text-start">
                    <strong><i class="fas fa-clock me-2"></i>Access Hours:</strong><br>
                    Available: 5:00 AM - 6:00 PM<br>
                    Current Time: {{ $current_time }}
                </div>
            @endif

            <a href="{{ url('/public/attendance') }}" class="btn-return">
                <i class="fas fa-home me-2"></i>Return to Home
            </a>

            <div class="support-info">
                <p>
                    <i class="fas fa-info-circle me-2"></i>
                    If you believe this is an error, please contact your teacher or administrator for a new access code.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
