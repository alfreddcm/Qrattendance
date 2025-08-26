@extends('admin.sidebar')
@section('title', 'Manage Teachers')
@section('content')

<div class="sticky-header" >
    <div class="d-flex justify-content-between align-items-center" style="margin-left: 1rem;" >
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-chalkboard-teacher me-2"></i>
                Manage Teachers
            </h4>
            <p class="subtitle fs-6 mb-0">Add, edit, and manage teacher accounts</p>
        </div>
        
    </div>
</div>

    <div class="container-fluid py-2">
        @include('partials.alerts')

    <!-- Statistics Dashboard (compact) -->
    <div class="row mb-3 gx-3">
        <div class="col-md-3 col-6">
            <div class="card stats-card primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Teachers</div>
                        <div class="stat-value text-primary">{{ $stats['total_teachers'] }}</div>
                    </div>
                    <i class="fas fa-chalkboard-teacher fa-2x text-primary opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stats-card success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">With Sections</div>
                        <div class="stat-value text-success">{{ $stats['teachers_with_sections'] }}</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stats-card warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Without Sections</div>
                        <div class="stat-value text-warning">{{ $stats['teachers_without_sections'] }}</div>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x text-warning opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stats-card info h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Total Sections</div>
                        <div class="stat-value text-info">{{ $stats['total_sections'] }}</div>
                    </div>
                    <i class="fas fa-layer-group fa-2x text-info opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sections Management Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-layer-group me-2"></i>
                    Sections Management
                </h6>
                <div>
                    <span class="badge bg-light text-dark me-2">{{ isset($sections) ? $sections->count() : 0 }} sections</span>
                    <button type="button" class="btn btn-light btn-sm btn-action" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                        <i class="fas fa-plus me-1"></i>Add Section
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                @if(isset($sections) && $sections->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-compact mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Section</th>
                                    <th>Teacher</th>
                                    <th>Semester</th>
                                    <th>Students</th>
                                    <th>Time Ranges</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sections as $section)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong>{{ $section->name }}</strong>
                                            <small class="text-muted">Grade {{ $section->gradelevel }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($section->teacher)
                                            <span class="badge bg-primary">{{ $section->teacher->name }}</span>
                                        @else
                                            <span class="badge bg-warning">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($section->semester)
                                            <span class="badge bg-info">{{ $section->semester->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $section->students->count() }} students</span>
                                    </td>
                                    <td>
                                        @if($section->am_time_in_start && $section->am_time_out_end)
                                            <div class="small">
                                                <strong>AM:</strong> {{ \Carbon\Carbon::parse($section->am_time_in_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i A') }}<br>
                                                @if($section->pm_time_in_start && $section->pm_time_out_end)
                                                    <strong>PM:</strong> {{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i A') }}
                                                @endif
                                            </div>
                                        @else
                                            <small class="text-muted">Not set</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm" 
                                                    onclick="editSection({{ $section->id }})"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteSection({{ $section->id }}, '{{ $section->name }}')"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Sections Found</h5>
                        <p class="text-muted">Add sections to organize your students and manage attendance schedules.</p>
                        <button type="button" class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                            <i class="fas fa-plus me-2"></i>Add First Section
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

    <!-- Teachers List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Teachers List
                    </h6>
                    <div>
                        <span class="badge bg-light text-dark me-2">{{ $teachers->total() }} teachers</span>
                        <button type="button" class="btn btn-light btn-sm btn-action" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-plus me-1"></i>Add Teacher
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($teachers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-compact mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable" data-sort="name">
                                            <a href="{{ route('admin.manage-teachers') }}?sort_by=name&sort_order={{ request('sort_by') == 'name' && request('sort_order') == 'asc' ? 'desc' : 'asc' }}" class="text-decoration-none text-dark">
                                                Name
                                                @if(request('sort_by') == 'name')
                                                    @if(request('sort_order') == 'asc')
                                                        <i class="fas fa-sort-up ms-1"></i>
                                                    @else
                                                        <i class="fas fa-sort-down ms-1"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort ms-1 text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Username</th>
                                        <th>School</th>
                                        <th>Position</th>
                                        <th>Section & Students</th>
                                        <th class="sortable" data-sort="phone_number">
                                            <a href="{{ route('admin.manage-teachers') }}?sort_by=phone_number&sort_order={{ request('sort_by') == 'phone_number' && request('sort_order') == 'asc' ? 'desc' : 'asc' }}" class="text-decoration-none text-dark">
                                                Phone Number
                                                @if(request('sort_by') == 'phone_number')
                                                    @if(request('sort_order') == 'asc')
                                                        <i class="fas fa-sort-up ms-1"></i>
                                                    @else
                                                        <i class="fas fa-sort-down ms-1"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort ms-1 text-muted"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teachers as $teacher)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>{{ $teacher->name }}</strong>
                                                <small class="text-muted">{{ $teacher->email }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $teacher->username }}</span>
                                        </td>
                                        <td>
                                            @if($teacher->school)
                                                <span class="badge bg-success">{{ Str::limit($teacher->school->name, 15) }}</span>
                                            @else
                                                <span class="badge bg-warning">No School</span>
                                            @endif
                                        </td>
                                        <td>{{ $teacher->position ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $allSections = collect();
                                                // Add legacy single section if exists
                                                if($teacher->section) {
                                                    $allSections->push($teacher->section);
                                                }
                                                // Add sections from pivot table
                                                $allSections = $allSections->merge($teacher->sections);
                                                $allSections = $allSections->unique('id');
                                            @endphp
                                            
                                            @if($allSections->count() > 0)
                                                <div class="d-flex flex-column">
                                                    @foreach($allSections as $section)
                                                        <div class="mb-1">
                                                            <span class="fw-bold">{{ $section->section_name ?? $section->name }} - Grade {{ $section->grade_level ?? $section->gradelevel }}</span>
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-users me-1"></i>
                                                                {{ $section->students ? $section->students->count() : 0 }} students
                                                            </small>
                                                        </div>
                                                    @endforeach
                                                    @if($allSections->count() > 1)
                                                        <span class="badge bg-info">{{ $allSections->count() }} Sections</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Not Assigned</span>
                                            @endif
                                        </td>
                                        <td>{{ $teacher->phone_number ?? 'N/A' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTeacherModal"
                                                        onclick="editTeacher({{ $teacher->id }})"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                 
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm" 
                                                        onclick="if(confirm('Are you sure you want to delete this teacher?')) { document.getElementById('delete-form-{{ $teacher->id }}').submit(); }"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <form id="delete-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.delete-teacher', $teacher->id) }}" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="card-footer">
                            {{ $teachers->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No teachers found</h5>
                            <p class="text-muted">Start by adding your first teacher to the system.</p>
                            <button type="button" class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                                <i class="fas fa-plus me-2"></i>Add First Teacher
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addTeacherModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.store-teacher') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required 
                                   value="{{ old('name') }}"
                                   minlength="2" maxlength="255" 
                                   title="Name should contain only letters, spaces, and periods"
                                   placeholder="Enter full name">
                        </div>
                        
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label for="add_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_username" name="username" required 
                                   value="{{ old('username') }}"
                                   minlength="3" maxlength="50" 
                                    
                                   title="Username should contain only letters, numbers, underscores, and hyphens"
                                   placeholder="Enter username">
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="add_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="add_email" name="email" required 
                                   value="{{ old('email') }}"
                                   maxlength="255" 
                                   title="Enter a valid email address"
                                   placeholder="user@example.com">
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="add_password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="add_password" name="password" required 
                                   minlength="6" maxlength="100" 
                                   title="Password must be at least 6 characters long"
                                   placeholder="Enter password">
                        </div>
                        
                        <!-- School -->
                        <div class="col-md-6 mb-3">
                            <label for="add_school_id" class="form-label">School <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_school_id" name="school_id" required 
                                    title="Select the school this teacher will be assigned to">
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Position -->
                        <div class="col-md-6 mb-3">
                            <label for="add_position" class="form-label">Position</label>
                            <select class="form-select" id="add_position" name="position" title="Select position">
                                <option value="">Select Position</option>
                                <option value="Teacher" {{ old('position') == 'Teacher' ? 'selected' : '' }}>Teacher</option>
                                <option value="Adviser" {{ old('position') == 'Adviser' ? 'selected' : '' }}>Adviser</option>
                            </select>
                        </div>
                        
                        <!-- Section Selection -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Section Assignment</label>
                            @if($sections->count() > 0)
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="section_id" id="add_no_section" value="" checked>
                                        <label class="form-check-label text-muted" for="add_no_section">
                                            <i class="fas fa-minus-circle me-1"></i>No Section Assignment
                                        </label>
                                    </div>
                                    @foreach($sections as $section)
                                        @php
                                            $isAssigned = $section->teacher_id !== null;
                                            $assignedTeacher = $isAssigned ? \App\Models\User::find($section->teacher_id) : null;
                                        @endphp
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="section_id" 
                                                   id="add_section_{{ $section->id }}" 
                                                   value="{{ $section->id }}"
                                                   @if($isAssigned) disabled @endif>
                                            <label class="form-check-label @if($isAssigned) text-muted @endif" for="add_section_{{ $section->id }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $section->name }}</strong> - Grade {{ $section->gradelevel }}
                                                        <br><small class="text-muted">{{ $section->students->count() ?? 0 }} students</small>
                                                    </div>
                                                    @if($isAssigned)
                                                        <div class="text-end">
                                                            <span class="badge bg-warning">Already Assigned</span>
                                                            <br><small class="text-muted">Teacher: {{ $assignedTeacher->name ?? 'Unknown' }}</small>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-success">Available</span>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Only unassigned sections can be selected. Assigned sections show current teacher.
                                </small>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please add sections first before assigning teachers.
                                </div>
                            @endif
                        </div>
                        
                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <label for="add_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="add_phone_number" name="phone_number" 
                                   value="{{ old('phone_number') }}"
                                   placeholder="e.g., +1234567890" pattern="[\+]?[0-9\s\-\(\)]+" 
                                   title="Enter a valid phone number (digits, spaces, dashes, parentheses, and + allowed)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Add Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addSectionModalLabel">
                    <i class="fas fa-layer-group me-2"></i>Add New Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSectionForm" method="POST" action="{{ route('admin.section.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Section Name -->
                        <div class="col-md-6 mb-3">
                            <label for="add_section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_section_name" name="name" required 
                                   minlength="1" maxlength="100" 
                                   pattern="[A-Za-z0-9\s\-]+" 
                                   title="Section name should contain only letters, numbers, spaces, and hyphens"
                                   placeholder="e.g., Einstein, Grade 7-A">
                        </div>
                        
                        <!-- Grade Level -->
                        <div class="col-md-6 mb-3">
                            <label for="add_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_gradelevel" name="gradelevel" required 
                                    title="Select a grade level">
                                <option value="">Select Grade Level</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                        
                        <!-- Semester -->
                        <div class="col-md-6 mb-3">
                            <label for="add_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Teacher -->
                        <div class="col-md-6 mb-3">
                            <label for="add_teacher_id" class="form-label">Assign Teacher</label>
                            <select class="form-select" id="add_teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- AM Time Range -->
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Morning Schedule</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="add_am_time_in_start" class="form-label">AM Time In Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_am_time_in_start" name="am_time_in_start" value="07:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_am_time_in_end" class="form-label">AM Time In End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_am_time_in_end" name="am_time_in_end" value="08:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_am_time_out_start" class="form-label">AM Time Out Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_am_time_out_start" name="am_time_out_start" value="11:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_am_time_out_end" class="form-label">AM Time Out End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_am_time_out_end" name="am_time_out_end" value="12:00" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PM Time Range -->
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Afternoon Schedule</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="add_pm_time_in_start" class="form-label">PM Time In Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_pm_time_in_start" name="pm_time_in_start" value="13:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_pm_time_in_end" class="form-label">PM Time In End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_pm_time_in_end" name="pm_time_in_end" value="14:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_pm_time_out_start" class="form-label">PM Time Out Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_pm_time_out_start" name="pm_time_out_start" value="16:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="add_pm_time_out_end" class="form-label">PM Time Out End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="add_pm_time_out_end" name="pm_time_out_end" value="17:00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Add Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSectionModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSectionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <!-- Section Name -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_section_name" name="name" required 
                                   minlength="1" maxlength="100" 
                                   pattern="[A-Za-z0-9\s\-]+" 
                                   title="Section name should contain only letters, numbers, spaces, and hyphens"
                                   placeholder="e.g., Einstein, Grade 7-A">
                        </div>
                        
                        <!-- Grade Level -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_gradelevel" name="gradelevel" required 
                                    title="Select a grade level from 1 to 12">
                                <option value="">Select Grade Level</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">Grade {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Semester -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Teacher -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_teacher_id" class="form-label">Assign Teacher</label>
                            <select class="form-select" id="edit_teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- AM Time Range -->
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Morning Schedule</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_am_time_in_start" class="form-label">AM Time In Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_am_time_in_start" name="am_time_in_start" value="07:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_am_time_in_end" class="form-label">AM Time In End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_am_time_in_end" name="am_time_in_end" value="08:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_am_time_out_start" class="form-label">AM Time Out Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_am_time_out_start" name="am_time_out_start" value="11:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_am_time_out_end" class="form-label">AM Time Out End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_am_time_out_end" name="am_time_out_end" value="12:00" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PM Time Range -->
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Afternoon Schedule</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_pm_time_in_start" class="form-label">PM Time In Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_pm_time_in_start" name="pm_time_in_start" value="13:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_pm_time_in_end" class="form-label">PM Time In End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_pm_time_in_end" name="pm_time_in_end" value="14:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_pm_time_out_start" class="form-label">PM Time Out Start <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_pm_time_out_start" name="pm_time_out_start" value="16:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_pm_time_out_end" class="form-label">PM Time Out End <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_pm_time_out_end" name="pm_time_out_end" value="17:00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Section Modal -->
<div class="modal fade" id="deleteSectionModal" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSectionModalLabel">
                    <i class="fas fa-trash me-2"></i>Delete Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete the section <strong><span id="deleteSectionName"></span></strong>?</p>
                <p class="text-muted">This will also affect all students currently assigned to this section.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteSectionForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Section
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editTeacherModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editTeacherForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required 
                                   minlength="2" maxlength="255" 
                                   pattern="[A-Za-z\s\.]+" 
                                   title="Name should contain only letters, spaces, and periods"
                                   placeholder="Enter full name">
                        </div>
                        
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_username" name="username" required 
                                   minlength="3" maxlength="50" 
                                    
                                   title="Username should contain only letters, numbers, underscores, and hyphens"
                                   placeholder="Enter username">
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required 
                                   maxlength="255" 
                                   title="Enter a valid email address"
                                   placeholder="user@example.com">
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" 
                                   minlength="6" maxlength="100" 
                                   title="Password must be at least 6 characters long (leave blank to keep current)"
                                   placeholder="Leave blank to keep current password">
                        </div>
                        
                        <!-- School -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_school_id" class="form-label">School <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_school_id" name="school_id" required 
                                    title="Select the school this teacher will be assigned to">
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Position -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_position" class="form-label">Position</label>
                            <select class="form-select" id="edit_position" name="position" title="Select position">
                                <option value="">Select Position</option>
                                <option value="Teacher">Teacher</option>
                                <option value="Adviser">Adviser</option>
                            </select>
                        </div>
                        
                        <!-- Section Selection -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Section Assignment</label>
                            @if($sections->count() > 0)
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($sections as $section)
                                        @php
                                            $isAssigned = $section->teacher_id !== null;
                                            $assignedTeacher = $isAssigned ? \App\Models\User::find($section->teacher_id) : null;
                                        @endphp
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="section_ids[]" 
                                                   id="edit_section_{{ $section->id }}" 
                                                   value="{{ $section->id }}"
                                                   data-assigned="{{ $isAssigned ? 'true' : 'false' }}"
                                                   data-teacher-id="{{ $section->teacher_id }}"
                                                   @if($isAssigned) disabled @endif>
                                            <label class="form-check-label @if($isAssigned) text-muted @endif" for="edit_section_{{ $section->id }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $section->name }}</strong> - Grade {{ $section->gradelevel }}
                                                        <br><small class="text-muted">{{ $section->students->count() ?? 0 }} students</small>
                                                    </div>
                                                    @if($isAssigned)
                                                        <div class="text-end">
                                                            <span class="badge bg-warning">Already Assigned</span>
                                                            <br><small class="text-muted">Teacher: {{ $assignedTeacher->name ?? 'Unknown' }}</small>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-success">Available</span>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Select multiple sections to assign to this teacher. Only unassigned sections or sections currently assigned to this teacher can be selected.
                                </small>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please add sections first before assigning teachers.
                                </div>
                            @endif
                        </div>
     
                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone_number" name="phone_number" 
                                   placeholder="e.g., +1234567890" pattern="[\+]?[0-9\s\-\(\)]+" 
                                   title="Enter a valid phone number (digits, spaces, dashes, parentheses, and + allowed)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reassign Section Modal -->
<div class="modal fade" id="reassignSectionModal" tabindex="-1" aria-labelledby="reassignSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="reassignSectionModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Reassign Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.reassign-section') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Note:</strong> Reassigning a section will transfer all students to the new teacher.
                    </div>
                    
                    <input type="hidden" id="reassign_section_id" name="section_id">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Current Assignment:</strong></label>
                        <div class="card bg-light">
                            <div class="card-body p-2 small">
                                <div><strong>Section:</strong> <span id="current_section_name"></span></div>
                                <div><strong>Current Teacher:</strong> <span id="current_teacher_name"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reassign_new_teacher_id" class="form-label"><strong>New Teacher:</strong></label>
                        <select class="form-select" id="reassign_new_teacher_id" name="new_teacher_id" required>
                            <option value="">Select New Teacher</option>
                            @php
                                $allTeachers = \App\Models\User::where('role', 'teacher')->get();
                            @endphp
                            @foreach($allTeachers as $teacher)
                                <option value="{{ $teacher->id }}" 
                                        @if($teacher->section_id) data-has-section="true" data-section-name="{{ $teacher->section->name ?? 'Unknown' }}" @endif>
                                    {{ $teacher->name }} ({{ $teacher->username }})
                                    @if($teacher->section_id) - Currently assigned to: {{ $teacher->section->name ?? 'Unknown Section' }} @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Teachers with existing sections will require section transfer.</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="transfer_students" name="transfer_students" value="1" checked>
                        <label class="form-check-label" for="transfer_students">
                            Transfer all students in this section to the new teacher
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="fas fa-exchange-alt me-1"></i>Reassign Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Section Modal -->
<div class="modal fade" id="createSectionModal" tabindex="-1" aria-labelledby="createSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createSectionModalLabel">
                    <i class="fas fa-plus me-2"></i>Create New Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.create-section-for-teacher') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="create_teacher_id" name="teacher_id">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Teacher:</strong></label>
                        <div class="card bg-light">
                            <div class="card-body p-2 small">
                                <span id="create_teacher_name"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_gradelevel" name="gradelevel" required>
                                <option value="">Select Grade Level</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                            <label for="create_grade_level" class="form-label"><strong>Grade Level:</strong></label>
                            <select class="form-select" id="create_grade_level" name="grade_level" required>
                                <option value="">Select Grade</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">Grade {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="create_semester_id" class="form-label"><strong>Semester:</strong></label>
                        <select class="form-select" id="create_semester_id" name="semester_id" required>
                            <option value="">Select Semester</option>
                            @php
                                $semesters = \App\Models\Semester::all();
                            @endphp
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" @if($semester->is_active) selected @endif>
                                    {{ $semester->semester_name }} ({{ $semester->start_date }} - {{ $semester->end_date }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Page-specific styles only */
    .sortable a {
        color: inherit;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .sortable a:hover {
        color: #007bff;
        text-decoration: none;
    }
    
    .sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
    }
    
    .sortable:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
</style>

<script>
const teachers = @json($teachers->items());

function editTeacher(teacherId) {
    const teacher = teachers.find(t => t.id === teacherId);
    if (!teacher) return;
    
    // Set form action
    document.getElementById('editTeacherForm').action = `/admin/teachers/${teacherId}`;
    
    // Fill form fields
    document.getElementById('edit_name').value = teacher.name || '';
    document.getElementById('edit_username').value = teacher.username || '';
    document.getElementById('edit_email').value = teacher.email || '';
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_school_id').value = teacher.school_id || '';
    document.getElementById('edit_position').value = teacher.position || '';
    document.getElementById('edit_phone_number').value = teacher.phone_number || '';
    
     const currentTeacherId = teacher.id;
    const teacherSections = teacher.sections || [];  
     
    document.querySelectorAll('input[name="section_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.disabled = false;
        
         if (checkbox.dataset.teacherId == currentTeacherId) {
            checkbox.disabled = false;
            checkbox.parentElement.querySelector('label').classList.remove('text-muted');
            const badge = checkbox.parentElement.querySelector('.badge');
            if (badge) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Available';
            }
        } 
        // Disable sections assigned to other teachers
        else if (checkbox.dataset.assigned === 'true' && checkbox.dataset.teacherId != currentTeacherId) {
            checkbox.disabled = true;
            checkbox.parentElement.querySelector('label').classList.add('text-muted');
        }
    });
    
    // Check sections that are currently assigned to this teacher
    if (Array.isArray(teacherSections)) {
        teacherSections.forEach(section => {
            const sectionCheckbox = document.getElementById(`edit_section_${section.id}`);
            if (sectionCheckbox) {
                sectionCheckbox.checked = true;
            }
        });
    } else if (teacher.section_id) {
        // Fallback for single section assignment
        const currentSectionCheckbox = document.getElementById(`edit_section_${teacher.section_id}`);
        if (currentSectionCheckbox) {
            currentSectionCheckbox.checked = true;
        }
    }
}

function openReassignModal(sectionId, sectionName, currentTeacherId, currentTeacherName) {
    document.getElementById('reassign_section_id').value = sectionId;
    document.getElementById('current_section_name').textContent = sectionName;
    document.getElementById('current_teacher_name').textContent = currentTeacherName;
    
    // Remove current teacher from dropdown options
    const newTeacherSelect = document.getElementById('reassign_new_teacher_id');
    Array.from(newTeacherSelect.options).forEach(option => {
        if (option.value == currentTeacherId) {
            option.style.display = 'none';
        } else {
            option.style.display = 'block';
        }
    });
}

function openCreateSectionModal(teacherId, teacherName) {
    document.getElementById('create_teacher_id').value = teacherId;
    document.getElementById('create_teacher_name').textContent = teacherName;
    
    // Reset form fields
    document.getElementById('create_section_name').value = '';
    document.getElementById('create_grade_level').value = '';
}

function showUnassignedTeachers() {
    // Highlight teachers without sections
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        const sectionCell = row.cells[4]; // Section & Students column
        if (sectionCell && sectionCell.textContent.includes('Not Assigned')) {
            row.style.backgroundColor = '#fff3cd';
            row.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });
    
    setTimeout(() => {
        tableRows.forEach(row => {
            row.style.backgroundColor = '';
        });
    }, 3000);
}

function showUnassignedSections() {
    alert('Check sections that appear without teachers in the section management area.');
}

// Add event listeners for school selection in modals
document.addEventListener('DOMContentLoaded', function() {
    // Handle school selection in Add Teacher modal
    const addSchoolSelect = document.getElementById('school_id');
    const addSectionContainer = document.getElementById('section_selection_container');
    
    if (addSchoolSelect && addSectionContainer) {
        addSchoolSelect.addEventListener('change', function() {
            loadSectionsForModal(this.value, addSectionContainer, 'add');
        });
    }
    
    // Handle school selection in Edit Teacher modal  
    const editSchoolSelect = document.getElementById('edit_school_id');
    const editSectionContainer = document.getElementById('edit_section_selection_container');
    
    if (editSchoolSelect && editSectionContainer) {
        editSchoolSelect.addEventListener('change', function() {
            loadSectionsForModal(this.value, editSectionContainer, 'edit');
        });
    }
});

function loadSectionsForModal(schoolId, container, modalType) {
    if (!schoolId) {
        container.innerHTML = `
            <div class="form-check">
                <input class="form-check-input" type="radio" name="section_id" value="" id="${modalType}_no_section" checked>
                <label class="form-check-label" for="${modalType}_no_section">
                    No Section Assigned
                </label>
            </div>
        `;
        return;
    }

    fetch(`/admin/schools/${schoolId}/sections`)
        .then(response => response.json())
        .then(sections => {
            let html = `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="section_id" value="" id="${modalType}_no_section" checked>
                    <label class="form-check-label" for="${modalType}_no_section">
                        No Section Assigned
                    </label>
                </div>
            `;

            sections.forEach(section => {
                const isAssigned = section.teacher_id && section.teacher_id !== null;
                const teacherName = section.teacher ? section.teacher.name : '';
                
                html += `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="section_id" 
                               value="${section.id}" 
                               id="${modalType}_section_${section.id}"
                               data-assigned="${isAssigned}"
                               data-teacher-id="${section.teacher_id || ''}"
                               ${isAssigned ? 'disabled' : ''}>
                        <label class="form-check-label ${isAssigned ? 'text-muted' : ''}" 
                               for="${modalType}_section_${section.id}">
                            ${section.name} - Grade ${section.gradelevel}
                            ${teacherName ? `<small class="text-muted d-block">Teacher: ${teacherName}</small>` : ''}
                        </label>
                        <span class="badge ${isAssigned ? 'bg-warning' : 'bg-success'} ms-2">
                            ${isAssigned ? 'Already Assigned' : 'Available'}
                        </span>
                    </div>
                `;
            });

            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            container.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="section_id" value="" id="${modalType}_no_section" checked>
                    <label class="form-check-label" for="${modalType}_no_section">
                        No Section Assigned
                    </label>
                </div>
                <div class="text-danger">Error loading sections</div>
            `;
        });
}

// Section Management Functions
const sections = @json($sections ?? []);

function editSection(sectionId) {
    const section = sections.find(s => s.id === sectionId);
    if (!section) return;
    
    // Set form action
    document.getElementById('editSectionForm').action = `/admin/sections/${sectionId}`;
    
    // Fill form fields with fallback default values
    document.getElementById('edit_section_name').value = section.name || '';
    document.getElementById('edit_gradelevel').value = section.gradelevel || '';
    document.getElementById('edit_semester_id').value = section.semester_id || '';
    document.getElementById('edit_teacher_id').value = section.teacher_id || '';
    
    // Time fields with proper format fallbacks
    document.getElementById('edit_am_time_in_start').value = section.am_time_in_start || '07:00';
    document.getElementById('edit_am_time_in_end').value = section.am_time_in_end || '08:00';
    document.getElementById('edit_am_time_out_start').value = section.am_time_out_start || '11:00';
    document.getElementById('edit_am_time_out_end').value = section.am_time_out_end || '12:00';
    document.getElementById('edit_pm_time_in_start').value = section.pm_time_in_start || '13:00';
    document.getElementById('edit_pm_time_in_end').value = section.pm_time_in_end || '14:00';
    document.getElementById('edit_pm_time_out_start').value = section.pm_time_out_start || '16:00';
    document.getElementById('edit_pm_time_out_end').value = section.pm_time_out_end || '17:00';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editSectionModal')).show();
}

function deleteSection(sectionId, sectionName) {
    // Set form action
    document.getElementById('deleteSectionForm').action = `/admin/sections/${sectionId}`;
    
    // Set section name in modal
    document.getElementById('deleteSectionName').textContent = sectionName;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('deleteSectionModal')).show();
}

function validateSectionTimes() {
    const amTimeInStart = document.getElementById('add_am_time_in_start').value;
    const amTimeInEnd = document.getElementById('add_am_time_in_end').value;
    const amTimeOutStart = document.getElementById('add_am_time_out_start').value;
    const amTimeOutEnd = document.getElementById('add_am_time_out_end').value;
    const pmTimeInStart = document.getElementById('add_pm_time_in_start').value;
    const pmTimeInEnd = document.getElementById('add_pm_time_in_end').value;
    const pmTimeOutStart = document.getElementById('add_pm_time_out_start').value;
    const pmTimeOutEnd = document.getElementById('add_pm_time_out_end').value;
    
    // All time fields are required according to controller validation
    if (!amTimeInStart || !amTimeInEnd || !amTimeOutStart || !amTimeOutEnd ||
        !pmTimeInStart || !pmTimeInEnd || !pmTimeOutStart || !pmTimeOutEnd) {
        alert('All time fields are required.');
        return false;
    }
    
    // Validate AM time sequence
    if (amTimeInStart >= amTimeInEnd) {
        alert('AM Time In Start must be before AM Time In End.');
        return false;
    }
    if (amTimeInEnd >= amTimeOutStart) {
        alert('AM Time In End must be before AM Time Out Start.');
        return false;
    }
    if (amTimeOutStart >= amTimeOutEnd) {
        alert('AM Time Out Start must be before AM Time Out End.');
        return false;
    }
    
    // Validate PM time sequence
    if (pmTimeInStart >= pmTimeInEnd) {
        alert('PM Time In Start must be before PM Time In End.');
        return false;
    }
    if (pmTimeInEnd >= pmTimeOutStart) {
        alert('PM Time In End must be before PM Time Out Start.');
        return false;
    }
    if (pmTimeOutStart >= pmTimeOutEnd) {
        alert('PM Time Out Start must be before PM Time Out End.');
        return false;
    }
    
    // Validate AM and PM don't overlap
    if (amTimeOutEnd >= pmTimeInStart) {
        alert('AM Time Out End must be before PM Time In Start.');
        return false;
    }
    
    return true;
}

// Form submissions
document.getElementById('addSectionForm').addEventListener('submit', function(e) {
    if (!validateSectionTimes()) {
        e.preventDefault();
        return;
    }
    
    // Form will submit normally if validation passes
});

document.getElementById('editSectionForm').addEventListener('submit', function(e) {
    // Update validation for edit form
    const amTimeInStart = document.getElementById('edit_am_time_in_start').value;
    const amTimeInEnd = document.getElementById('edit_am_time_in_end').value;
    const amTimeOutStart = document.getElementById('edit_am_time_out_start').value;
    const amTimeOutEnd = document.getElementById('edit_am_time_out_end').value;
    const pmTimeInStart = document.getElementById('edit_pm_time_in_start').value;
    const pmTimeInEnd = document.getElementById('edit_pm_time_in_end').value;
    const pmTimeOutStart = document.getElementById('edit_pm_time_out_start').value;
    const pmTimeOutEnd = document.getElementById('edit_pm_time_out_end').value;
    
    // All time fields are required according to controller validation
    if (!amTimeInStart || !amTimeInEnd || !amTimeOutStart || !amTimeOutEnd ||
        !pmTimeInStart || !pmTimeInEnd || !pmTimeOutStart || !pmTimeOutEnd) {
        alert('All time fields are required.');
        e.preventDefault();
        return;
    }
    
    // Validate AM time sequence
    if (amTimeInStart >= amTimeInEnd) {
        alert('AM Time In Start must be before AM Time In End.');
        e.preventDefault();
        return;
    }
    if (amTimeInEnd >= amTimeOutStart) {
        alert('AM Time In End must be before AM Time Out Start.');
        e.preventDefault();
        return;
    }
    if (amTimeOutStart >= amTimeOutEnd) {
        alert('AM Time Out Start must be before AM Time Out End.');
        e.preventDefault();
        return;
    }
    
    // Validate PM time sequence
    if (pmTimeInStart >= pmTimeInEnd) {
        alert('PM Time In Start must be before PM Time In End.');
        e.preventDefault();
        return;
    }
    if (pmTimeInEnd >= pmTimeOutStart) {
        alert('PM Time In End must be before PM Time Out Start.');
        e.preventDefault();
        return;
    }
    if (pmTimeOutStart >= pmTimeOutEnd) {
        alert('PM Time Out Start must be before PM Time Out End.');
        e.preventDefault();
        return;
    }
    
    // Validate AM and PM don't overlap
    if (amTimeOutEnd >= pmTimeInStart) {
        alert('AM Time Out End must be before PM Time In Start.');
        e.preventDefault();
        return;
    }
    
    // Form will submit normally if validation passes
});

// Add Teacher Form Validation
document.addEventListener('DOMContentLoaded', function() {
    // Show modal again if there are validation errors for the add teacher form
    @if($errors->any() && old('_token'))
        const addTeacherModal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
        addTeacherModal.show();
    @endif

    const addTeacherForm = document.querySelector('#addTeacherModal form');
    if (addTeacherForm) {
        addTeacherForm.addEventListener('submit', function(e) {
            // Basic client-side validation
            const name = document.getElementById('add_name').value.trim();
            const username = document.getElementById('add_username').value.trim();
            const email = document.getElementById('add_email').value.trim();
            const password = document.getElementById('add_password').value;
            const schoolId = document.getElementById('add_school_id').value;
            
            if (!name || !username || !email || !password || !schoolId) {
                e.preventDefault();
                alert('Please fill in all required fields (Name, Username, Email, Password, and School).');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Username validation
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
            
            // Log form data for debugging (remove in production)
            console.log('Submitting teacher form with data:', {
                name: name,
                username: username,
                email: email,
                school_id: schoolId,
                position: document.getElementById('add_position').value,
                phone_number: document.getElementById('add_phone_number').value,
                section_id: document.querySelector('input[name="section_id"]:checked')?.value || ''
            });
            
            // Form will submit normally if validation passes
            return true;
        });
    }
});
</script>

@endsection
