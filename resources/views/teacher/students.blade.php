@extends('teacher/sidebar')
@section('title', 'Students')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

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
 
    $sectionCounts = $students->groupBy(function($student) {
                    return $student->section ? $student->section->name : 'Unknown';
                })->map(function($students, $sectionName) {
                    return [
                        'name' => $sectionName,
                        'count' => $students->count(),
                        'icon' => $sectionName === 'STEM' ? 'fas fa-flask' : 
                                 ($sectionName === 'HUMMS' ? 'fas fa-book' : 'fas fa-users'),
                        'color' => $sectionName === 'STEM' ? 'primary' : 
                                  ($sectionName === 'HUMMS' ? 'success' : 'info')
                    ];
                })->filter(function($section) {
                    return $section['name'] !== 'Unknown' && $section['count'] > 0;
                });
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

<!-- Search and Filter Row -->
<div class="row mb-3">
    <div class="col-lg-8">
        <form method="GET" action="{{ route('teacher.students') }}" class="d-flex align-items-center gap-2 flex-wrap">
            @if(request('section_id') && !request('grade_section'))
                <input type="hidden" name="section_id" value="{{ request('section_id') }}">
            @endif
            <input type="text" name="search" placeholder="Search students..." class="form-control form-control-sm" value="{{ request('search') }}" style="width: 200px;">
            <select name="semester_id" class="form-select form-select-sm" style="width: 150px;">
                <option value="">All Semesters</option>
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $selectedSemester == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }}
                    </option>
                @endforeach
            </select>
            <select name="grade_section" class="form-select form-select-sm" style="width: 180px;">
                <option value="">All Grade & Section</option>
                @foreach($gradeSectionOptions as $option)
                    @php
                        $currentSelection = request('grade_section') ?: ($selectedGradeSection ?? '');
                    @endphp
                    <option value="{{ $option['value'] }}" {{ $currentSelection == $option['value'] ? 'selected' : '' }}>
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">My Sections</h5>
        </div>
        <div class="row">

            @if($sectionCounts->count() > 0)
                @foreach($sectionCounts as $section)
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle bg-{{ $section['color'] }}-subtle text-{{ $section['color'] }} me-3">
                                    <i class="{{ $section['icon'] }}"></i>
                                </div>
                                <div>
                                    <h6 class="card-title mb-1 fw-bold">{{ $section['name'] }}</h6>
                                    <p class="card-text text-muted mb-0">{{ $section['count'] }} students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-info-circle me-2"></i>
                        No sections found. Add students to see section overview.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>



<div class="card shadow-sm sticky-card">
    <div class="card-header bg-primary text-white p-2 sticky-card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
             <div class="d-flex align-items-center">
                <h6 class="mb-0 fs-6 me-3">
                    <span class="me-1">👨‍🎓</span>
                    Student Records
                </h6>
                <span class="badge bg-light text-primary fs-6">{{ count($students) }} Students</span>
            </div>
            
             <div class="d-flex align-items-center gap-2 flex-wrap">
                 <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-1"></i>Add Student
                </button>
                
                @if(count($students) > 0)
                    <div class="btn-group">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="qrActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
               
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="idActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-id-card me-1"></i>Student IDs
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="idActionsDropdown">
                        <li>
                            <a href="{{ route('student.ids.print.my.students') }}" class="dropdown-item" target="_blank">
                                <i class="fas fa-print me-2"></i>Print My Students' IDs (Ctrl+P)
                            </a>
                        </li>
                    </ul>

                    <a href="{{ route('teacher.students.export') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-download me-1"></i>Export
                    </a>
                @endif
                
                 <div class="border-start border-light ps-2 ms-1">
                    <form method="GET" action="{{ route('teacher.students') }}" class="d-flex align-items-center gap-1" id="sortForm">
                         @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('semester_id'))
                            <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                        @endif
                        @if(request('grade_section'))
                            <input type="hidden" name="grade_section" value="{{ request('grade_section') }}">
                        @endif
                        
                        <select name="sort_by" class="form-select form-select-sm" style="width: 90px;" onchange="this.form.submit()">
                            <option value="name" {{ request('sort_by', 'name') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="gender" {{ request('sort_by') == 'gender' ? 'selected' : '' }}>Gender</option>
                            <option value="age" {{ request('sort_by') == 'age' ? 'selected' : '' }}>Age</option>
                        </select>
                        
                        <select name="sort_order" class="form-select form-select-sm" style="width: 65px;" onchange="this.form.submit()">
                            <option value="asc" {{ request('sort_order', 'asc') == 'asc' ? 'selected' : '' }}>A-Z</option>
                            <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Z-A</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        @if(count($students) > 0)
            <div class="table-responsive" style="max-height: 73vh; overflow-y: auto;">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th class="text-center" style="width: 80px;">Photo</th>
                            <th>
                                Name & ID
                            </th>
                            <th>
                                Section
                            </th>
                            <th class="text-center" style="width: 150px;">
                                Gender
                            </th>
                            <th class="text-center" style="width: 80px;">
                                Age
                            </th>
                            <th>Contact Details</th>
                            <th class="text-center" style="width: 100px;">
                                QR Code
                            </th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="students-table-body">
                        @foreach($students as $index => $student)
                        <tr>
                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                            
                             <td class="text-center align-middle">
                                @php
                                    $hasValidImage = false;
                                    $imageUrl = '';
                                    
                                    if($student->picture) {
                                        // Check storage disk first
                                        if(Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                                            $hasValidImage = true;
                                            $imageUrl = Storage::url('student_pictures/' . $student->picture);
                                        }
                                        // Fallback to public path check
                                        elseif(file_exists(public_path('storage/student_pictures/' . $student->picture))) {
                                            $hasValidImage = true;
                                            $imageUrl = asset('storage/student_pictures/' . $student->picture);
                                        }
                                    }
                                @endphp

                                @if($hasValidImage)
                                    <img src="{{ $imageUrl }}" alt="Student Picture" 
                                         class="rounded-circle" 
                                         style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; border: 2px solid #dee2e6;">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                @endif



                            </td>
                            
                             <td>
                                <div class="fw-bold">{{ $student->name }}</div>
                                <small class="text-muted">ID: {{ $student->id_no }}</small>
                            </td>
                            
                             <td>
                                @if($student->section)
                                    <span class="badge bg-primary">{{ $student->section->name }}</span><br>
                                    <small class="text-muted">Grade {{ $student->section->gradelevel }}</small>
                                @else
                                    <span class="badge bg-secondary">{{ $student->section_name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">Grade {{ $student->grade_level ?? 'N/A' }}</small>
                                @endif
                            </td>
                            
                             <td class="text-center">
                                @if($student->gender == 'M')
                                    <span class=" ">Male</span>
                                @elseif($student->gender == 'F')
                                    <span class=" ">Female</span>
                                @else
                                    <span class=" ">{{ $student->gender }}</span>
                                @endif
                            </td>
                            
                             <td class="text-center">
                                <span class=" ">{{ $student->age }}</span>
                            </td>
                            
                             <td>
                                <div class="small">
                                    @if($student->cp_no)
                                        <div><i class="fas fa-phone text-muted me-1"></i>{{ $student->cp_no }}</div>
                                    @endif
                                    @if($student->address)
                                        <div class="text-truncate" style="max-width: 150px;" title="{{ $student->address }}">
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>{{ $student->address }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                             <td class="text-center">
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
                                         class="qr-code-display border rounded"
                                         style="width: 40px; height: 40px; cursor: pointer;"
                                         data-bs-toggle="modal"
                                         data-bs-target="#qrModal{{ $student->id }}"
                                         title="Click to view larger QR code">
                                @else
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#generateQrModal{{ $student->id }}" 
                                            title="Generate QR">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                @endif
                            </td>
                            
                             <td>
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#infoModal{{ $student->id }}" 
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" 
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('teacher.students.edit', $student->id) }}" class="dropdown-item">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('student.id.print', $student->id) }}" class="dropdown-item" target="_blank">
                                                <i class="fas fa-print me-2"></i>Print ID
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('teacher.students.destroy', $student->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to remove this student?')">
                                                    <i class="fas fa-trash me-2"></i>Remove
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
            <div class="modal-body p-3">
                <div class="row g-3">
                    <!-- Left Column - Photo & QR -->
                    <div class="col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center p-3">
                                @php
                                    $hasValidModalImage = false;
                                    $modalImageUrl = '';
                                    
                                    if($student->picture) {
                                        if(Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                                            $hasValidModalImage = true;
                                            $modalImageUrl = Storage::url('student_pictures/' . $student->picture);
                                        }
                                        elseif(file_exists(public_path('storage/student_pictures/' . $student->picture))) {
                                            $hasValidModalImage = true;
                                            $modalImageUrl = asset('storage/student_pictures/' . $student->picture);
                                        }
                                    }
                                @endphp

                                <!-- Student Photo -->
                                @if($hasValidModalImage)
                                    <img src="{{ $modalImageUrl }}" alt="Student Picture" 
                                         class="rounded-circle mb-3" 
                                         style="width: 200px; height: 200px; object-fit: cover; border: 3px solid #6366f1;">
                                @else
                                    <div class="bg-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                         style="width: 200px; height: 200px; border: 3px solid #dee2e6;">
                                        <i class="fas fa-user text-muted" style="font-size: 60px;"></i>
                                    </div>
                                @endif
                                
                                <!-- Student Name & ID -->
                                <h6 class="mb-1 fw-bold">{{ $student->name }}</h6>
                                <div class="badge bg-primary mb-3">{{ $student->id_no }}</div>

                                <!-- QR Code Section -->
                                <div class="border-top pt-3">
                                    <div class="mb-2">
                                        <small class="text-muted fw-bold"><i class="fas fa-qrcode me-1"></i>QR Code</small>
                                    </div>
                                    @php
                                        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                                        $qrSvgExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg');
                                        $qrPngExists = Storage::disk('public')->exists('qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png');
                                    @endphp
                                    @if($qrSvgExists)
                                        <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg') }}" alt="QR Code" 
                                             class="border rounded" style="width: 180px; height: 180px;">
                                    @elseif($qrPngExists)
                                        <img src="{{ asset('storage/qr_codes/' . $student->id_no . '_' . $sanitizedName . '.png') }}" alt="QR Code" 
                                             class="border rounded" style="width: 180px; height: 180px;">
                                    @else
                                        <div class="text-muted">
                                            <i class="fas fa-qrcode fa-5x mb-1"></i>
                                            <div><small>No QR Code</small></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Information -->
                    <div class="col-md-8">
                        <div class="row g-2">
                            <!-- Personal Information -->
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-header bg-primary text-white py-2">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 70px;">Section:</small>
                                                    <span class="badge bg-primary">{{ $student->section->name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 50px;">Grade:</small>
                                                    <span class="fw-semibold">{{ $student->section->gradelevel ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 70px;">Gender:</small>
                                                    <span class="fw-semibold">
                                                        @if($student->gender == 'M') Male
                                                        @elseif($student->gender == 'F') Female
                                                        @else {{ $student->gender }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 50px;">Age:</small>
                                                    <span class="fw-semibold">{{ $student->age }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-header bg-success text-white py-2">
                                        <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Information</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="d-flex align-items-start py-1">
                                                    <small class="text-muted me-3" style="min-width: 60px;">Phone:</small>
                                                    <span class="fw-semibold">{{ $student->cp_no ?: 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex align-items-start py-1">
                                                    <small class="text-muted me-3" style="min-width: 60px;">Address:</small>
                                                    <span class="fw-semibold">{{ $student->address ?: 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-header bg-warning text-dark py-2">
                                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Emergency Contact</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 80px;">Name:</small>
                                                    <span class="fw-semibold">{{ $student->contact_person_name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 90px;">Relationship:</small>
                                                    <span class="fw-semibold">{{ $student->contact_person_relationship ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 60px;">Contact:</small>
                                                    <span class="fw-semibold">{{ $student->contact_person_contact ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <a href="{{ route('student.id.print', $student->id) }}" class="btn btn-outline-primary btn-sm" title="Print Student ID" target="_blank">
                    <i class="fas fa-print me-1"></i>Print ID
                </a>
                <a href="{{ route('teacher.students.edit', $student->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
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
                    
                    $modalQrImagePath = ''  ;
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

        
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('teacher.students.add') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="addStudentModalLabel">
                                <i class="fas fa-user-plus me-2"></i>Add New Student
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                 <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-secondary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                        <h6 class="mb-0 text-secondary fw-bold">Student Picture</h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="student-photo-placeholder mb-2" onclick="document.getElementById('picture').click();" style="width: 100px; height: 100px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #6c757d; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #6c757d; cursor: pointer; position: relative;">
                                                <i class="fas fa-user-circle" style="font-size: 2rem; margin-bottom: 5px; opacity: 0.7;"></i>
                                                <span style="font-size: 0.7rem; font-weight: 500; text-align: center;">No Photo<br><small>Click to add</small></span>
                                                <div style="position: absolute; top: 5px; right: 5px; background: #007bff; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                             <div id="photo-controls" class="d-flex gap-2 mb-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCameraModal()">
                                                    <i class="fa fa-camera me-1"></i>Take Photo
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('picture').click()">
                                                    <i class="fa fa-upload me-1"></i>Upload File
                                                </button>
                                            </div>
                                            
                                             <input type="file" class="form-control d-none" id="picture" name="picture" accept="image/*">
                                            <input type="hidden" id="captured_image" name="captured_image">
                                            <div id="image-preview" class="mt-2" style="display: none;">
                                                <img id="preview-img" src="" alt="Preview" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px; border: 3px solid #28a745;">
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="removePhoto()">
                                                        <i class="fa fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted">Upload a student photo or take a photo with camera</small>
                                        </div>
                                    </div>
                                </div>

                                 <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <h6 class="mb-0 text-primary fw-bold">Basic Information</h6>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="id_no" name="id_no" required placeholder="e.g., 2024-001">
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name (LN, FN MI.) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required placeholder="Dela Cruz, Juan M.">
                                </div>
                                
                                 <div class="col-12 mt-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <h6 class="mb-0 text-success fw-bold">Academic Information</h6>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="section_id" class="form-label">Grade & Section <span class="text-danger">*</span></label>
                                    <select class="form-select" id="section_id" name="section_id" required>
                                        <option value="">Select Grade & Section</option>
                                        @foreach($teacherSections as $section)
                                            <option value="{{ $section->id }}" data-grade="{{ $section->gradelevel }}">
                                                Grade {{ $section->gradelevel }} - {{ $section->name }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                
                                 <div class="col-12 mt-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <h6 class="mb-0 text-info fw-bold">Personal Information</h6>
                                    </div>
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
                                    <input type="number" class="form-control" id="age" name="age" min="13" max="25" required placeholder="Age">
                                </div>
                                <div class="col-md-4">
                                    <label for="cp_no" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="cp_no" name="cp_no" placeholder="09XX XXX XXXX">
                                </div>
                                <div class="col-md-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" placeholder="Complete address">
                                </div>
                                
                                 <div class="col-12 mt-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-danger text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <h6 class="mb-0 text-danger fw-bold">Emergency Contact <span class="text-danger">*</span></h6>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_person_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_person_name" name="contact_person_name" required placeholder="Parent/Guardian name">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_person_contact" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_person_contact" name="contact_person_contact" required placeholder="09XX XXX XXXX">
                                </div>
                                <div class="col-md-12">
                                    <label for="contact_person_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                                    <select class="form-select" id="contact_person_relationship" name="contact_person_relationship" required>
                                        <option value="">Select Relationship</option>
                                        <option value="Parent">Parent</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Sibling">Sibling</option>
                                        <option value="Relative">Relative</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importStudentsModal" data-bs-dismiss="modal">
                                <i class="fas fa-file-excel me-1"></i>Import Excel File
                            </button>
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
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title" id="importStudentsModalLabel">
                            <i class="fas fa-file-excel me-1"></i>Import Students from Excel
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="row g-3">
                             <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light border-0 py-2">
                                        <small class="text-success fw-bold">
                                            <i class="fas fa-list-ol me-1"></i>Quick Steps
                                        </small>
                                    </div>
                                    <div class="card-body p-3">
                                        <ol class="mb-2 small">
                                            <li class="mb-1">Download template file</li>
                                            <li class="mb-1">Fill in student data</li>
                                            <li class="mb-1">Save and upload file</li>
                                            <li class="mb-1">Review and confirm</li>
                                        </ol>
                                        
                                        <div class="d-grid mb-2">
                                            <a href="{{ route('teacher.students.downloadTemplate') }}" class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>Download Template
                                            </a>
                                        </div>
                                        
                                        <div class="bg-light p-2 rounded">
                                            <small class="text-success fw-bold d-block mb-1">
                                                <i class="fas fa-check-circle me-1"></i>Supported Formats
                                            </small>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <small class="d-flex align-items-center">
                                                        <i class="fas fa-file-excel text-success me-1"></i>.xlsx, .xls, .csv
                                                    </small>
                                                </div>
                                            </div>
                                            <small class="text-muted">Max: 5MB</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                             <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light border-0 py-2">
                                        <small class="text-primary fw-bold">
                                            <i class="fas fa-upload me-1"></i>Upload Your File
                                        </small>
                                    </div>
                                    <div class="card-body p-3">
                                        <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="file" class="form-label small">Select Excel or CSV File</label>
                                                <div class="border-2 border-dashed border-primary rounded p-3 text-center bg-light">
                                                    <div class="mb-2">
                                                        <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                                                    </div>
                                                    <input type="file" name="file" id="file" class="form-control form-control-sm" accept=".csv, .xls, .xlsx" required>
                                                    <small class="form-text text-muted mt-1 d-block">
                                                        Choose template format file
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-upload me-1"></i>Upload & Import
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                         <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-warning py-2 border-0">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-exclamation-triangle text-warning me-2 mt-1"></i>
                                        <div>
                                            <small class="fw-bold text-warning d-block mb-1">Important Notes</small>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <ul class="mb-0" style="font-size: 0.75rem;">
                                                        <li>Fill all required fields: Student ID, Name <strong>(LN, FN MI.)</strong>, Gender (M/F), Age, Address, Contact Phone, Emergency Contact Name, Relationship (Parent/Guardian/etc),  Emergency Contact Phone</li>
                                                        <li>Name format must be: <strong>Last Name, First Name Middle Initial.</strong> (example: Dela Cruz, Juan M.)</li>
                                                        <li>Student IDs must be unique - duplicates will be skipped</li>
                                                     </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>
                </</div>
            </div>
        </div>

<!-- Camera Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true" style="z-index: 1070 !important;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cameraModalLabel">
                    <i class="fa fa-camera me-2"></i>Take Photo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="camera-container">
                    <video id="camera-video" autoplay playsinline class="camera-video" style="width: 100%; max-width: 400px; border-radius: 15px; border: 3px solid #007bff;"></video>
                    <canvas id="camera-canvas" style="display: none;"></canvas>
                </div>
                <div id="camera-controls" class="mt-3">
                    <button type="button" class="btn btn-primary" onclick="capturePhoto()">
                        <i class="fa fa-camera"></i> Capture Photo
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="retakePhoto()" style="display: none;" id="retake-btn">
                        <i class="fa fa-redo"></i> Retake
                    </button>
                </div>
                <div id="camera-preview" class="mt-3" style="display: none;">
                    <img id="captured-photo" src="" alt="Captured Photo" style="width: 200px; height: 200px; object-fit: cover; border-radius: 15px; border: 3px solid #28a745;">
                </div>
                <div id="camera-error" class="mt-3 alert alert-danger" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i> Camera not available. Please upload a file instead.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="usePhoto()" id="use-photo-btn" style="display: none;">
                    <i class="fa fa-check"></i> Use Photo
                </button>
            </div>
        </div>
    </div>
</div>

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
     .table-responsive {
        border-radius: 0 0 0.375rem 0.375rem;
    }
    
    .sticky-top {
        background-color: #f8f9fa !important;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .sticky-top th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        vertical-align: middle;
        padding: 12px 8px;
    }
    
    /* Sort notification styling */
    .sort-notification {
        border-radius: 8px;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        font-size: 0.9rem;
    }
    
     .sticky-card-header {
        position: sticky;
        top: 0;
        z-index: 100;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
     .table-responsive::-webkit-scrollbar {
        width: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #6366f1;
        border-radius: 4px;
        transition: background 0.3s ease;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #4f46e5;
    }
    
     .card-body {
        padding: 0 !important;
    }
    
    .table {
        margin-bottom: 0 !important;
    }
    
     .table tbody tr {
        border-bottom: 1px solid #dee2e6;
    }
    
    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.04);
    }
    
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
    
     .row {
        scrollbar-width: thin;
        scrollbar-color: #6366f1 #f1f1f1;
    }
    
     .icon-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
    }
    
    .bg-primary-subtle {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-subtle {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-info-subtle {
        background-color: rgba(13, 202, 240, 0.1);
    }

    .student-card {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        background: white;
        transition: all 0.2s ease;
        height: 280px;
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
        height: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    
    .student-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 3px;
        line-height: 1.2;
    }
    
    .student-id-text {
        font-size: 0.7rem;
        color: #6b7280;
        margin-bottom: 4px;
    }
    
    .student-details {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    
    .detail-item {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 2px;
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
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px solid #f1f5f9;
        flex: 1;
    }
    
    .info-item {
        padding: 0 2px ;
        margin-bottom: 4px;
    }
    
    .info-item small {
        font-size: 0.7rem;
        color: #6b7280;
        font-weight: 500;
    }
    
     .editable-field {
        position: relative;
    }
    
    .editable-field .edit-btn {
        opacity: 0.6;
        transition: opacity 0.2s ease;
        font-size: 0.7rem !important;
        padding: 0.1rem 0.3rem !important;
        background-color: rgba(13, 110, 253, 0.1);
        border-color: rgba(13, 110, 253, 0.3);
    }
    
    .editable-field:hover .edit-btn {
        opacity: 1;
        background-color: rgba(13, 110, 253, 0.2);
        border-color: rgba(13, 110, 253, 0.5);
    }
    
    .editable-input .input-group {
        max-width: 200px;
    }
    
    .editable-input .form-control,
    .editable-input .form-select {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .editable-input .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
    }
    
    .student-name .edit-btn {
        margin-left: 0.5rem;
    }
    
    .detail-item .edit-btn {
        margin-left: 0.25rem;
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
    
     #cameraModal {
        z-index: 1070 !important;
    }
    
    #cameraModal .modal-backdrop {
        z-index: 1065 !important;
    }
    
     #addStudentModal {
        z-index: 1055 !important;
    }
</style>

<script>
 document.addEventListener('DOMContentLoaded', function() {
     loadAvailableSections();
    
      const emergencyToggle = document.querySelector('[data-bs-target="#emergencyContact"]');
     if (emergencyToggle) {
         emergencyToggle.addEventListener('click', function() {
             const icon = this.querySelector('i');
             icon.classList.toggle('fa-chevron-down');
             icon.classList.toggle('fa-chevron-up');
         });
     }

      const pictureInput = document.getElementById('picture');
     const imagePreview = document.getElementById('image-preview');
     const previewImg = document.getElementById('preview-img');
     const placeholder = document.querySelector('.student-photo-placeholder');

     if (pictureInput) {
         pictureInput.addEventListener('change', function(e) {
             const file = e.target.files[0];
             if (file) {
                 const reader = new FileReader();
                 reader.onload = function(e) {
                     previewImg.src = e.target.result;
                     imagePreview.style.display = 'block';
                     if (placeholder) {
                         placeholder.style.display = 'none';
                     }
                 };
                 reader.readAsDataURL(file);
             }
         });
     }
    
      const fileInput = document.getElementById('file');
     if (fileInput) {
         fileInput.addEventListener('change', function() {
             const fileName = this.files[0]?.name || 'No file selected';
             const label = this.parentElement.querySelector('.form-text');
             if (label) {
                 label.textContent = `Selected: ${fileName}`;
                 label.classList.add('text-success');
             }
         });
     }
    
      const addStudentForm = document.querySelector('#addStudentModal form');
     if (addStudentForm) {
         addStudentForm.addEventListener('submit', function(e) {
             const requiredFields = this.querySelectorAll('[required]');
             let isValid = true;
             
             requiredFields.forEach(field => {
                 if (!field.value.trim()) {
                     field.classList.add('is-invalid');
                     isValid = false;
                 } else {
                     field.classList.remove('is-invalid');
                     field.classList.add('is-valid');
                 }
             });
             
             if (!isValid) {
                 e.preventDefault();
                 const firstInvalid = this.querySelector('.is-invalid');
                 if (firstInvalid) {
                     firstInvalid.focus();
                     firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 }
             }
         });
     }
    
     document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const editableField = this.closest('.editable-field');
            enterEditMode(editableField);
        });
    });
    
     document.addEventListener('click', function(e) {
        if (e.target.closest('.save-btn')) {
            e.preventDefault();
            const editableField = e.target.closest('.editable-field');
            saveField(editableField);
        }
        
        if (e.target.closest('.cancel-btn')) {
            e.preventDefault();
            const editableField = e.target.closest('.editable-field');
            cancelEdit(editableField);
        }
    });

let availableSections = {};

function loadAvailableSections() {
    fetch('/teacher/students/sections')
        .then(response => response.json())
        .then(data => {
            availableSections = data;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
             availableSections = [
                { grade_level: 'Grade 11', sections: ['STEM', 'ABM'] },
                { grade_level: 'Grade 12', sections: ['HUMMS', 'STEM'] }
            ];
        });
}

function enterEditMode(editableField) {
    const display = editableField.querySelector('.editable-display');
    const input = editableField.querySelector('.editable-input');
    const field = editableField.dataset.field;
    
    display.style.display = 'none';
    input.style.display = 'block';
    
    if (field === 'section') {
        populateSectionOptions(editableField);
    }
    
     const inputElement = input.querySelector('input, select');
    if (inputElement) {
        inputElement.focus();
    }
}

function populateSectionOptions(editableField) {
    const select = editableField.querySelector('.section-select');
    const currentGradeLevel = editableField.closest('.student-info-section')
        .querySelector('[data-field="grade_level"] .badge').textContent.trim();
    const currentSection = editableField.querySelector('.badge').textContent.trim();
    
    select.innerHTML = '';
    
     const currentOption = document.createElement('option');
    currentOption.value = currentSection;
    currentOption.textContent = currentSection;
    currentOption.selected = true;
    select.appendChild(currentOption);
    
     const gradeData = availableSections.find(g => g.grade_level === currentGradeLevel);
    if (gradeData) {
        gradeData.sections.forEach(section => {
            if (section !== currentSection) {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                select.appendChild(option);
            }
        });
    }
    
     availableSections.forEach(gradeData => {
        if (gradeData.grade_level !== currentGradeLevel) {
            gradeData.sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = `${gradeData.grade_level} - ${section}`;
                select.appendChild(option);
            });
        }
    });
    
     const newOption = document.createElement('option');
    newOption.value = '_new_';
    newOption.textContent = '+ Add New Section';
    select.appendChild(newOption);
    
     select.addEventListener('change', function() {
        if (this.value === '_new_') {
            const newSection = prompt('Enter new section name:');
            if (newSection) {
                this.innerHTML = '';
                const option = document.createElement('option');
                option.value = newSection;
                option.textContent = newSection;
                option.selected = true;
                this.appendChild(option);
            } else {
                this.value = currentSection;
            }
        }
    });
}

function saveField(editableField) {
    const studentId = editableField.dataset.studentId;
    const field = editableField.dataset.field;
    const inputElement = editableField.querySelector('.editable-input input, .editable-input select');
    const newValue = inputElement.value.trim();
    
    if (!newValue) {
        alert('Value cannot be empty');
        return;
    }
    
     const saveBtn = editableField.querySelector('.save-btn');
    const originalHTML = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
     const updateData = {
        [field]: newValue,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        _method: 'PATCH'
    };
    
     fetch(`/teacher/students/${studentId}/quick-update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': updateData._token
        },
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
             updateFieldDisplay(editableField, newValue, field);
            exitEditMode(editableField);
            showAlert('success', data.message || 'Updated successfully');
        } else {
            throw new Error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showAlert('error', error.message || 'Failed to update. Please try again.');
        saveBtn.innerHTML = originalHTML;
        saveBtn.disabled = false;
    });
}

function updateFieldDisplay(editableField, newValue, field) {
    const display = editableField.querySelector('.editable-display');
    
    if (field === 'name') {
        const nameElement = display.querySelector('.student-name');
        nameElement.innerHTML = `${newValue} <button class="btn btn-sm btn-outline-primary ms-1 edit-btn" title="Edit Name"><i class="fas fa-edit" style="font-size: 0.7rem;"></i></button>`;
        
         const editBtn = nameElement.querySelector('.edit-btn');
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            enterEditMode(editableField);
        });
    } else {
        const badge = display.querySelector('.badge');
        badge.textContent = newValue;
    }
}

function cancelEdit(editableField) {
    exitEditMode(editableField);
}

function exitEditMode(editableField) {
    const display = editableField.querySelector('.editable-display');
    const input = editableField.querySelector('.editable-input');
    
    display.style.display = 'block';
    input.style.display = 'none';
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
    
     setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

 function confirmGenerate() {
    return confirm('Are you sure you want to generate a QR code for this student?');
}

 document.querySelectorAll('.generate-qr-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var btn = form.querySelector('button[type="submit"]');
        var spinner = btn.querySelector('.spinner-border');
        btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btn.childNodes[1]) btn.childNodes[1].textContent = ' Generating...';
         var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    });
    
 
    let videoStream = null;

    console.log('Camera script loaded - functions being defined');

 

     window.toggleCamera = function() {
        console.log('=== TOGGLE CAMERA FUNCTION CALLED ===');
        
        try {
            const cameraView = document.getElementById('camera-view');
            const photoControls = document.getElementById('photo-controls');
            
            console.log('cameraView element:', cameraView);
            console.log('photoControls element:', photoControls);
            console.log('current cameraView display style:', cameraView ? cameraView.style.display : 'element not found');
            
            if (!cameraView) {
                console.error('ERROR: camera-view element not found in DOM');
                alert('Camera view element not found. Please refresh the page.');
                return;
            }
            
            if (cameraView.style.display === 'none' || cameraView.style.display === '') {
                console.log('Showing camera view...');
                cameraView.style.display = 'block';
                console.log('Camera view display set to block, calling startCamera()');
                window.startCamera();
            } else {
                console.log('Hiding camera view...');
                window.closeCamera();
            }
        } catch (error) {
            console.error('ERROR in toggleCamera function:', error);
            alert('Error in toggleCamera: ' + error.message);
        }
    }

    window.startCamera = function() {
        console.log('=== START CAMERA FUNCTION CALLED ===');
        
        try {
            const video = document.getElementById('camera-video');
            console.log('camera-video element:', video);
            
            if (!video) {
                console.error('ERROR: camera-video element not found');
                alert('Camera video element not found');
                return;
            }
            
             console.log('Checking camera support...');
            console.log('navigator.mediaDevices:', navigator.mediaDevices);
            console.log('getUserMedia:', navigator.mediaDevices ? navigator.mediaDevices.getUserMedia : 'not available');
            
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.error('ERROR: Camera not supported on this device/browser');
                alert('Camera not supported on this device/browser');
                window.closeCamera();
                return;
            }
            
            console.log('Camera supported, requesting camera access...');
            
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 }, 
                    height: { ideal: 480 },
                    facingMode: 'user'  
                } 
            })
            .then(function(stream) {
                console.log('Camera access granted, stream received:', stream);
                videoStream = stream;
                video.srcObject = stream;
                console.log('Video source set successfully');
            })
            .catch(function(err) {
                console.error('ERROR accessing camera:', err);
                console.error('Error name:', err.name);
                console.error('Error message:', err.message);
                alert('Error accessing camera: ' + err.message);
                window.closeCamera();
            });
        } catch (error) {
            console.error('ERROR in startCamera function:', error);
            alert('Error in startCamera: ' + error.message);
        }
    }

    window.closeCamera = function() {
        console.log('=== CLOSE CAMERA FUNCTION CALLED ===');
        
        try {
            const cameraView = document.getElementById('camera-view');
            const video = document.getElementById('camera-video');
            
            console.log('cameraView element:', cameraView);
            console.log('video element:', video);
            console.log('videoStream:', videoStream);
            
             if (videoStream) {
                console.log('Stopping video stream tracks...');
                videoStream.getTracks().forEach(track => {
                    console.log('Stopping track:', track);
                    track.stop();
                });
                videoStream = null;
                console.log('Video stream stopped and cleared');
            } else {
                console.log('No video stream to stop');
            }
            
             if (cameraView) {
                cameraView.style.display = 'none';
                console.log('Camera view hidden');
            }
            
            if (video) {
                video.srcObject = null;
                console.log('Video source cleared');
            }
        } catch (error) {
            console.error('ERROR in closeCamera function:', error);
        }
    }

    window.capturePhoto = function() {
        console.log('=== CAPTURE PHOTO FUNCTION CALLED ===');
        
        try {
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            
            if (!video || !canvas) {
                console.error('ERROR: video or canvas element not found');
                alert('Required elements not found');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            
             canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            console.log('Canvas dimensions set:', canvas.width, 'x', canvas.height);
            
             ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
             const dataURL = canvas.toDataURL('image/jpeg', 0.8);
            console.log('Photo captured, data URL length:', dataURL.length);
            
             document.getElementById('captured_image').value = dataURL;
            window.showImagePreview(dataURL);
            
             window.closeCamera();
            console.log('Photo capture completed');
        } catch (error) {
            console.error('ERROR in capturePhoto function:', error);
            alert('Error capturing photo: ' + error.message);
        }
    }

    window.showImagePreview = function(src) {
        console.log('showImagePreview called with src length:', src ? src.length : 'null');
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (preview && previewImg) {
            previewImg.src = src;
            preview.style.display = 'block';
            console.log('Image preview shown');
        } else {
            console.error('Preview elements not found');
        }
    }
    
    window.removePhoto = function() {
        console.log('removePhoto called');
         const pictureInput = document.getElementById('picture');
        const capturedInput = document.getElementById('captured_image');
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (pictureInput) pictureInput.value = '';
        if (capturedInput) capturedInput.value = '';
        if (preview) preview.style.display = 'none';
        if (previewImg) previewImg.src = '';
        
        console.log('Photo removed and preview hidden');
    }
    
     const pictureInput = document.getElementById('picture');
    if (pictureInput) {
        pictureInput.addEventListener('change', function(e) {
            console.log('File input changed');
            const file = e.target.files[0];
            if (file) {
                console.log('File selected:', file.name, file.size);
                const reader = new FileReader();
                reader.onload = function(e) {
                    window.showImagePreview(e.target.result);
                     const capturedInput = document.getElementById('captured_image');
                    if (capturedInput) capturedInput.value = '';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
     let videoStream = null;
    let cameraModal = null;
    
     window.openCameraModal = function() {
        cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
        cameraModal.show();
        
         document.getElementById('camera-preview').style.display = 'none';
        document.getElementById('retake-btn').style.display = 'none';
        document.getElementById('use-photo-btn').style.display = 'none';
        document.getElementById('camera-error').style.display = 'none';
        document.getElementById('camera-video').style.display = 'block';
        
        startCamera();
    }
    
     function startCamera() {
        const video = document.getElementById('camera-video');
        
         if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraError();
            return;
        }
        
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 }, 
                height: { ideal: 480 },
                facingMode: 'user'
            } 
        })
        .then(function(stream) {
            videoStream = stream;
            video.srcObject = stream;
        })
        .catch(function(err) {
            console.error('Error accessing camera:', err);
            showCameraError();
        });
    }
    
     window.capturePhoto = function() {
        const video = document.getElementById('camera-video');
        const canvas = document.getElementById('camera-canvas');
        const ctx = canvas.getContext('2d');
        
         canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
         ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
         const dataURL = canvas.toDataURL('image/jpeg', 0.8);
        
         const capturedPhoto = document.getElementById('captured-photo');
        capturedPhoto.src = dataURL;
        
         document.getElementById('camera-video').style.display = 'none';
        document.getElementById('camera-preview').style.display = 'block';
        document.getElementById('retake-btn').style.display = 'inline-block';
        document.getElementById('use-photo-btn').style.display = 'inline-block';
    }
    
     window.retakePhoto = function() {
        document.getElementById('camera-preview').style.display = 'none';
        document.getElementById('retake-btn').style.display = 'none';
        document.getElementById('use-photo-btn').style.display = 'none';
        document.getElementById('camera-video').style.display = 'block';
    }
    
     window.usePhoto = function() {
        const capturedPhoto = document.getElementById('captured-photo');
        const dataURL = capturedPhoto.src;
        
         document.getElementById('captured_image').value = dataURL;
        
         showImagePreview(dataURL);
        
         stopCamera();
        cameraModal.hide();
    }
    
     function showCameraError() {
        document.getElementById('camera-video').style.display = 'none';
        document.getElementById('camera-error').style.display = 'block';
    }
    
     function stopCamera() {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }
    }
    
     window.showImagePreview = function(src) {
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (preview && previewImg) {
            previewImg.src = src;
            preview.style.display = 'block';
        }
    }
    
     window.removePhoto = function() {
         const pictureInput = document.getElementById('picture');
        const capturedInput = document.getElementById('captured_image');
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (pictureInput) pictureInput.value = '';
        if (capturedInput) capturedInput.value = '';
        if (preview) preview.style.display = 'none';
        if (previewImg) previewImg.src = '';
    }
    
     const cameraModalElement = document.getElementById('cameraModal');
    if (cameraModalElement) {
        cameraModalElement.addEventListener('hidden.bs.modal', function () {
            stopCamera();
        });
    }
});
</script>

@endsection
