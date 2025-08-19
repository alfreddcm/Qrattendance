@extends('teacher/sidebar')
@section('title', 'Students')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    use Illuminate\Support\Facades\Storage;
    
    $missingQr = false;
    if(isset($students)) {
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
    }
    $semesters = isset($semesters) ? $semesters : \App\Models\Semester::all();
    $selectedSemester = request('semester_id');
@endphp

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">👨‍🎓</span>
                    Student Management
                </h4>
                <p class="subtitle mb-0">Manage student records and QR codes</p>
            </div>
        </div>
    </div>

    <div class="container-fluid">
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

        @if(isset($noSectionsAssigned) && $noSectionsAssigned)
            <!-- No Sections Assigned Message -->
            <div class="section-assignment-notice">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5 class="mb-2">No Sections Assigned</h5>
                <p class="mb-3">Please contact the administrator to assign a section before adding students.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('teacher.semesters') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Semester
                    </a>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-refresh me-1"></i>Refresh
                    </button>
                </div>
            </div>
        @else
            <!-- Assigned Sections Display -->
            @if(isset($teacherSections) && $teacherSections->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chalkboard me-1"></i>Your Assigned Sections
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($teacherSections as $section)
                                <span class="badge bg-primary">{{ $section->name }} - Grade {{ $section->gradelevel }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Search and Filters -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-search me-1"></i>Search & Filter
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.students') }}" class="d-flex align-items-center gap-2">
                        <input type="text" name="search" placeholder="Search students..." class="form-control" value="{{ request('search') }}" style="width: 200px;">
                        <select name="semester_id" class="form-select" style="width: 180px;">
                            <option value="">All Semesters</option>
                            @if(isset($semesters))
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>
                                        {{ $sem->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <select name="grade_section" class="form-select" style="width: 200px;">
                            <option value="">All Sections</option>
                            @if(isset($gradeSectionOptions))
                                @foreach($gradeSectionOptions as $option)
                                    <option value="{{ $option['value'] }}" {{ request('grade_section') == $option['value'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        
                        <!-- Filter and Action Buttons -->
                        @if(isset($students) && $students->count() > 0)
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
                                <li>
                                    <a href="{{ route('student.ids.print.my.students') }}" class="dropdown-item" target="_blank">
                                        <i class="fas fa-print me-2"></i>Print My Students' IDs (Ctrl+P)
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <a href="{{ route('teacher.students.export') }}" class="btn btn-outline-success btn-sm me-2">
                            <i class="fas fa-download me-1"></i>Export
                        </a>
                        @endif
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-plus me-1"></i>Add Student
                        </button>
                    </form>
                </div>
            </div>

            <!-- Students List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-1"></i>Students List</span>
                    <span class="badge bg-primary">{{ isset($students) ? $students->count() : 0 }} students</span>
                </div>
                <div class="card-body p-0">
                    @if(isset($students) && $students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                         
                                        <th>Picture</th>
                                        <th class="sortable" data-sort="id_no">
                                            ID No
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="name">
                                            Name
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="section">
                                            Section
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="gender">
                                            Gender
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="age">
                                            Age
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-sort="cp_no">
                                            Contact
                                            <i class="fas fa-sort ms-1 sort-icon"></i>
                                        </th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                    <tr>
                                        
                                        <td>
                                            @if($student->picture)
                                                <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Student Picture" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                            @else
                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-info">{{ $student->id_no }}</span></td>
                                        <td><strong>{{ $student->name }}</strong></td>
                                        <td>
                                            @if($student->section)
                                                <span class="badge bg-success">{{ $student->section->name }} - Grade {{ $student->section->gradelevel }}</span>
                                            @else
                                                <span class="badge bg-warning">No Section</span>
                                            @endif
                                        </td>
                                        <td>{{ $student->gender }}</td>
                                        <td>{{ $student->age }}</td>
                                        <td>{{ $student->cp_no ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                                $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                                                $qrPngExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png');
                                            @endphp
                                            @if($qrSvgExists)
                                                <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg') }}" 
                                                     alt="QR Code" 
                                                     style="width: 40px; height: 40px; cursor: pointer;" 
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#qrModal{{ $student->id }}"
                                                     title="Click to view larger QR code">
                                            @elseif($qrPngExists)
                                                <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png') }}" 
                                                     alt="QR Code" 
                                                     style="width: 40px; height: 40px; cursor: pointer;" 
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#qrModal{{ $student->id }}"
                                                     title="Click to view larger QR code">
                                            @else
                                                <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#generateQrModal{{ $student->id }}">
                                                    <i class="fas fa-qrcode"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#infoModal{{ $student->id }}" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="editStudent({{ $student->id }})" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteStudent({{ $student->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @if(!$qrSvgExists && !$qrPngExists)
                                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#generateQrModal{{ $student->id }}">
                                                        <i class="fas fa-magic"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-2"></i>
                            <h5 class="text-muted">No students found</h5>
                            <p class="text-muted">Start by adding students to your assigned sections.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addStudentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Student
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6 mb-3">
                            <label for="add_id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_id_no" name="id_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        
                        <!-- Section Assignment -->
                         
                            
                            <!-- Existing Section Selection -->
                            <div id="add_existing_section_fields">
                                <select class="form-select" id="add_section_id" name="section_id">
                                    <option value="">Select Section</option>
                                    @if(isset($teacherSections))
                                        @foreach($teacherSections as $section)
                                            <option value="{{ $section->id }}">{{ $section->name }} - Grade {{ $section->gradelevel }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <!-- New Section Creation -->
                            <div id="add_new_section_fields" style="display: none;" class="section-creation-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="add_new_section_name" class="form-label">Section Name</label>
                                        <input type="text" class="form-control" id="add_new_section_name" name="new_section_name" placeholder="e.g., STEM, HUMSS">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="add_new_section_gradelevel" class="form-label">Grade Level</label>
                                        <select class="form-select" id="add_new_section_gradelevel" name="new_section_gradelevel">
                                            <option value="">Select Grade</option>
                                            <option value="7">Grade 7</option>
                                            <option value="8">Grade 8</option>
                                            <option value="9">Grade 9</option>
                                            <option value="10">Grade 10</option>
                                            <option value="11">Grade 11</option>
                                            <option value="12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                                <small class="text-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Creating a new section will automatically assign it to you.
                                </small>
                            </div>
                        </div>
                        
                        <!-- Personal Information -->
                        <div class="col-md-4 mb-3">
                            <label for="add_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_age" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_age" name="age" min="1" max="100" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_cp_no" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_cp_no" name="cp_no" required>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="add_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="add_address" name="address" rows="2" required></textarea>
                        </div>
                        
                        <!-- Picture Upload -->
                        <div class="col-12 mb-3">
                            <label for="add_picture" class="form-label">Student Picture</label>
                            <input type="file" class="form-control" id="add_picture" name="picture" accept="image/*">
                        </div>
                        
                        <!-- Emergency Contact -->
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_name" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="add_contact_person_name" name="contact_person_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_relationship" class="form-label">Relationship</label>
                            <input type="text" class="form-control" id="add_contact_person_relationship" name="contact_person_relationship">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_contact" class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control" id="add_contact_person_contact" name="contact_person_contact">
                        </div>
                        
                        <!-- Semester -->
                        <div class="col-12 mb-3">
                            <label for="add_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @if(isset($semesters))
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Student Information Modals -->
@if(isset($students))
@foreach($students as $student)
<div class="modal fade" id="infoModal{{ $student->id }}" tabindex="-1" aria-labelledby="infoModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2">
                                            <i class="fas fa-info-circle me-2"></i>Personal Info
                                        </h6>
                                        <div class="info-item">
                                            <strong>Section:</strong> 
                                            @if(isset($student->section))
                                                <span class="badge bg-primary">{{ $student->section->name ?? 'N/A' }}</span>
                                            @else
                                                <span class="badge bg-warning">No Section</span>
                                            @endif
                                        </div>
                                        <div class="info-item">
                                            <strong>Grade Level:</strong> 
                                            @if(isset($student->section))
                                                <span class="badge bg-success">{{ $student->section->gradelevel ?? 'N/A' }}</span>
                                            @else
                                                <span class="badge bg-warning">N/A</span>
                                            @endif
                                        </div>
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
                                    <div class="card-body">
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
@endif

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status" aria-hidden="true"></div>
                </div>
                <h4 class="mt-3">Generating QR Codes...</h4>
                <p>Please wait while we generate QR codes for your students.</p>
            </div>
        </div>
    </div>
</div>

<style>
.bg-pink {
    background-color: #ec4899 !important;
}

.info-item {
    margin-bottom: 8px;
}

.info-item strong {
    color: #495057;
}

/* QR Code hover effects */
img[data-bs-toggle="modal"] {
    transition: transform 0.2s ease;
}

img[data-bs-toggle="modal"]:hover {
    transform: scale(1.1);
}

/* Table hover effects */
.table-hover tbody tr:hover td {
    background-color: rgba(0,123,255,.075);
}

/* Button spacing */
.btn-group .btn {
    margin-right: 2px;
}

/* Modal improvements */
.modal-body .info-item {
    padding: 4px 0;
}

.modal-body .card {
    margin-bottom: 15px;
}

/* Badge improvements */
.badge {
    font-size: 0.8em;
}

/* Loading spinner */
.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Sortable table headers */
.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
    transition: background-color 0.2s ease;
}

.sortable:hover {
    background-color: rgba(0,123,255,.1) !important;
}

.sortable .sort-icon {
    opacity: 0.3;
    transition: opacity 0.2s ease;
}

.sortable:hover .sort-icon {
    opacity: 0.8;
}

.sortable.sort-asc .sort-icon::before {
    content: "\f0de"; /* fa-sort-up */
    opacity: 1;
    color: #007bff;
}

.sortable.sort-desc .sort-icon::before {
    content: "\f0dd"; /* fa-sort-down */
    opacity: 1;
    color: #007bff;
}

.sortable.sort-asc .sort-icon,
.sortable.sort-desc .sort-icon {
    opacity: 1;
}
</style>

<script>
// Section option toggle and other functionality
document.addEventListener('DOMContentLoaded', function() {
    const existingRadio = document.getElementById('add_existing_section');
    const createRadio = document.getElementById('add_create_section');
    const existingFields = document.getElementById('add_existing_section_fields');
    const newFields = document.getElementById('add_new_section_fields');
    
    function toggleSectionFields() {
        if (createRadio && createRadio.checked) {
            existingFields.style.display = 'none';
            newFields.style.display = 'block';
        } else {
            existingFields.style.display = 'block';
            newFields.style.display = 'none';
        }
    }
    
    if (existingRadio) existingRadio.addEventListener('change', toggleSectionFields);
    if (createRadio) createRadio.addEventListener('change', toggleSectionFields);
    
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Update select all when individual checkboxes change
    studentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            const totalCount = studentCheckboxes.length;
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = (checkedCount === totalCount);
                selectAllCheckbox.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
            }
        });
    });

    // Table sorting functionality
    initializeTableSorting();
});

// Add student form submission
const addStudentForm = document.getElementById('addStudentForm');
if (addStudentForm) {
    addStudentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("teacher.students.add") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding student');
        });
    });
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
        const loadingModal = document.getElementById('loadingModal');
        if (loadingModal) {
            var bootstrapModal = new bootstrap.Modal(loadingModal);
            bootstrapModal.show();
        }
    });
});

// Form submission with loading state for generate all QR
const generateAllQrForm = document.getElementById('generateAllQrForm');
if (generateAllQrForm) {
    generateAllQrForm.addEventListener('submit', function() {
        const button = this.querySelector('button[type="submit"]');
        button.classList.add('loading');
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    });
}

// Utility functions
function editStudent(studentId) {
    // Implementation for edit functionality
    console.log('Edit student:', studentId);
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        fetch(`/teacher/students/${studentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting student: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting student');
        });
    }
}

function generateQR(studentId) {
    fetch(`/teacher/students/${studentId}/generate-qr`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error generating QR code: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating QR code');
    });
}

// JavaScript Functions
function confirmGenerate() {
    return confirm('Are you sure you want to generate a QR code for this student?');
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Table sorting functionality
function initializeTableSorting() {
    const table = document.querySelector('.table');
    const sortableHeaders = document.querySelectorAll('.sortable');
    
    if (!table || sortableHeaders.length === 0) return;
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortColumn = this.dataset.sort;
            const currentSort = this.classList.contains('sort-asc') ? 'asc' : 
                               this.classList.contains('sort-desc') ? 'desc' : 'none';
            
            // Remove sort classes from all headers
            sortableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Determine new sort direction
            let newSort = 'asc';
            if (currentSort === 'asc') {
                newSort = 'desc';
            } else if (currentSort === 'desc') {
                newSort = 'asc';
            }
            
            // Add sort class to current header
            this.classList.add(`sort-${newSort}`);
            
            // Sort the table
            sortTable(table, sortColumn, newSort);
        });
    });
}

function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const sortedRows = rows.sort((a, b) => {
        let aVal = getCellValue(a, column);
        let bVal = getCellValue(b, column);
        
        // Handle different data types
        if (column === 'age') {
            aVal = parseInt(aVal) || 0;
            bVal = parseInt(bVal) || 0;
        } else if (column === 'id_no') {
            // Extract numeric part from badge if present
            aVal = aVal.replace(/[^\d]/g, '') || aVal;
            bVal = bVal.replace(/[^\d]/g, '') || bVal;
        } else {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (aVal < bVal) return direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return direction === 'asc' ? 1 : -1;
        return 0;
    });
    
    // Re-append sorted rows
    sortedRows.forEach(row => tbody.appendChild(row));
}

function getCellValue(row, column) {
    const columnIndex = getColumnIndex(column);
    if (columnIndex === -1) return '';
    
    const cell = row.cells[columnIndex];
    if (!cell) return '';
    
    // Handle special cases
    if (column === 'name') {
        const strong = cell.querySelector('strong');
        return strong ? strong.textContent.trim() : cell.textContent.trim();
    } else if (column === 'section' || column === 'id_no') {
        const badge = cell.querySelector('.badge');
        return badge ? badge.textContent.trim() : cell.textContent.trim();
    } else {
        return cell.textContent.trim();
    }
}

function getColumnIndex(column) {
    const headers = document.querySelectorAll('.table thead th');
    const columnMap = {
        'id_no': 2,
        'name': 3,
        'section': 4,
        'gender': 5,
        'age': 6,
        'cp_no': 7
    };
    
    return columnMap[column] || -1;
}
</script>

@endsection
