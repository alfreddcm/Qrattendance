<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Access - Enter Code</title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .scanning-indicator {
            display: none;
            text-align: center;
            color: #007bff;
            font-size: 14px;
            margin-top: 10px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .scanning-indicator.active {
            display: block;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .submit-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
            box-shadow: 0 5px 20px rgba(0, 123, 255, 0.4);
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
            border: 2px dashed #007bff;
            color: #007bff;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }

        .qr-scanner-btn:hover {
            background: #007bff;
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

                <form id="codeForm" action="{{ route('public.attendance.index') }}" method="GET">
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
                        <div class="scanning-indicator" id="scanningIndicator">
                            <i class="fas fa-barcode me-2"></i>Ready for barcode scanner...
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Access Attendance
                    </button>
                </form>


            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const codeInput = document.getElementById('code');
        const codeForm = document.getElementById('codeForm');
        const scanningIndicator = document.getElementById('scanningIndicator');
        let lastInputTime = Date.now();
        let scanBuffer = '';

        // Ensure input is always focused when page loads
        window.addEventListener('load', function() {
            codeInput.focus();
            scanningIndicator.classList.add('active');
        });

        // Re-focus input if user clicks anywhere on the page
        document.addEventListener('click', function(e) {
            if (e.target !== codeInput) {
                codeInput.focus();
            }
        });

        // Prevent input from losing focus
        codeInput.addEventListener('blur', function() {
            setTimeout(() => {
                codeInput.focus();
            }, 100);
        });

        // Handle input from keyboard or barcode scanner
        codeInput.addEventListener('input', function(e) {
            const currentTime = Date.now();
            const timeDiff = currentTime - lastInputTime;
            lastInputTime = currentTime;

            // Remove any non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');

            // Detect barcode scanner (rapid input)
            if (timeDiff < 50) {
                scanningIndicator.innerHTML = '<i class="fas fa-barcode me-2"></i>Scanning...';
            }

            // Auto-submit when 6 digits are entered
            if (this.value.length === 6) {
                scanningIndicator.innerHTML = '<i class="fas fa-check-circle me-2"></i>Code detected! Redirecting...';
                scanningIndicator.style.color = '#28a745';

                // Small delay to show feedback, then submit
                setTimeout(() => {
                    codeForm.submit();
                }, 500);
            } else if (this.value.length > 6) {
                // Trim to 6 digits if somehow more are entered
                this.value = this.value.substring(0, 6);
            }
        });

        // Handle manual form submission
        codeForm.addEventListener('submit', function(e) {
            const code = codeInput.value;
            if (code.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit code');
                codeInput.focus();
                return false;
            }
        });

        // Handle keyboard enter key
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.length === 6) {
                    codeForm.submit();
                } else {
                    alert('Please enter a complete 6-digit code');
                }
            }
        });

        // Visual feedback for focused state
        setInterval(() => {
            if (document.activeElement !== codeInput) {
                codeInput.focus();
            }
        }, 1000);

        console.log('Barcode scanner ready. Input field is auto-focused and will auto-submit on 6-digit scan.');
    </script>
</body>
</html>
