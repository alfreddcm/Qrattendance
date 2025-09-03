@extends('admin.sidebar')
@section('title', 'Manage Students')
@section('content')

<div class="sticky-header" >
    <div class="d-flex justify-content-between align-items-center" style="margin-left: 1rem;">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-chalkboard-teacher me-2"></i>
                Manage Students
            </h4>
            <p class="subtitle fs-6 mb-0">Add, edit, and manage Student informations</p>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

      <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.manage-students') }}" id="studentFilterFormTop" class="row g-2 align-items-center">
                <div class="col-md-2">
                    <label class="form-label small mb-1">School</label>
                    <select name="school_id" class="form-select form-select-sm">
                        <option value="">All Schools</option>
                        @foreach($schools ?? [] as $school)
                            <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Semester</label>
                    <select name="semester_id" class="form-select form-select-sm">
                        <option value="">All Semesters</option>
                        @foreach($semesters ?? [] as $sem)
                            <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>{{ $sem->name ?? $sem->term ?? 'Semester' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Teacher</label>
                    <select name="teacher_id" id="filterTeacher" class="form-select form-select-sm">
                        <option value="">All Teachers</option>
                        @foreach($teachers ?? [] as $t)
                            @php
                                $label = $t->name;
                            @endphp
                            <option value="{{ $t->id }}" data-sections='@json($t->sections ?? [])' {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Section</label>
                    <select name="section_id" id="filterSection" class="form-select form-select-sm">
                        <option value="">All Sections</option>
                        @if(request('teacher_id'))
                            @php
                                $selectedTeacher = $teachers->firstWhere('id', request('teacher_id'));
                            @endphp
                            @if($selectedTeacher && $selectedTeacher->sections)
                                @foreach($selectedTeacher->sections as $sec)
                                    <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->section_name ?? $sec->name }} (Grade {{ $sec->gradelevel ?? $sec->grade_level }})</option>
                                @endforeach
                            @endif
                        @endif
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
                    <button type="submit" class="btn btn-sm btn-success">Apply Filters</button>
                    <a href="{{ route('admin.manage-students') }}" class="btn btn-sm btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

     <!-- Main Header -->
    <div class="card shadow-sm sticky-card" style="margin-left: 1rem; margin-right: 1rem;">
        <div class="card-header bg-primary text-white p-3 sticky-card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <h6 class="mb-0 fs-6 me-3">
                        <span class="me-1">👨‍🎓</span>
                        Student List
                    </h6>
                    <span class="badge bg-light text-primary fs-6">{{ $students->count() }} total</span>
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    
                    
                    <!-- Action Buttons -->
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-1"></i>Add Student
                    </button>
                    
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#importStudentsModal">
                        <i class="fas fa-file-excel me-1"></i>Import Students
                    </button>
                    
                    <button class="btn btn-success btn-sm" onclick="generateAllQrs()">
                        <i class="fas fa-magic me-1"></i>Generate All QR Codes
                    </button>
                    
                    <button class="btn btn-info btn-sm" onclick="window.open('{{ route('student.ids.print.all') }}', '_blank')">
                        <i class="fas fa-print me-1"></i>Print All Student IDs
                    </button>
                    
                </div>
            </div>
        </div>
        
        <!-- Stats Row -->
        @php
            // Note: these counts compute on all students (pagination removed).
            $completeCount = $students->filter(function($s){
                return $s->picture && ($s->qr_code || ($s->id_no && $s->name)) && $s->cp_no;
            })->count();
            $missingCount = $students->count() - $completeCount;
        @endphp
        <div class="p-3 bg-light border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded p-2 me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5">{{ $students->count() }}</div>
                            <small class="text-muted">Total students</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success text-white rounded p-2 me-3">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-success">{{ $completeCount }}</div>
                            <small class="text-muted">Complete profiles</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning text-white rounded p-2 me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-warning">{{ $missingCount }}</div>
                            <small class="text-muted">Missing Profile</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
   
        <div class="card-body p-0">
            @if($students->count() > 0)
                <div class="table-responsive" style="max-height: 73vh; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th class="text-center" style="width: 80px;">Photo</th>
                                <th>Name & ID</th>
                                <th>Section</th>
                                <th class="text-center" style="width: 100px;">Gender</th>
                                <th class="text-center" style="width: 80px;">Age</th>
                                <th>Contact Details</th>
                                <th>School & Teacher</th>
                                <th class="text-center" style="width: 100px;">QR Code</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                            <tr>
                                <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                                
                                <!-- Photo Column -->
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
                                
                                <!-- Name & ID Column -->
                                <td>
                                    <div class="fw-bold">{{ $student->name }}</div>
                                    <small class="text-muted">ID: {{ $student->id_no ?? 'N/A' }}</small>
                                </td>
                                
                                <!-- Section Column -->
                                <td>
                                    @if($student->section)
                                        <span class="badge bg-primary">{{ $student->section->name }}</span><br>
                                        <small class="text-muted">Grade {{ $student->section->gradelevel }}</small>
                                    @else
                                        <span class="badge bg-secondary">{{ $student->section_name ?? 'N/A' }}</span><br>
                                        <small class="text-muted">Grade {{ $student->grade_level ?? 'N/A' }}</small>
                                    @endif
                                </td>
                                
                                <!-- Gender Column -->
                                <td class="text-center">
                                    @if($student->gender == 'M')
                                        <span>Male</span>
                                    @elseif($student->gender == 'F')
                                        <span>Female</span>
                                    @else
                                        <span>{{ $student->gender ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                
                                <!-- Age Column -->
                                <td class="text-center">
                                    <span>{{ $student->age ?? 'N/A' }}</span>
                                </td>
                                
                                <!-- Contact Details Column -->
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
                                        @if($student->contact_person_name)
                                            <div class="text-muted small">
                                                <i class="fas fa-user-friends text-muted me-1"></i>{{ $student->contact_person_name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- School & Teacher Column -->
                                <td>
                                    <div class="small">
                                        @if($student->section && $student->section->teacher)
                                            <div class="fw-semibold text-primary">
                                                <i class="fas fa-chalkboard-teacher text-muted me-1"></i>{{ $student->section->teacher->name }}
                                            </div>
                                        @elseif($student->section && $student->section->teachers->count() > 0)
                                            <div class="fw-semibold text-primary">
                                                <i class="fas fa-chalkboard-teacher text-muted me-1"></i>{{ $student->section->teachers->first()->name }}
                                                @if($student->section->teachers->count() > 1)
                                                    <small class="text-muted">(+{{ $student->section->teachers->count() - 1 }} more)</small>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="text-muted">
                                            <i class="fas fa-school text-muted me-1"></i>{{ $student->school->name ?? 'San Guillermo Vocational and Industrial High School' }}
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- QR Code Column -->
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
                                             style="width: 40px; height: 40px; border-radius: 4px;" 
                                             data-bs-toggle="modal" data-bs-target="#qrModal{{ $student->id }}" 
                                             class="cursor-pointer">
                                    @else
                                        <button class="btn btn-outline-warning btn-sm" onclick="generateQr({{ $student->id }})" title="Generate QR Code">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                    @endif
                                </td>
                                
                                <!-- Actions Column -->
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#infoModal{{ $student->id }}">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.students.edit', $student->id) }}">
                                                    <i class="fas fa-edit me-2"></i>Edit Student
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('student.id.print', $student->id) }}" target="_blank">
                                                    <i class="fas fa-print me-2"></i>Print ID
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
            @else
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-users text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <h4 class="text-muted mb-3">No students found</h4>
                    <p class="text-muted mb-4">No students match the current filters. Try adjusting your search criteria.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-1"></i>Add Student
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Student Modals -->
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
                                <div class="badge bg-primary mb-3">{{ $student->id_no ?? 'N/A' }}</div>

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
                                                    <span class="badge bg-primary">{{ $student->section->name ?? $student->section_name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 50px;">Grade:</small>
                                                    <span class="fw-semibold">{{ $student->section->gradelevel ?? $student->grade_level ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 70px;">Gender:</small>
                                                    <span class="fw-semibold">
                                                        @if($student->gender == 'M') Male
                                                        @elseif($student->gender == 'F') Female
                                                        @else {{ $student->gender ?? 'N/A' }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center py-1">
                                                    <small class="text-muted me-3" style="min-width: 50px;">Age:</small>
                                                    <span class="fw-semibold">{{ $student->age ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Information -->
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0"><i class="fas fa-school me-2"></i>Academic Information</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="d-flex align-items-start py-1">
                                                    <small class="text-muted me-3" style="min-width: 60px;">School:</small>
                                                    <span class="fw-semibold">{{ $student->school->name ?? 'San Guillermo Vocational and Industrial High School' }}</span>
                                                </div>
                                            </div>
                                            @if($student->section && $student->section->teacher)
                                                <div class="col-12">
                                                    <div class="d-flex align-items-start py-1">
                                                        <small class="text-muted me-3" style="min-width: 60px;">Teacher:</small>
                                                        <span class="fw-semibold">{{ $student->section->teacher->name }}</span>
                                                    </div>
                                                </div>
                                            @elseif($student->section && $student->section->teachers->count() > 0)
                                                <div class="col-12">
                                                    <div class="d-flex align-items-start py-1">
                                                        <small class="text-muted me-3" style="min-width: 60px;">Teachers:</small>
                                                        <div class="fw-semibold">
                                                            @foreach($student->section->teachers as $teacher)
                                                                <div>{{ $teacher->name }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($student->semester)
                                                <div class="col-12">
                                                    <div class="d-flex align-items-start py-1">
                                                        <small class="text-muted me-3" style="min-width: 60px;">Semester:</small>
                                                        <span class="fw-semibold">{{ $student->semester->name ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            @endif
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
                <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-primary btn-sm">
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

<!-- QR Code Modals -->
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
                    $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                    $modalHasQrCode = false;
                    $modalQrImagePath = '';
                    
                    if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                        $modalHasQrCode = true;
                        $modalQrImagePath = $student->qr_code;
                    } else {
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
                    <img src="{{ asset('storage/' . $modalQrImagePath) }}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                    <div class="mt-3">
                        <h6>{{ $student->name }}</h6>
                        <p class="text-muted mb-0">ID: {{ $student->id_no ?? 'N/A' }}</p>
                    </div>
                @else
                    <div class="text-muted">
                        <i class="fas fa-qrcode fa-5x mb-3"></i>
                        <h5>No QR Code Available</h5>
                        <p>QR Code has not been generated for this student yet.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                @if($modalHasQrCode)
                    <a href="{{ asset('storage/' . $modalQrImagePath) }}" download="{{ $student->name }}_QR.png" class="btn btn-outline-primary">
                        <i class="fas fa-download me-1"></i>Download QR
                    </a>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addStudentModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Student Picture Section -->
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

                        <!-- Basic Information -->
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
                        
                        <!-- Academic Information -->
                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h6 class="mb-0 text-success fw-bold">Academic Information</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="admin_school_id" class="form-label">School <span class="text-danger">*</span></label>
                            <select class="form-select" id="admin_school_id" name="school_id" required onchange="loadTeachersBySchool()">
                                <option value="">Select School</option>
                                @foreach($schools ?? [] as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="admin_teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="admin_teacher_id" name="user_id" required onchange="loadSectionsByTeacher()" disabled>
                                <option value="">Select Teacher</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="admin_section_id" class="form-label">Grade & Section <span class="text-danger">*</span></label>
                            <select class="form-select" id="admin_section_id" name="section_id" required disabled>
                                <option value="">Select Grade & Section</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="admin_semester_id" class="form-label">Semester</label>
                            <select class="form-select" id="admin_semester_id" name="semester_id">
                                <option value="">Select Semester</option>
                                @foreach($semesters ?? [] as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Personal Information -->
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
                        
                        <!-- Emergency Contact -->
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

<!-- Import Students Modal -->
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
                                    <a href="{{ route('admin.students.downloadTemplate') }}" class="btn btn-success btn-sm">
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
                                        <label for="import_file" class="form-label small">Select Excel or CSV File</label>
                                        <div class="border-2 border-dashed border-primary rounded p-3 text-center bg-light">
                                            <div class="mb-2">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                                            </div>
                                            <input type="file" name="file" id="import_file" class="form-control form-control-sm" accept=".csv, .xls, .xlsx" required>
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
                                                <li>Fill all required fields: Student ID, Name <strong>(LN, FN MI.)</strong>, Gender (M/F), Age, School ID, Teacher ID, Section ID, Emergency Contact Name, Relationship, Emergency Contact Phone</li>
                                                <li>Name format must be: <strong>Last Name, First Name Middle Initial.</strong> (example: Dela Cruz, Juan M.)</li>
                                                <li>Student IDs must be unique - duplicates will be skipped</li>
                                                <li>School ID, Teacher ID, and Section ID must match existing records in the system</li>
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
        </div>
    </div>
</div>

<!-- Camera Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cameraModalLabel">Take Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="camera-container">
                    <video id="camera-video" autoplay playsinline style="width: 100%; max-width: 400px; border-radius: 15px; border: 3px solid #007bff;"></video>
                    <canvas id="camera-canvas" style="display: none;"></canvas>
                </div>
                <div id="camera-controls" class="mt-3">
                    <button type="button" class="btn btn-primary" onclick="capturePhoto()"><i class="fa fa-camera"></i> Capture Photo</button>
                    <button type="button" class="btn btn-secondary" onclick="retakePhoto()" style="display: none;" id="retake-btn"><i class="fa fa-redo"></i> Retake</button>
                </div>
                <div id="camera-preview" class="mt-3" style="display: none;"><img id="captured-photo" src="" alt="Captured Photo" style="width: 200px; height: 200px; object-fit: cover; border-radius: 15px; border: 3px solid #28a745;"></div>
                <div id="camera-error" class="mt-3 alert alert-danger" style="display: none;"><i class="fa fa-exclamation-triangle"></i> Camera not available. Please upload a file instead.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="usePhoto()" id="use-photo-btn" style="display: none;"><i class="fa fa-check"></i> Use Photo</button>
            </div>
        </div>
    </div>
</div>

<script>
 let videoStream = null;
let cameraModal = null;

function openCameraModal() {
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
        video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } 
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

function showCameraError() { 
    document.getElementById('camera-video').style.display = 'none'; 
    document.getElementById('camera-error').style.display = 'block'; 
}

function capturePhoto() {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('camera-canvas');
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth; 
    canvas.height = video.videoHeight; 
    context.drawImage(video, 0, 0);
    const dataURL = canvas.toDataURL('image/jpeg', 0.8);
    document.getElementById('captured-photo').src = dataURL;
    document.getElementById('camera-preview').style.display = 'block';
    document.getElementById('camera-video').style.display = 'none';
    document.getElementById('retake-btn').style.display = 'inline-block';
    document.getElementById('use-photo-btn').style.display = 'inline-block';
}

function retakePhoto() { 
    document.getElementById('camera-preview').style.display = 'none'; 
    document.getElementById('camera-video').style.display = 'block'; 
    document.getElementById('retake-btn').style.display = 'none'; 
    document.getElementById('use-photo-btn').style.display = 'none'; 
}

function usePhoto() {
    const dataURL = document.getElementById('captured-photo').src;
    document.getElementById('captured_image').value = dataURL;
    showImagePreview(dataURL);
    stopCamera(); 
    cameraModal.hide();
}

function stopCamera() { 
    if (videoStream) { 
        videoStream.getTracks().forEach(track => track.stop()); 
        videoStream = null; 
    } 
}

function showImagePreview(src) { 
    const preview = document.getElementById('image-preview'); 
    const previewImg = document.getElementById('preview-img'); 
    const placeholder = document.querySelector('.student-photo-placeholder');
    
     if (placeholder) {
        placeholder.style.display = 'none';
    }
    
     previewImg.src = src; 
    preview.style.display = 'block';
}

function removePhoto() {
    const preview = document.getElementById('image-preview');
    const placeholder = document.querySelector('.student-photo-placeholder');
    const pictureInput = document.getElementById('picture');
    const capturedInput = document.getElementById('captured_image');
    
     if (pictureInput) pictureInput.value = '';
    if (capturedInput) capturedInput.value = '';
    
     preview.style.display = 'none';
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
}

 document.getElementById('picture').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) { 
            showImagePreview(e.target.result); 
            document.getElementById('captured_image').value = ''; 
        }
        reader.readAsDataURL(file);
    }
});

 document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () { 
    stopCamera(); 
});

 function loadTeachersBySchool() {
    const schoolSelect = document.getElementById('admin_school_id');
    const teacherSelect = document.getElementById('admin_teacher_id');
    const sectionSelect = document.getElementById('admin_section_id');
    
    const schoolId = schoolSelect.value;
    
     teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
    sectionSelect.innerHTML = '<option value="">Select Grade & Section</option>';
    teacherSelect.disabled = !schoolId;
    sectionSelect.disabled = true;
    
    if (schoolId) {
        // Fetch teachers for this school
        fetch(`/admin/schools/${schoolId}/teachers`)
            .then(response => response.json())
            .then(teachers => {
                teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.name;
                    teacherSelect.appendChild(option);
                });
                teacherSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading teachers:', error);
                teacherSelect.disabled = false;
            });
    }
}

function loadSectionsByTeacher() {
    const teacherSelect = document.getElementById('admin_teacher_id');
    const sectionSelect = document.getElementById('admin_section_id');
    
    const teacherId = teacherSelect.value;
    
     sectionSelect.innerHTML = '<option value="">Select Grade & Section</option>';
    sectionSelect.disabled = !teacherId;
    
    if (teacherId) {
         fetch(`/admin/teachers/${teacherId}/sections`)
            .then(response => response.json())
            .then(sections => {
                sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = `Grade ${section.gradelevel} - ${section.name}`;
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.disabled = false;
            });
    }
}

let debounceTimer;

function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        document.getElementById('studentFilterForm').submit();
    }, 500);
}

function generateQr(studentId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/students/${studentId}/generate-qr`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}

function generateAllQrs() {
    if (confirm('Generate QR codes for all students? This may take some time.')) {
         const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
        button.disabled = true;
        
         const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.students.generateQrs") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<script>
 document.addEventListener('DOMContentLoaded', function() {
    const teacherSelect = document.getElementById('filterTeacher');
    const sectionSelect = document.getElementById('filterSection');
    
     const initialSectionValue = sectionSelect ? sectionSelect.value : '';

    function populateSections(preserveSelection = true) {
        if(!teacherSelect || !sectionSelect) return;
        const selected = teacherSelect.selectedOptions[0];
        const sectionsData = selected ? selected.getAttribute('data-sections') : null;
        
         let sectionToSelect = '';
        if (preserveSelection) {
             sectionToSelect = sectionSelect.value || initialSectionValue;
        }
        
         sectionSelect.innerHTML = '<option value="">All Sections</option>';
        
        if(sectionsData) {
            try {
                const sections = JSON.parse(sectionsData);
                sections.forEach(sec => {
                    const opt = document.createElement('option');
                    opt.value = sec.id;
                    opt.text = (sec.section_name || sec.name) + ' (Grade ' + (sec.gradelevel || sec.grade_level || '') + ')';
                    
                     if (sectionToSelect && sec.id == sectionToSelect) {
                        opt.selected = true;
                    }
                    
                    sectionSelect.appendChild(opt);
                });
            } catch(e) {
                console.error('Error parsing sections data:', e);
            }
        }
    }

    if(teacherSelect) {
        teacherSelect.addEventListener('change', function() {
             populateSections(false);
        });
        
         populateSections(true);
    }
});
</script>

<style>
     .sticky-card {
        position: relative;
    }
    
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
    
     .cursor-pointer {
        cursor: pointer;
    }
    
     .sticky-header {
        z-index: 1020;
    }
    
     .stats-row {
        position: relative;
        z-index: 1;
    }
    
    .stat-card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 600;
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
    }
</style>

@endsection
