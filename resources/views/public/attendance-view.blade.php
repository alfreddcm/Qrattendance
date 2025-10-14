<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - {{ $attendanceCode->teacher->name ?? 'Attendance' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .attendance-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .student-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px;
            transition: background-color 0.2s;
        }
        
        .student-item:hover {
            background-color: #f8f9fa;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-qr {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .student-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .student-id {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .section-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .code-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="attendance-card">
            <!-- Header -->
            <div class="header-gradient">
                <h2 class="mb-2">
                    <i class="fas fa-users me-2"></i>
                    Student Attendance
                </h2>
                <p class="mb-0">
                    <strong>Teacher:</strong> {{ $attendanceCode->teacher->name ?? 'N/A' }}
                </p>
                @if($attendanceCode->section)
                    <p class="mb-0">
                        <strong>Section:</strong> {{ $attendanceCode->section->name }}
                    </p>
                @endif
            </div>
            
            <!-- Code Info -->
            <div class="p-4">
                <div class="code-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-key text-primary me-2"></i>
                            <strong>Access Code:</strong> 
                            <span class="font-monospace fs-5 text-primary">{{ $code }}</span>
                        </div>
                        <div>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span class="text-success">Valid</span>
                        </div>
                    </div>
                </div>
                
                <!-- Students Count -->
                <div class="mb-3">
                    <h5>
                        <i class="fas fa-list me-2"></i>
                        Student List
                        <span class="badge bg-primary ms-2">{{ $students->count() }} Students</span>
                    </h5>
                </div>
                
                <!-- Students List -->
                @if($students->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h5 class="mt-3">No Students Found</h5>
                        <p class="text-muted">
                            There are no students registered for this section.
                        </p>
                    </div>
                @else
                    <div class="students-container">
                        @foreach($students as $index => $student)
                            <div class="student-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="fw-bold text-muted" style="font-size: 1.2rem;">
                                            {{ $index + 1 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        @if($student->qrcode)
                                            <img src="{{ asset('storage/' . $student->qrcode) }}" 
                                                 alt="QR" 
                                                 class="student-qr"
                                                 onerror="this.src='{{ asset('img/default-qr.png') }}'">
                                        @else
                                            <div class="student-qr d-flex align-items-center justify-content-center bg-light">
                                                <i class="fas fa-qrcode text-muted"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col">
                                        <div class="student-name">{{ $student->name }}</div>
                                        <div class="student-id">
                                            <i class="fas fa-id-card me-1"></i>
                                            {{ $student->student_id ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        @if($student->section)
                                            <span class="section-badge">
                                                {{ $student->section->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Footer Info -->
                <div class="mt-4 text-center text-muted">
                    <small>
                        <i class="fas fa-clock me-1"></i>
                        Accessed on {{ \Carbon\Carbon::now()->format('F j, Y g:i A') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-refresh to check if code is still valid -->
    <script>
        // Check every 30 seconds if code is still valid
        setInterval(function() {
            fetch('{{ route("public.attendance.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    code: '{{ $code }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('This access code is no longer valid.');
                    window.location.href = '{{ route("public.attendance") }}';
                }
            })
            .catch(error => {
                console.error('Error validating code:', error);
            });
        }, 30000); // 30 seconds
    </script>
</body>
</html>
