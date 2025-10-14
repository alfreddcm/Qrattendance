<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Access - Enter Code</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .card-header-custom i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .card-header-custom h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .card-header-custom p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .code-input {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 10px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .code-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
        
        .qr-scanner-btn {
            border: 2px dashed #667eea;
            color: #667eea;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .qr-scanner-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .info-text {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 20px;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header-custom">
                <i class="fas fa-qrcode"></i>
                <h2>Attendance Access</h2>
                <p>Enter your 6-digit code to continue</p>
            </div>
            
            <div class="login-body">
                @if(isset($error))
                    <div class="alert alert-danger alert-custom mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ $error }}
                    </div>
                @endif
                
                <form id="codeForm" action="{{ route('public.attendance') }}" method="GET">
                    <div class="mb-3">
                        <label for="code" class="form-label">
                            <i class="fas fa-key me-2"></i>Access Code
                        </label>
                        <input type="text" 
                               class="form-control code-input" 
                               id="code" 
                               name="code" 
                               placeholder="000000" 
                               maxlength="6" 
                               pattern="[0-9]{6}"
                               required
                               autofocus>
                        <div class="form-text text-center">
                            Enter the 6-digit code provided by your teacher
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Access Attendance
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <button type="button" class="btn qr-scanner-btn" onclick="alert('QR Scanner feature coming soon!')">
                    <i class="fas fa-camera me-2"></i>Scan QR Code Instead
                </button>
                
                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Don't have a code? Ask your teacher to generate one.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-format code input
        document.getElementById('code').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                // Validate code via AJAX first (optional)
                document.getElementById('codeForm').submit();
            }
        });
        
        // Prevent form submission if code is not 6 digits
        document.getElementById('codeForm').addEventListener('submit', function(e) {
            const code = document.getElementById('code').value;
            if (code.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit code');
            }
        });
    </script>
</body>
</html>
