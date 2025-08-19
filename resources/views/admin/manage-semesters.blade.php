@extends('admin.sidebar')
@section('title', 'Manage Semesters')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-calendar-alt me-2"></i>
                Manage Semesters
            </h4>
            <p class="subtitle fs-6 mb-0">Create and manage academic semesters for schools</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                <i class="fas fa-plus me-1"></i>Add Semester
            </button>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Semesters Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white p-2">
            <h6 class="mb-0 fs-6"><i class="fas fa-list me-1"></i>All Semesters</h6>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="py-1 fs-6">Name</th>
                            <th class="py-1 fs-6">School</th>
                            <th class="py-1 fs-6">Duration</th>
                            <th class="py-1 fs-6">Status</th>
                            <th>Time Ranges</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                        <tr>
                            <td>
                                <strong>{{ $semester->name }}</strong>
                                @if(isset($semester->weekdays))
                                    <br><small class="text-muted">{{ $semester->weekdays }} weekdays</small>
                                @endif
                            </td>
                            <td>
                                @if($semester->school)
                                    <span class="badge bg-info">{{ $semester->school->name }}</span>
                                @else
                                    <span class="badge bg-secondary">No School</span>
                                @endif
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($semester->start_date)->format('M j, Y') }}
                                <br>
                                <small class="text-muted">to {{ \Carbon\Carbon::parse($semester->end_date)->format('M j, Y') }}</small>
                            </td>
                            <td>
                                @if($semester->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <small>
                                    @if($semester->morning_period_start && $semester->morning_period_end)
                                        <div><strong>Morning:</strong> {{ \Carbon\Carbon::parse($semester->morning_period_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->morning_period_end)->format('g:i A') }}</div>
                                    @endif
                                    @if($semester->afternoon_period_start && $semester->afternoon_period_end)
                                        <div><strong>Afternoon:</strong> {{ \Carbon\Carbon::parse($semester->afternoon_period_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->afternoon_period_end)->format('g:i A') }}</div>
                                    @endif
                                    
                                    @if(!$semester->morning_period_start && !$semester->afternoon_period_start)
                                        <span class="text-muted">Time ranges not set</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editSemester({{ $semester->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteSemester({{ $semester->id }}, '{{ $semester->name }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                    <p>No semesters found. Create your first semester!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $semesters->links() }}
            </div>
        </div>
    </div>

    <!-- Sections Management -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fs-6"><i class="fas fa-layer-group me-1"></i>Sections Management</h6>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                    <i class="fas fa-plus me-1"></i>Add Section
                </button>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="py-1 fs-6">Section Name</th>
                            <th class="py-1 fs-6">Grade Level</th>
                            <th class="py-1 fs-6">Teacher</th>
                            <th class="py-1 fs-6">Semester</th>
                            <th class="py-1 fs-6">Students</th>
                            <th class="py-1 fs-6">Time Ranges</th>
                            <th class="py-1 fs-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections ?? [] as $section)
                        <tr>
                            <td class="py-1">
                                <strong>{{ $section->name }}</strong>
                            </td>
                            <td class="py-1">
                                <span class="badge bg-primary">Grade {{ $section->gradelevel }}</span>
                            </td>
                            <td class="py-1">
                                @if($section->teacher)
                                    <span class="badge bg-info">{{ $section->teacher->name }}</span>
                                @else
                                    <span class="badge bg-warning">No Teacher</span>
                                @endif
                            </td>
                            <td class="py-1">
                                @if($section->semester)
                                    <span class="badge bg-secondary">{{ $section->semester->name }}</span>
                                @else
                                    <span class="badge bg-danger">No Semester</span>
                                @endif
                            </td>
                            <td class="py-1">
                                <span class="badge bg-dark">{{ $section->students->count() }} students</span>
                            </td>
                            <td class="py-1">
                                <small>
                                    @if($section->am_time_in_start && $section->am_time_in_end)
                                        <div><strong>AM In:</strong> {{ \Carbon\Carbon::parse($section->am_time_in_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->am_time_in_end)->format('g:i A') }}</div>
                                    @endif
                                    @if($section->am_time_out_start && $section->am_time_out_end)
                                        <div><strong>AM Out:</strong> {{ \Carbon\Carbon::parse($section->am_time_out_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i A') }}</div>
                                    @endif
                                    @if($section->pm_time_in_start && $section->pm_time_in_end)
                                        <div><strong>PM In:</strong> {{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->pm_time_in_end)->format('g:i A') }}</div>
                                    @endif
                                    @if($section->pm_time_out_start && $section->pm_time_out_end)
                                        <div><strong>PM Out:</strong> {{ \Carbon\Carbon::parse($section->pm_time_out_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i A') }}</div>
                                    @endif
                                    
                                    @if(!$section->am_time_in_start && !$section->am_time_out_start && !$section->pm_time_in_start && !$section->pm_time_out_start)
                                        <span class="text-muted">No time ranges</span>
                                    @endif
                                </small>
                            </td>
                            <td class="py-1">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editSection({{ $section->id }})"
                                            title="Edit Section">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteSection({{ $section->id }}, '{{ $section->name }}')"
                                            title="Delete Section">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-layer-group fa-3x mb-3"></i>
                                    <p>No sections found. Create your first section!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Semester Modal -->
<div class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSemesterModalLabel">Add New Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.semester.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Semester Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school_id" class="form-label">School</label>
                                <select class="form-control" id="school_id" name="school_id" required>
                                    <option value="">Select School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Period Times (Optional)</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="morning_period_start" class="form-label">Morning Period Start</label>
                                <input type="time" class="form-control" id="morning_period_start" name="morning_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="morning_period_end" class="form-label">Morning Period End</label>
                                <input type="time" class="form-control" id="morning_period_end" name="morning_period_end">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="afternoon_period_start" class="form-label">Afternoon Period Start</label>
                                <input type="time" class="form-control" id="afternoon_period_start" name="afternoon_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="afternoon_period_end" class="form-label">Afternoon Period End</label>
                                <input type="time" class="form-control" id="afternoon_period_end" name="afternoon_period_end">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Semester Modal -->
<div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSemesterModalLabel">Edit Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSemesterForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Semester Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_school_id" class="form-label">School</label>
                                <select class="form-control" id="edit_school_id" name="school_id" required>
                                    <option value="">Select School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Period Times (Optional)</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_morning_period_start" class="form-label">Morning Period Start</label>
                                <input type="time" class="form-control" id="edit_morning_period_start" name="morning_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_morning_period_end" class="form-label">Morning Period End</label>
                                <input type="time" class="form-control" id="edit_morning_period_end" name="morning_period_end">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_afternoon_period_start" class="form-label">Afternoon Period Start</label>
                                <input type="time" class="form-control" id="edit_afternoon_period_start" name="afternoon_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_afternoon_period_end" class="form-label">Afternoon Period End</label>
                                <input type="time" class="form-control" id="edit_afternoon_period_end" name="afternoon_period_end">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSemesterModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the semester "<span id="deleteSemesterName"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will affect all associated data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSemesterForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Semester</button>
                </form>
            </div>
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
            <form method="POST" action="{{ route('admin.section.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="section_name" name="name" required placeholder="e.g., STEM, HUMSS, ICT">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="section_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="section_gradelevel" name="gradelevel" required>
                                <option value="">Select Grade Level</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="section_teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-control" id="section_teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="section_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="section_semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Attendance Time Ranges (Optional)</h6>
                    
                    <!-- Morning Sessions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="am_time_in_start" class="form-label">AM Time-In Start</label>
                            <input type="time" class="form-control" id="am_time_in_start" name="am_time_in_start" value="07:00">
                        </div>
                        <div class="col-md-6">
                            <label for="am_time_in_end" class="form-label">AM Time-In End</label>
                            <input type="time" class="form-control" id="am_time_in_end" name="am_time_in_end" value="07:30">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="am_time_out_start" class="form-label">AM Time-Out Start</label>
                            <input type="time" class="form-control" id="am_time_out_start" name="am_time_out_start" value="11:30">
                        </div>
                        <div class="col-md-6">
                            <label for="am_time_out_end" class="form-label">AM Time-Out End</label>
                            <input type="time" class="form-control" id="am_time_out_end" name="am_time_out_end" value="12:00">
                        </div>
                    </div>
                    
                    <!-- Afternoon Sessions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="pm_time_in_start" class="form-label">PM Time-In Start</label>
                            <input type="time" class="form-control" id="pm_time_in_start" name="pm_time_in_start" value="13:00">
                        </div>
                        <div class="col-md-6">
                            <label for="pm_time_in_end" class="form-label">PM Time-In End</label>
                            <input type="time" class="form-control" id="pm_time_in_end" name="pm_time_in_end" value="13:30">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="pm_time_out_start" class="form-label">PM Time-Out Start</label>
                            <input type="time" class="form-control" id="pm_time_out_start" name="pm_time_out_start" value="16:30">
                        </div>
                        <div class="col-md-6">
                            <label for="pm_time_out_end" class="form-label">PM Time-Out End</label>
                            <input type="time" class="form-control" id="pm_time_out_end" name="pm_time_out_end" value="17:00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Create Section
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
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editSectionModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editSectionForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_section_name" name="name" required placeholder="e.g., STEM, HUMSS, ICT">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_section_gradelevel" name="gradelevel" required>
                                <option value="">Select Grade Level</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_section_teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_section_semester_id" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_section_semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Attendance Time Ranges (Optional)</h6>
                    
                    <!-- Morning Sessions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_am_time_in_start" class="form-label">AM Time-In Start</label>
                            <input type="time" class="form-control" id="edit_am_time_in_start" name="am_time_in_start">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_am_time_in_end" class="form-label">AM Time-In End</label>
                            <input type="time" class="form-control" id="edit_am_time_in_end" name="am_time_in_end">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_am_time_out_start" class="form-label">AM Time-Out Start</label>
                            <input type="time" class="form-control" id="edit_am_time_out_start" name="am_time_out_start">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_am_time_out_end" class="form-label">AM Time-Out End</label>
                            <input type="time" class="form-control" id="edit_am_time_out_end" name="am_time_out_end">
                        </div>
                    </div>
                    
                    <!-- Afternoon Sessions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_pm_time_in_start" class="form-label">PM Time-In Start</label>
                            <input type="time" class="form-control" id="edit_pm_time_in_start" name="pm_time_in_start">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_pm_time_in_end" class="form-label">PM Time-In End</label>
                            <input type="time" class="form-control" id="edit_pm_time_in_end" name="pm_time_in_end">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="edit_pm_time_out_start" class="form-label">PM Time-Out Start</label>
                            <input type="time" class="form-control" id="edit_pm_time_out_start" name="pm_time_out_start">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_pm_time_out_end" class="form-label">PM Time-Out End</label>
                            <input type="time" class="form-control" id="edit_pm_time_out_end" name="pm_time_out_end">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
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
                <h5 class="modal-title" id="deleteSectionModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the section "<span id="deleteSectionName"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will remove all students from this section.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSectionForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Section</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editSemester(semesterId) {
    fetch(`/admin/semesters/${semesterId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_start_date').value = data.start_date || '';
            document.getElementById('edit_end_date').value = data.end_date || '';
            document.getElementById('edit_school_id').value = data.school_id || '';
            document.getElementById('edit_morning_period_start').value = data.morning_period_start || '';
            document.getElementById('edit_morning_period_end').value = data.morning_period_end || '';
            document.getElementById('edit_afternoon_period_start').value = data.afternoon_period_start || '';
            document.getElementById('edit_afternoon_period_end').value = data.afternoon_period_end || '';
            
            document.getElementById('editSemesterForm').action = '/admin/semesters/' + semesterId;
            
            new bootstrap.Modal(document.getElementById('editSemesterModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading semester data');
        });
}

function deleteSemester(id, name) {
    try {
        document.getElementById('deleteSemesterName').textContent = name || 'this semester';
        document.getElementById('deleteSemesterForm').action = '/admin/semesters/' + id;
        
        new bootstrap.Modal(document.getElementById('deleteSemesterModal')).show();
    } catch (error) {
        console.error('Error opening delete modal:', error);
        alert('Error opening delete confirmation. Please refresh the page and try again.');
    }
}

// Section management functions
function editSection(sectionId) {
    fetch(`/admin/sections/${sectionId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_section_name').value = data.name || '';
            document.getElementById('edit_section_gradelevel').value = data.gradelevel || '';
            document.getElementById('edit_section_teacher_id').value = data.teacher_id || '';
            document.getElementById('edit_section_semester_id').value = data.semester_id || '';
            document.getElementById('edit_am_time_in_start').value = data.am_time_in_start || '';
            document.getElementById('edit_am_time_in_end').value = data.am_time_in_end || '';
            document.getElementById('edit_am_time_out_start').value = data.am_time_out_start || '';
            document.getElementById('edit_am_time_out_end').value = data.am_time_out_end || '';
            document.getElementById('edit_pm_time_in_start').value = data.pm_time_in_start || '';
            document.getElementById('edit_pm_time_in_end').value = data.pm_time_in_end || '';
            document.getElementById('edit_pm_time_out_start').value = data.pm_time_out_start || '';
            document.getElementById('edit_pm_time_out_end').value = data.pm_time_out_end || '';
            
            document.getElementById('editSectionForm').action = '/admin/sections/' + sectionId;
            
            new bootstrap.Modal(document.getElementById('editSectionModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading section data');
        });
}

function deleteSection(id, name) {
    try {
        document.getElementById('deleteSectionName').textContent = name || 'this section';
        document.getElementById('deleteSectionForm').action = '/admin/sections/' + id;
        
        new bootstrap.Modal(document.getElementById('deleteSectionModal')).show();
    } catch (error) {
        console.error('Error opening delete modal:', error);
        alert('Error opening delete confirmation. Please refresh the page and try again.');
    }
}
</script>

@endsection
