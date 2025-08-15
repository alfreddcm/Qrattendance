@extends('admin.sidebar')
@section('title', 'Manage Students')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-user-graduate me-2"></i>
                Manage Students
            </h4>
            <p class="subtitle fs-6 mb-0">View and manage student records across all schools</p>
        </div>
        
    </div>
</div>

<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search   -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('admin.manage-students') }}" id="studentFilterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label small fw-semibold fs-6">Search Students</label>
                                <input type="text" name="search" id="search" placeholder="Name or ID..." class="form-control form-control-sm" value="{{ request('search') }}" oninput="debounceSubmit()">
                            </div>
                            <div class="col-md-2">
                                <label for="school_id" class="form-label small fw-semibold fs-6">School</label>
                                <select name="school_id" id="school_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Schools</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="teacher_id" class="form-label small fw-semibold fs-6">Teacher</label>
                                <select name="teacher_id" id="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Teachers</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="qr_status" class="form-label small fw-semibold">QR Status</label>
                                <select name="qr_status" id="qr_status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="with_qr" {{ request('qr_status') == 'with_qr' ? 'selected' : '' }}>With QR</option>
                                    <option value="without_qr" {{ request('qr_status') == 'without_qr' ? 'selected' : '' }}>Without QR</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    @if(request()->hasAny(['search', 'school_id', 'teacher_id', 'qr_status']))
                                        <a href="{{ route('admin.manage-students') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--   Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card shadow-sm h-100 border-0 border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <h6 class="card-title text-primary mb-1 fw-semibold">Total Students</h6>
                            <h2 class="mb-0 text-primary fw-bold">{{ $students->total() }}</h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card shadow-sm h-100 border-0 border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <h6 class="card-title text-success mb-1 fw-semibold">Schools</h6>
                            <h2 class="mb-0 text-success fw-bold">{{ $schools->count() }}</h2>
                            <small class="text-muted">With students</small>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-school fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card shadow-sm h-100 border-0 border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <h6 class="card-title text-info mb-1 fw-semibold"> Teachers</h6>
                            <h2 class="mb-0 text-info fw-bold">{{ $teachers->count() }}</h2>
                            <small class="text-muted">Managing students</small>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
              
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center sticky-top custom-sticky" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 me-3">
                            <i class="fas fa-list me-2"></i>
                            Students List
                        </h5>
                        <span class="badge bg-light text-dark">{{ $students->total() }} total</span>
                    </div>
                    
                    <div class="d-flex gap-2">
                         <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bars me-1"></i>Actions
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="quickActionsDropdown">
                                <li><h6 class="dropdown-header">All Students Actions</h6></li>
                                <li>
                                    <form method="POST" action="{{ route('admin.students.generateQrs') }}" class="generate-qr-form m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-magic me-2 text-primary"></i>Generate All QR Codes
                                        </button>
                                    </form>
                                </li>
                              
                                <li>
                                    <a class="dropdown-item" href="{{ route('student.ids.print.all') }}" target="_blank">
                                        <i class="fas fa-print me-2 text-success"></i>Print All Student IDs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.students.export') }}">
                                        <i class="fas fa-file-excel me-2 text-info"></i>Export Student Data
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <a class="btn btn-success text-dark" href="#" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-user-plus me-2 text-dark"></i>Add Student
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="background-color: #5b73e8; color: white;">
                                    <tr>
                                        <th style="width: 40px; border: none;">

                                        </th>
                                        <th style="width: 80px; text-align: center; border: none;">Photo</th>
                                        <th style="border: none;">Student ID</th>
                                        <th style="border: none;">STUDENT INFO</th>
                                        <th style="border: none;">Contact Person</th>
                                        <th style="border: none;">SCHOOL</th>
                                        <th style="border: none;">QR STATUS</th>
                                        <th style="border: none;">Profile status</th>
                                        <th style="width: 120px; text-align: center; border: none;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                    <tr style="border-bottom: 1px solid #e9ecef;">
                                        <td style="vertical-align: middle;">
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                             
                                            @if($student->picture)
                                                <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" 
                                                     alt="{{ $student->name }}" 
                                                     class="rounded" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto" 
                                                     style="width: 50px; height: 50px; border: 2px solid #dee2e6;">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <strong style="color: #2c3e50;">{{ $student->id_no ?? 'N/A' }}</strong>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div>
                                                <strong style="color: #2c3e50;">{{ $student->name }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                     {{ $student->user->section_name ?? 'N/A' }}
                                                    @if($student->age)
                                                        <br>Age: {{ $student->age }}
                                                    @endif
                                                    @if($student->id_no)
                                                        <br>Number: {{ $student->id_no }}
                                                    @endif
                                                </small>
                                            </div>
                                        </td>
                                        <td style="vertical-align: middle;">
                                            @if($student->cp_no)
                                                <div>
                                                    <strong>{{ $student->contact_person_name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $student->contact_person_contact }}</small>
                                                    @if($student->contact_person_relation)
                                                        <br>
                                                        <small class="text-muted">{{ $student->contact_person_relation }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">No contact</span>
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                                <strong style="color: #2c3e50;">{{ $student->school->name ?? 'N/A' }}</strong>
                                            </div>
                                        </td>
                                        <td style="vertical-align: middle; text-align: center;">
                                            
                                        
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
                                    style="width: 5em; height: 5em; object-fit: contain;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#qrModal{{ $student->id }}"
                                    title="Click to view larger QR code">
                                @else
                                <form action="{{ route('admin.students.generateQr', $student->id) }}" method="POST" class="generate-qr-form d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-qrcode me-1"></i>Generate
                                    </button>
                                </form>
                                @endif


 
                                        </td>
                                        <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                            @php
                                                $missingFields = [];
                                                if(!$student->picture) $missingFields[] = 'Photo';
                                                if(!$student->qr_code) $missingFields[] = 'QR';
                                                if(!$student->cp_no) $missingFields[] = 'Contact';
                                                if(!$student->address) $missingFields[] = 'Address';
                                                if(!$student->gender) $missingFields[] = 'Gender';
                                                if(!$student->age) $missingFields[] = 'Age';
                                                if(!$student->id_no) $missingFields[] = 'Student ID';
                                                if(!$student->name) $missingFields[] = 'Name';
                                                if(!$student->contact_person_name) $missingFields[] = 'Emergency Contact';
                                                if(!$student->contact_person_relationship) $missingFields[] = 'Relationship';
                                                if(!$student->contact_person_contact) $missingFields[] = 'Emergency Phone';
                                                if(!$student->school_id) $missingFields[] = 'School';
                                                if(!$student->user_id) $missingFields[] = 'Teacher';
                                            @endphp
                                            
                                            @if(count($missingFields) == 0)
                                                <span class="badge bg-success">Complete</span>
                                            @else
                                                <span class="badge bg-warning" title="Missing: {{ implode(', ', $missingFields) }}">
                                                    {{ implode(', ', $missingFields) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle; text-align: center;">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#infoModal{{ $student->id }}">
                                                            <i class="fas fa-eye text-primary me-2"></i>View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal{{ $student->id }}">
                                                            <i class="fas fa-edit text-warning me-2"></i>Edit Student
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('student.id.print', $student->id) }}" target="_blank">
                                                            <i class="fas fa-print text-info me-2"></i>Print ID
                                                        </a>
                                                    </li>
                                                    @if(!$student->qr_code)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('admin.students.generateQr', $student->id) }}" method="POST" class="generate-qr-form m-0">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item" style="background: none; border: none; width: 100%; text-align: left;">
                                                                    <i class="fas fa-qrcode text-success me-2"></i>Generate QR Code
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="deleteStudent({{ $student->id }}, '{{ $student->name }}')">
                                                            <i class="fas fa-trash text-danger me-2"></i>Delete Student
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="card-footer">
                            {{ $students->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No students found</h5>
                            <p class="text-muted">Students will appear here once teachers add them to their classes.</p>
                        </div>
                    @endif
                
                </div>
            </div>
        </div>
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
                            <img src="{{ asset('storage/student_pictures/' . $student->picture)  }}" alt="Student Picture" 
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
                        <div class="text-center mt-2">
                            
                         <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-qrcode me-2"></i>QR Code
                                        </h6>
                                        @if($student->qr_code)
                                            <img src="{{ asset('storage/' . $student->qr_code) }}" alt="QR Code" 
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
                                            <strong>School:</strong> 
                                            <span class="badge bg-primary">{{ optional($student->school)->name ?? 'N/A' }}</span>
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
                             
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('student.id.print', $student->id) }}" class="btn btn-outline-info" target="_blank">
                    <i class="fas fa-print me-1"></i>Print ID
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $student->id }}">
                    <i class="fas fa-edit me-2"></i>Edit Student
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editModal{{ $student->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.update', $student->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel{{ $student->id }}">
                        <i class="fas fa-user-edit me-2"></i>Edit Student - {{ $student->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_id_no_{{ $student->id }}" class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_id_no_{{ $student->id }}" name="id_no" value="{{ $student->id_no }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_name_{{ $student->id }}" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name_{{ $student->id }}" name="name" value="{{ $student->name }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_gender_{{ $student->id }}" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_gender_{{ $student->id }}" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M" {{ $student->gender == 'M' ? 'selected' : '' }}>Male</option>
                                <option value="F" {{ $student->gender == 'F' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_age_{{ $student->id }}" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_age_{{ $student->id }}" name="age" value="{{ $student->age }}" min="1" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_school_{{ $student->id }}" class="form-label">School</label>
                            <select class="form-select" id="edit_school_{{ $student->id }}" name="school_id">
                                <option value="">Select School</option>
                                @foreach($schools as $school)                                <option value="{{ $school->id }}" {{ $student->school_id == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="edit_address_{{ $student->id }}" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address_{{ $student->id }}" name="address" rows="2">{{ $student->address }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_cp_no_{{ $student->id }}" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="edit_cp_no_{{ $student->id }}" name="cp_no" value="{{ $student->cp_no }}">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_contact_person_name_{{ $student->id }}" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="edit_contact_person_name_{{ $student->id }}" name="contact_person_name" value="{{ $student->contact_person_name }}">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_contact_person_relationship_{{ $student->id }}" class="form-label">Relationship</label>
                            <select class="form-select" id="edit_contact_person_relationship_{{ $student->id }}" name="contact_person_relationship">
                                <option value="">Select Relationship</option>
                                <option value="Parent" {{ $student->contact_person_relationship == 'Parent' ? 'selected' : '' }}>Parent</option>
                                <option value="Guardian" {{ $student->contact_person_relationship == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                <option value="Sibling" {{ $student->contact_person_relationship == 'Sibling' ? 'selected' : '' }}>Sibling</option>
                                <option value="Relative" {{ $student->contact_person_relationship == 'Relative' ? 'selected' : '' }}>Relative</option>
                                <option value="Other" {{ $student->contact_person_relationship == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_contact_person_contact_{{ $student->id }}" class="form-label">Emergency Contact Number</label>
                            <input type="tel" class="form-control" id="edit_contact_person_contact_{{ $student->id }}" name="contact_person_contact" value="{{ $student->contact_person_contact }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Student
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf
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
                            <label for="school_id" class="form-label">School</label>
                            <select class="form-select" id="school_id" name="school_id">
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="user_id">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="cp_no" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="cp_no" name="cp_no">
                        </div>
                        <div class="col-md-6">
                            <label for="contact_person_name" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="contact_person_name" name="contact_person_name">
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
                            <input type="tel" class="form-control" id="contact_person_contact" name="contact_person_contact">
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="importStudentsModalLabel">
                            <i class="fas fa-file-upload me-2"></i>Import Students from Excel
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Instructions Section -->
                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>How to Import Students</h6>
                            <ol class="mb-2">
                                <li>Download the template file below</li>
                                <li>Fill in the student data following the format</li>
                                <li>Save and upload your completed file</li>
                                <li>Review the preview before confirming the import</li>
                            </ol>
                            <hr>
                            <p class="mb-0"><strong>Supported formats:</strong> .csv, .xls, .xlsx (max 5MB)</p>
                        </div>

                        <!-- Download Template Section -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-download me-2"></i>Step 1: Download Template
                            </h6>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.students.downloadTemplate') }}" class="btn btn-outline-success">
                                    <i class="fas fa-file-excel me-1"></i>Download Excel Template
                                </a>
                                <a href="{{ route('admin.students.downloadSampleData') }}" class="btn btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>View Sample Data
                                </a>
                            </div>
                            <small class="text-muted d-block mt-2">
                                The template includes all required fields with proper formatting examples.
                            </small>
                        </div>

                        <hr>

                        <!-- Upload Section -->
                        <div class="mb-3">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-upload me-2"></i>Step 2: Upload Your File
                            </h6>
                            <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col">
                                        <label for="import_file" class="form-label">Choose Excel/CSV file:</label>
                                        <input type="file" name="file" id="import_file" class="form-control" 
                                               accept=".csv, .xls, .xlsx" required 
                                               onchange="handleFileSelect(this)">
                                        <div class="form-text">
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            Accepted formats: CSV, Excel (.xls, .xlsx) | Max size: 5MB
                                        </div>
                                    </div>
                                    
                                </div>

                                <!-- File Info Display -->
                                <div id="fileInfo" class="mt-3" style="display: none;">
                                    <div class="alert alert-light border">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-alt text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold" id="fileName"></div>
                                                <small class="text-muted" id="fileDetails"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg" id="uploadBtn">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Upload and Process File
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Progress Indicator -->
                        <div id="uploadProgress" class="mt-3" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Processing...</span>
                                </div>
                                <span>Processing your file, please wait...</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
</div>


<style>
    .bg-pink {
        background-color: #e91e63 !important;
    }
    
    .info-item {
        margin-bottom: 0.5rem;
    }
    
    .generate-qr-form {
        display: inline-block;
    }

    .card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        border-bottom: 1px solid #e9ecef;
    }
    
    .btn {
        border-radius: 6px;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .badge {
        font-size: 0.75em;
    }

    
    .bg-pink {
        background-color: #e91e63 !important;
    }
    
    .form-select {
        border-radius: 6px;
        min-width: 150px;
    }
    
     .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .table tbody td {
        padding: 1rem 0.75rem;
        border-top: none;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    

     .dropdown-toggle {
        border-radius: 20px;
        padding: 0.375rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .dropdown-toggle::after {
        margin-left: 0.5rem;
    }
    
    .dropdown-menu {
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border: none;
        min-width: 250px;
        padding: 0.5rem 0;
    }
    
    .dropdown-header {
        color: #495057;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.5rem 1rem 0.25rem;
    }
    
    .dropdown-item {
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        border-radius: 0;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(3px);
        color: #495057;
    }
    
    .dropdown-item i {
        width: 16px;
        text-align: center;
    }
    
    #bulkActions .btn {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
        border-radius: 15px;
        font-weight: 500;
    }
    
    .card-header .btn {
        border-radius: 25px;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s ease;
    }
    
    .card-header .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .custom-sticky {
  top: 10%; 
  z-index: 1020;  
 }
</style>

<script>
    let debounceTimer;
    
    document.querySelectorAll('.generate-qr-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });
    });


let debounceTimer;

function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        document.getElementById('studentFilterForm').submit();
    }, 500); 

function showQRCode(qrCodeUrl) {
     const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="${qrCodeUrl}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                </div>

            </div>
        </div>
    `;
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
     modal.addEventListener('hidden.bs.modal', function () {
        modal.remove();
    });
}
}

function deleteStudent(studentId, studentName) {
    if (confirm(`Are you sure you want to delete student "${studentName}"? This action cannot be undone.`)) {
         const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/students/${studentId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}


document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    document.querySelectorAll('.dropdown-menu form').forEach(function(form) {
        form.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});

function downloadTemplate() {
    window.location.href = '{{ route("admin.students.downloadTemplate") }}';
}

 function handleFileSelect(input) {
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileDetails = document.getElementById('fileDetails');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2); 
        
        fileName.textContent = file.name;
        fileDetails.textContent = `Size: ${fileSize} MB | Type: ${file.type || 'Unknown'}`;
        fileInfo.style.display = 'block';
        
         if (file.size > 5 * 1024 * 1024) {
            showImportAlert('File size exceeds 5MB limit. Please choose a smaller file.', 'danger');
            input.value = '';
            fileInfo.style.display = 'none';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['.csv', '.xls', '.xlsx'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            showImportAlert('Invalid file type. Please choose a CSV or Excel file.', 'danger');
            input.value = '';
            fileInfo.style.display = 'none';
            return;
        }
    } else {
        fileInfo.style.display = 'none';
    }
}

 function showImportAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const modalBody = document.querySelector('#importStudentsModal .modal-body');
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
     setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadProgress = document.getElementById('uploadProgress');
            
            uploadBtn.disabled = true;
            uploadProgress.style.display = 'block';
            
             uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        });
    }

     const importModal = document.getElementById('importStudentsModal');
    if (importModal) {
        importModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('importForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadProgress = document.getElementById('uploadProgress');
            const fileInfo = document.getElementById('fileInfo');
            
             if (form) form.reset();
            
             if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Upload and Process File';
            }
            
             if (uploadProgress) uploadProgress.style.display = 'none';
            if (fileInfo) fileInfo.style.display = 'none';
            
             const alerts = document.querySelectorAll('#importStudentsModal .alert-dismissible');
            alerts.forEach(alert => alert.remove());
        });
    }
});

function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        document.getElementById('studentFilterForm').submit();
    }, 500);
}
</script>

@endsection
