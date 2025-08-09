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
            <h2>
                <span class="me-2">👨‍🎓</span>
                Student Management
            </h2>
            <p class="subtitle">Manage student records and QR codes</p>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <form method="GET" action="{{ route('teacher.students') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="search" placeholder="Search students..." class="form-control" value="{{ request('search') }}" style="width: 250px;">
            <select name="semester_id" class="form-select" style="width: 200px;">
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
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="qrActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                    @endif

                </ul>
            </div>
            
            <div class="btn-group">
                <button class="btn btn-outline-success dropdown-toggle" type="button" id="idActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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

        <a href="{{ route('teacher.students.export') }}" class="btn btn-outline-success me-2">
            <i class="fas fa-download me-1"></i>Export
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-2 mb-3" role="alert" style="max-width: 900px;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-2 mb-3" role="alert" style="max-width: 900px;">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif(session('info'))
    <div class="alert alert-info alert-dismissible fade show mt-2 mb-3" role="alert" style="max-width: 900px;">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <span class="me-2">👨‍🎓</span>
                Student Records
            </h5>
            <span class="badge bg-light text-primary">{{ count($students) }} Students</span>
        </div>
    </div>
    <div class="card-body p-3">
        @if(count($students) > 0)
            <div class="row g-3" style="max-height: 600px; overflow-y: auto;">
                @php $studentCount = 1; @endphp
                @foreach($students as $student)
                <div class="col-md-6 col-lg-4">
                <div class="student-card h-100 shadow-sm">
                    <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="student-id">{{ str_pad($studentCount, 4, '0', STR_PAD_LEFT) }}</span>
                       
                    </div>
                    </div>
                    <div class="card-body-custom">
                    <div class="row">
                        <!-- Student Photo Section -->
                        <div class="col-4">
                        <div class="student-image-section">
                            @if($student->picture)
                            <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Student Picture" 
                                 class="student-photo-card" 
                                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;">
                            @else
                            <div class="student-photo-placeholder-card">
                                <i class="fas fa-user"></i>
                            </div>
                            @endif
                        </div>
                        </div>
                        <!-- Student Details Section -->
                        <div class="col-8">
                        <div class="student-info-section">
                            <h6 class="student-name-card mb-1">{{ $student->name }}</h6>
                            <p class="student-id-text mb-2">ID: {{ $student->id_no }}</p>
                            
                            <div class="student-details-grid">
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
                    </div>
                    
                    <!-- Other Info Section with QR Code -->
                    <div class="other-info-section mt-3">
                        <div class="row">
                        <!-- Contact/Address Info -->
                        <div class="col-9">
                            <div class="info-grid">
                            <div class="info-item">
                                <small class="text-muted">Address:</small>
                                <div class="text-truncate" title="{{ $student->address }}">{{ $student->address }}</div>
                            </div>
                            <div class="info-item">
                                <small class="text-muted">Contact:</small>
                                <div>{{ $student->cp_no }}</div>
                            </div>
                            @if($student->contact_person_name)
                            <div class="info-item">
                                <small class="text-muted">Emergency Contact:</small>
                                <div>{{ $student->contact_person_name }}</div>
                            </div>
                            @endif
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
                                 class="qr-code-display clickable-qr"
                                 data-bs-toggle="modal"
                                 data-bs-target="#qrModal{{ $student->id }}"
                                 style="width: 60px; height: 60px; border: 1px solid #dee2e6; border-radius: 8px; cursor: pointer;"
                                 title="Click to view larger QR code">
                            @else
                            <form method="POST" action="{{ route('teacher.students.generateQr', $student->id) }}" class="generate-qr-form d-inline" onsubmit="return confirmGenerate()">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm qr-generate-btn" title="Generate QR Code">
                                <i class="fas fa-qrcode"></i><br>
                                <small>Generate<br>QR</small>
                                </button>
                            </form>
                            @endif
                        </div>
                        </div>
                    </div>
                    </div>
                    
                    <!-- Action Buttons Section -->
                    <div class="card-footer-custom">
                    <div class="d-flex justify-content-end align-items-center">
                        <!-- Action Buttons -->
                        <div class="action-buttons d-flex gap-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#infoModal{{ $student->id }}" title="View Details">
                            <i class="fas fa-eye me-1"></i>View
                        </button>
                        <a href="{{ route('teacher.students.edit', $student->id) }}" class="btn btn-sm btn-warning" title="Edit Student">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                           
                        <div class="btn-group">
                            <a href="{{ route('student.id.print', $student->id) }}" class="btn btn-sm btn-outline-info" title="Print Student ID (Ctrl+P)" target="_blank">
                            <i class="fas fa-print me-1"></i>Print
                            </a>
                        </div>
                        <form action="{{ route('teacher.students.destroy', $student->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')" title="Delete Student">
                            <i class="fas fa-trash me-1"></i>Remove
                            </button>
                        </form>
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
                                        <div class="info-item">
                                            <strong>Relationship:</strong> {{ $student->contact_person_relationship ?? 'N/A' }}
                                        </div>
                                        <div class="info-item">
                                            <strong>Contact:</strong> {{ $student->contact_person_contact ?? 'N/A' }}
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
                                            $qrPngExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png');
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

<!-- QR Modals for each student -->
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

.student-card {
    background: white;
    border-radius: 15px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.student-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.card-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: 1px solid rgba(255,255,255,0.2);
    padding: 1rem;
    color: white;
}

.card-body-custom {
    padding: 1.5rem;
    background: white;
    position: relative;
}

.card-footer-custom {
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    padding: 1rem;
}

.student-id {
    font-weight: 600;
    font-size: 1.1rem;
    color: white;
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    border: 1px solid rgba(255,255,255,0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.student-image-section {
    height: 100%;
}

.student-photo-card {
    border: 2px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.student-photo-placeholder-card {
    width: 100%;
    height: 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.student-photo-placeholder-card i {
    font-size: 3rem;
    opacity: 0.5;
}

.student-info-section {
    padding-left: 1rem;
}

.student-name-card {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
    line-height: 1.2;
}

.student-id-text {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.student-details-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.detail-label {
    font-weight: 500;
    color: #495057;
    min-width: 60px;
}

.other-info-section {
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item {
    font-size: 0.85rem;
}

.info-item small {
    display: block;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.qr-generate-btn {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    font-size: 0.7rem;
    line-height: 1.2;
}

.action-buttons .btn {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
}

/* Modal Improvements */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px 12px 0 0;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #f0f0f0;
    border-radius: 0 0 12px 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .student-card {
        margin-bottom: 1rem;
    }
    
    .card-body-custom {
        padding: 1rem;
    }
    
    .student-info-section {
        padding-left: 0.5rem;
    }
    
    .action-buttons {
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .action-buttons .btn {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Loading States */
.generate-qr-form {
    display: inline-block;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
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
