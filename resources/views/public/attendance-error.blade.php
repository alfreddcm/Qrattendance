<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Session Error</title>
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        
        .error-icon {
            color: #dc3545;
            font-size: 4rem;
            margin-bottom: 20px;
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
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <h2 class="text-danger mb-3">Session Error</h2>
        <p class="text-muted mb-4">{{ $error }}</p>
        
        @if(isset($session_date) && isset($current_date))
            <div class="alert alert-warning">
                <strong><i class="fas fa-calendar-times me-2"></i>Session Details:</strong><br>
                Session Date: {{ $session_date }}<br>
                Current Date: {{ $current_date }}<br>
                <small class="text-muted">Sessions expire daily at 6:00 PM for security.</small>
            </div>
        @endif
        
        @if(isset($current_time))
            <div class="alert alert-info">
                <strong><i class="fas fa-clock me-2"></i>Access Hours:</strong><br>
                Available: 5:00 AM - 6:00 PM<br>
                Current Time: {{ $current_time }}<br>
                <small class="text-muted">Please try again during allowed hours.</small>
            </div>
        @endif
        
        <div class="mt-4">
            <p class="small text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Please contact your teacher for a new attendance link.
            </p>
        </div>
        
        <div class="mt-4">
            <button onclick="window.close()" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>
                Close Window
            </button>
            <button onclick="location.reload()" class="btn btn-primary ms-2">
                <i class="fas fa-refresh me-2"></i>
                Refresh Page
            </button>
        </div>
    </div>
</body>
</html>
