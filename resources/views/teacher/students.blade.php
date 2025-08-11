@extends('teacher/sidebar')
@section('title', 'Students')
@section('content')

@php
    use Illuminate\Support\Facades\Storage;
    
    $missingQr = false;
    foreach($students as $student) {
        
        if (!$student->qr_code || !Storage::disk('public')->exists($student->qr_code)) {
            
            $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
            $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
            if (!$qrSvgExists) {
                $missingQr = true;
                break;
            }
        }
    }
    $semesters = \App\Models\Semester::all();
    $selectedSemester = request('semester_id');
@endphp

<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">👨‍🎓</span>
                Student Management
            </h4>
            <p class="subtitle fs-6 mb-0">Manage student records and QR codes</p>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <form method="GET" action="{{ route('teacher.students') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="search" placeholder="Search students..." class="form-control form-control-sm" value="{{ request('search') }}" style="width: 200px;">
            <select name="semester_id" class="form-select form-select-sm" style="width: 180px;">
                <option value="">All Semesters</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $selectedSemester == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline-primary">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-plus me-1"></i>Add Student
            </button>
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="qrActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-qrcode me-1"></i>QR Actions
                </button>
                <ul class="dropdown-menu" aria-labelledby="qrActionsDropdown">
                    @if($missingQr)
                    <li>
                        <form method="POST" action="{{ route('teacher.students.generateQrs') }}" class="generate-qr-form" id="generateAllQrForm" style="margin: 0;">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-magic me-2"></i>Generate All QR Codes
                            </button>
                        </form>
                    </li>
                    @else
                        <li>
                        <span class="dropdown-item text-success d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            Every student has a QR code
                        </span>
                        </li>
                    @endif

                </ul>
            </div>
            
            <div class="btn-group">
                <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="idActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-id-card me-1"></i>Student IDs
                </button>
                <ul class="dropdown-menu" aria-labelledby="idActionsDropdown">
                    @if(auth()->user()->role === 'admin')
                    <li>
                        <a href="{{ route('student.ids.print.all') }}" class="dropdown-item" target="_blank">
                            <i class="fas fa-print me-2"></i>Print All Student IDs (Ctrl+P)
                        </a>
                    </li>
                    @elseif(auth()->user()->role === 'teacher')
                    <li>
                        <a href="{{ route('student.ids.print.my.students') }}" class="dropdown-item" target="_blank">
                            <i class="fas fa-print me-2"></i>Print My Students' IDs (Ctrl+P)
                        </a>
                    </li>
                    @endif
                </ul>
            </div>

        <a href="{{ route('teacher.students.export') }}" class="btn btn-outline-success btn-sm me-2">
            <i class="fas fa-download me-1"></i>Export
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2 mb-2" role="alert" style="max-width: 900px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-2 mb-2" role="alert" style="max-width: 900px;">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif(session('info'))
    <div class="alert alert-info alert-dismissible fade show mt-2 mb-2" role="alert" style="max-width: 900px;">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm" style="height: calc(100vh - 220px); display: flex; flex-direction: column;">
    <div class="card-header bg-primary text-white p-2">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fs-6">
                <span class="me-1">👨‍🎓</span>
                Student Records
            </h6>
            <span class="badge bg-light text-primary fs-6">{{ count($students) }} Students</span>
        </div>
    </div>
    <div class="card-body p-2" style="flex: 1; overflow: hidden; display: flex; flex-direction: column;">
        @if(count($students) > 0)
            <div class="row g-2" style="flex: 1; overflow-y: auto; margin: 0; padding-right: 8px;">
                @php $studentCount = 1; @endphp
                @foreach($students as $student)
                <div class="col-md-6 col-lg-4">
                <div class="student-card shadow-sm">
                    <div class="card-header-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="student-id">{{ str_pad($studentCount, 4, '0', STR_PAD_LEFT) }}</span>
                            <div class="header-actions">
                                <button class="btn btn-outline-light btn-xs me-1" data-bs-toggle="modal" data-bs-target="#infoModal{{ $student->id }}" title="View">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-outline-light btn-xs dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('teacher.students.edit', $student->id) }}" class="dropdown-item">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('student.id.print', $student->id) }}" class="dropdown-item" target="_blank">
                                                <i class="fas fa-print me-2"></i>Print
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('teacher.students.destroy', $student->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash me-2"></i>Remove
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body-custom">
                        <div class="row h-100">
                             <div class="col-3">
                                <div class="student-image-section">
                                    @if($student->picture)
                                    <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Student Picture" 
                                         class="student-photo">
                                    @else
                                    <div class="student-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                             <div class="col-6">
                                <div class="student-info-section">
                                    <h6 class="student-name">{{ $student->name }}</h6>
                                    <p class="student-id-text">ID: {{ $student->id_no }}</p>
                                    
                                    <div class="student-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Gender:</span>
                                            @if($student->gender == 'M')
                                            <span class="badge bg-info">Male</span>
                                            @elseif($student->gender == 'F')
                                            <span class="badge bg-pink">Female</span>
                                            @else
                                            <span class="badge bg-secondary">{{ $student->gender }}</span>
                                            @endif
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Age:</span>
                                            <span class="badge bg-secondary">{{ $student->age }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code Section -->
                            <div class="col-3 d-flex align-items-center justify-content-center">
                                @php
                                $hasQrCode = false;
                                $qrImagePath = '';

                                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                                    $hasQrCode = true;
                                    $qrImagePath = $student->qr_code;
                                } else {
                                    $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                    $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                                    $qrPngExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png');
                                    if ($qrSvgExists) {
                                    $hasQrCode = true;
                                    $qrImagePath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';
                                    } elseif ($qrPngExists) {
                                    $hasQrCode = true;
                                    $qrImagePath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png';
                                    }
                                }
                                @endphp
                                @if($hasQrCode)
                                <img src="{{ asset('storage/' . $qrImagePath) }}" alt="QR Code"
                                     class="qr-code-display"
                                     data-bs-toggle="modal"
                                     data-bs-target="#qrModal{{ $student->id }}"
                                     title="Click to view larger QR code">
                                @else
                                <button type="button" class="btn btn-outline-primary qr-generate-btn" data-bs-toggle="modal" data-bs-target="#generateQrModal{{ $student->id }}" title="Generate QR">
                                    <i class="fas fa-qrcode"></i><br>
                                    <small>Generate<br>QR</small>
                                </button>
                                @endif
                            </div>
                       
                            <div class="info-item">
                                <small>Address:</small>
                                <div class="text-truncate">{{ $student->address }}</div>
                            
                                <small>Contact:</small>
                                <div>{{ $student->cp_no }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                @php $studentCount++; @endphp
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                <i class="fas fa-users text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                </div>
                <h4 class="text-muted mb-3">No students found</h4>
                <p class="text-muted mb-4">You haven't added any students yet. Get started by adding your first student.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-plus me-1"></i>Add Student
                </button>
            </div>
        @endif
    </div>
</div>

@foreach($students as $student)
<div class="modal fade" id="infoModal{{ $student->id }}" tabindex="-1" aria-labelledby="infoModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="infoModalLabel{{ $student->id }}">
                    <i class="fas fa-user me-2"></i>Student Information
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        @if($student->picture)
                            <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Student Picture" 
                                 class="img-fluid rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;">
                        @else
                            <div class="bg-light rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                 style="width: 150px; height: 150px; border: 3px solid #dee2e6;">
                                <i class="fas fa-user text-muted" style="font-size: 48px;"></i>
                            </div>
                        @endif
                        
                        <div class="text-center">
                            <h5 class="mb-1">{{ $student->name }}</h5>
                            <span class="badge bg-secondary">{{ $student->id_no }}</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-info-circle me-2"></i>Personal Info
                                        </h6>
                                        <div class="info-item">
                                            <strong>Gender:</strong> 
                                            @if($student->gender == 'M')
                                                <span class="badge bg-info">Male</span>
                                            @elseif($student->gender == 'F')
                                                <span class="badge bg-pink">Female</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $student->gender }}</span>
                                            @endif
                                        </div>
                                        <div class="info-item">
                                            <strong>Age:</strong> 
                                            <span class="badge bg-secondary">{{ $student->age }}</span>
                                        </div>
                                        <div class="info-item">
                                            <strong>Semester:</strong> 
                                            <span class="badge bg-primary">{{ optional($student->semester)->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-phone me-2"></i>Contact Info
                                        </h6>
                                        <div class="info-item">
                                            <strong>Phone:</strong> {{ $student->cp_no }}
                                        </div>
                                        <div class="info-item">
                                            <strong>Address:</strong> {{ $student->address }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-users me-2"></i>Emergency Contact
                                        </h6>
                                        <div class="info-item">
                                            <strong>Name:</strong> {{ $student->contact_person_name ?? 'N/A' }}
                                        </div>

                                        <div class="row">
                                            <div class="col">
                                                  <div class="info-item">
                                            <strong>Relationship:</strong> {{ $student->contact_person_relationship ?? 'N/A' }}
                                        </div>
                                            </div>
                                            <div class="col">
                                                 <div class="info-item">
                                            <strong>Contact:</strong> {{ $student->contact_person_contact ?? 'N/A' }}
                                        </div>
                                            </div>
                                        </div>
                                      
                                       
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body p-3 text-center">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-qrcode me-2"></i>QR Code
                                        </h6>
                                        @php
                                            $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                            $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                                         @endphp
                                        @if($qrSvgExists)
                                            <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg') }}" alt="QR Code" 
                                                 style="width: 120px; height: 120px; border: 2px solid #dee2e6; border-radius: 10px;">
                                        @elseif($qrPngExists)
                                            <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png') }}" alt="QR Code" 
                                                 style="width: 120px; height: 120px; border: 2px solid #dee2e6; border-radius: 10px;">
                                        @else
                                            <div class="text-muted">
                                                <i class="fas fa-qrcode fa-3x mb-2"></i>
                                                <div>No QR Code available</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('student.id.print', $student->id) }}" class="btn btn-sm btn-outline-info" title="Print Student ID (Ctrl+P)" target="_blank">
                    <i class="fas fa-print me-1"></i>Print
                </a>
                <a href="{{ route('teacher.students.edit', $student->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Student
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

@foreach($students as $student)
<div class="modal fade" id="qrModal{{ $student->id }}" tabindex="-1" aria-labelledby="qrModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrModalLabel{{ $student->id }}">
                    <i class="fas fa-qrcode me-2"></i>QR Code - {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                @php
                    
                    $modalQrImagePath = '';
                    $modalHasQrCode = false;
                    
                    if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                        $modalHasQrCode = true;
                        $modalQrImagePath = $student->qr_code;
                    } else {
                        
                        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                        $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                        $qrPngExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png');
                        
                        if ($qrSvgExists) {
                            $modalHasQrCode = true;
                            $modalQrImagePath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';
                        } elseif ($qrPngExists) {
                            $modalHasQrCode = true;
                            $modalQrImagePath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png';
                        }
                    }
                @endphp
                @if($modalHasQrCode)
                    <img src="{{ asset('storage/' . $modalQrImagePath) }}" alt="QR Code" 
                         style="width: 250px; height: 250px; border: 2px solid #dee2e6; border-radius: 10px;">
                @else
                    <div class="text-muted">
                        <i class="fas fa-qrcode fa-5x mb-3"></i>
                        <div>No QR Code available</div>
                    </div>
                @endif
                <div class="mt-3">
                    <small class="text-muted">Student ID: {{ $student->id_no }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

@foreach($students as $student)
<!-- Generate QR Modal -->
<div class="modal fade" id="generateQrModal{{ $student->id }}" tabindex="-1" aria-labelledby="generateQrModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="generateQrModalLabel{{ $student->id }}">
                    <i class="fas fa-qrcode me-2"></i>Generate QR Code - {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-qrcode text-primary" style="font-size: 3rem;"></i>
                </div>
                <p class="mb-3">Generate a QR code for <strong>{{ $student->name }}</strong> (ID: {{ $student->id_no }})?</p>
                <p class="text-muted small">This will create a unique QR code that can be used for attendance tracking.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form method="POST" action="{{ route('teacher.students.generateQr', $student->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic me-2"></i>Generate QR Code
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

        <!-- Add Student Modal -->
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('teacher.students.add') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addStudentModalLabel">
                                <i class="fas fa-user-plus me-2"></i>Add New Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="id_no" name="id_no" required>
                                    <small class="form-text text-muted">Enter a unique student identification number</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="age" class="form-label">Age <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="age" name="age" min="1" max="100" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="semester_id" class="form-label">Semester</label>
                                    <select class="form-select" id="semester_id" name="semester_id">
                                        <option value="">Select Semester</option>
                                        @foreach(\App\Models\Semester::all() as $semester)
                                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter student's complete address"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="cp_no" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="cp_no" name="cp_no" placeholder="e.g., +63 912 345 6789">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_person_name" class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" id="contact_person_name" name="contact_person_name" placeholder="Parent/Guardian name">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_person_relationship" class="form-label">Relationship</label>
                                    <select class="form-select" id="contact_person_relationship" name="contact_person_relationship">
                                        <option value="">Select Relationship</option>
                                        <option value="Parent">Parent</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Sibling">Sibling</option>
                                        <option value="Relative">Relative</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_person_contact" class="form-label">Emergency Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_person_contact" name="contact_person_contact" placeholder="Emergency contact number">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="me-auto">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importStudentsModal" data-bs-dismiss="modal">
                                    <i class="fas fa-file-excel me-1"></i>Import Excel File
                                </button>
                            </div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Student
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="importStudentsModal" tabindex="-1" aria-labelledby="importStudentsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importStudentsModalLabel">Upload Students</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <p class="text-muted">Download the template first, fill it with student data, then upload the file.</p>
                            <a href="{{ route('teacher.students.downloadTemplate') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-download me-1"></i>Download Template
                            </a>
                        </div>
                        <hr>
                        <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="file">Choose CSV or Excel file:</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".csv, .xls, .xlsx" required>
                                <small class="form-text text-muted">Supported formats: .csv, .xls, .xlsx</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i>Upload Students
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div>
                    <strong>Generating QR Code(s), please wait...</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar styling */
    .row::-webkit-scrollbar {
        width: 8px;
    }
    
    .row::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .row::-webkit-scrollbar-thumb {
        background: #6366f1;
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    
    .row::-webkit-scrollbar-thumb:hover {
        background: #4f46e5;
    }
    
    /* Firefox scrollbar */
    .row {
        scrollbar-width: thin;
        scrollbar-color: #6366f1 #f1f1f1;
    }

    .student-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        background: white;
        transition: all 0.2s ease;
        height: 240px;
        display: flex;
        flex-direction: column;
    }
    
    .student-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        transform: translateY(-2px);
    }
    
    .card-header-custom {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 10px 15px;
        border-radius: 10px 10px 0 0;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .header-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .btn-xs {
        padding: 3px 6px;
        font-size: 0.7rem;
        border-radius: 4px;
    }
    
    .btn-outline-light {
        border-color: rgba(255,255,255,0.3);
        color: white;
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.5);
        color: white;
    }
    
    .dropdown-menu {
        font-size: 0.85rem;
        min-width: 140px;
    }
    
    .dropdown-item {
        padding: 6px 12px;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .card-body-custom {
        padding: 12px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .student-image-section {
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .student-photo {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #6366f1;
    }
    
    .student-photo-placeholder {
        width: 60px;
        height: 60px;
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 24px;
    }
    
    .student-info-section {
        padding-left: 8px;
        height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .student-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.2;
    }
    
    .student-id-text {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 6px;
    }
    
    .student-details {
        display: flex;
        gap: 6px;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .detail-label {
        font-size: 0.7rem;
        color: #6b7280;
    }
    
    .badge {
        font-size: 0.65rem;
        padding: 2px 6px;
    }
    
    .bg-pink {
        background-color: #ec4899 !important;
    }
    
    .qr-code-display {
        width: 50px;
        height: 50px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .qr-code-display:hover {
        transform: scale(1.05);
    }
    
    .qr-generate-btn {
        width: 50px;
        height: 50px;
        font-size: 0.6rem;
        padding: 4px;
        line-height: 1.1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .additional-info {
        margin-top: 12px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
        flex: 1;
    }
    
    .info-item {
        margin-bottom: 4px;
    }
    
    .info-item small {
        font-size: 0.7rem;
        color: #6b7280;
        font-weight: 500;
    }
    
    .info-item div {
        font-size: 0.75rem;
        color: #374151;
        line-height: 1.2;
    }
    
    @media (max-width: 768px) {
        .student-card {
            height: auto;
            min-height: 200px;
        }
        
        .student-photo, .student-photo-placeholder {
            width: 50px;
            height: 50px;
        }
        
        .qr-code-display, .qr-generate-btn {
            width: 40px;
            height: 40px;
        }
        
        .header-actions {
            gap: 2px;
        }
        
        .btn-xs {
            padding: 2px 4px;
            font-size: 0.65rem;
        }
    }
</style>

<script>
// JavaScript Functions
function confirmGenerate() {
    return confirm('Are you sure you want to generate a QR code for this student?');
}

// QR Generation Loading
document.querySelectorAll('.generate-qr-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var btn = form.querySelector('button[type="submit"]');
        var spinner = btn.querySelector('.spinner-border');
        btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btn.childNodes[1]) btn.childNodes[1].textContent = ' Generating...';
        // Show loading modal
        var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    });
});

// Form submission with loading state
document.getElementById('generateAllQrForm')?.addEventListener('submit', function() {
    const button = this.querySelector('button[type="submit"]');
    button.classList.add('loading');
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
});
</script>

@endsection
