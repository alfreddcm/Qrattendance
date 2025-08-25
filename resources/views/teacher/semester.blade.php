@extends('teacher/sidebar')
@section('title', 'Manage Semesters')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">📚</span>
                Manage Semesters
            </h4>
            <p class="subtitle fs-6 mb-0">View and edit semester information (Contact admin to create new semesters)</p>
        </div>
        
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card ">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Semester Overview
                    </h5>
                </div>
                <div class="card-body">
                    @if($semesters->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-1"></i>
                            <h5 class="text-muted">No Semesters Found</h5>
                            <p class="text-muted">Contact your administrator to create semesters for your school.</p>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Only administrators can create new semesters. You can edit existing semester details.
                            </div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th  >Semester Name</th>
                                         <th  >Start Date</th>
                                        <th  >End Date</th>
                                        <th  >Status</th>
                                        <th  >Morning Period</th>
                                        <th  >Afternoon Period</th>
                                     </tr>
                                </thead>
                                <tbody>
                                    @foreach($semesters as $semester)
                                        @php
                                            $today = \Carbon\Carbon::now();
                                            $startDate = \Carbon\Carbon::parse($semester->start_date);
                                            $endDate = \Carbon\Carbon::parse($semester->end_date);
                                            $isActive = $today->between($startDate, $endDate);
                                            $isPast = $today->greaterThan($endDate);
                                            $isFuture = $today->lessThan($startDate);
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $semester->name }}</strong>
                                                @if($isActive)
                                                    <span class="badge bg-success ms-2">Current</span>
                                                @endif
                                            </td>
                                          
                                            <td>
                                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($semester->start_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($semester->end_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                @if($semester->status === 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($semester->status === 'completed')
                                                    <span class="badge bg-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-warning">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($semester->morning_period_start && $semester->morning_period_end)
                                                        {{ \Carbon\Carbon::parse($semester->morning_period_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($semester->morning_period_end)->format('g:i A') }}
                                                    @else
                                                        <span class="text-muted">Not set</span>
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($semester->afternoon_period_start && $semester->afternoon_period_end)
                                                        {{ \Carbon\Carbon::parse($semester->afternoon_period_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($semester->afternoon_period_end)->format('g:i A') }}
                                                    @else
                                                        <span class="text-muted">Not set</span>
                                                    @endif
                                                </small>
                                            </td>
                                         
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>To create new semesters, please contact your administrator. You can edit existing semester details only.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Management Section -->
    @if(!$semesters->isEmpty())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-layer-group me-2"></i>Section Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Section Overview:</strong> View section details and time schedules. Contact your administrator to make changes.
                    </div>
                    
                    <!-- Section List Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="sectionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Section Name</th>
                                    <th width="10%">Grade Level</th>
                             
                                     <th width="12%">AM Time In</th>
                                    <th width="12%">AM Time Out</th>
                                    <th width="12%">PM Time In</th>
                                    <th width="12%">PM Time Out</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sectionsTableBody">
                                @if($sections && count($sections) > 0)
                                    @foreach($sections as $section)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $section->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">Grade {{ $section->gradelevel }}</span>
                                            </td>
                                         
                                           
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->am_time_in_start && $section->am_time_in_end)
                                                        {{ \Carbon\Carbon::parse($section->am_time_in_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($section->am_time_in_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->am_time_out_start && $section->am_time_out_end)
                                                        {{ \Carbon\Carbon::parse($section->am_time_out_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->pm_time_in_start && $section->pm_time_in_end)
                                                        {{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($section->pm_time_in_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->pm_time_out_start && $section->pm_time_out_end)
                                                        {{ \Carbon\Carbon::parse($section->pm_time_out_start)->format('g:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-info" onclick="viewSection({{ $section->id }})" title="View Section Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-layer-group me-2"></i>No sections found. Click "Add Section" to create one.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Sections help organize students and generate detailed analytics. Configure sections based on your school's structure.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>


<!-- View Semester Modal -->
<div class="modal fade" id="viewSemesterModal" tabindex="-1" aria-labelledby="viewSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewSemesterModalLabel">
                    <i class="fas fa-eye me-2"></i>View Semester Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Semester Information:</strong> View semester details and schedules. Contact your administrator to make changes.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-1">
                            <label class="form-label">
                                <i class="fas fa-graduation-cap me-1"></i>Semester Name
                            </label>
                            <input type="text" class="form-control" id="view_name" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-1">
                            <label class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Status
                            </label>
                            <input type="text" class="form-control" id="view_status" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-1">
                            <label class="form-label">
                                <i class="fas fa-calendar-check me-1"></i>Start Date
                            </label>
                            <input type="text" class="form-control" id="view_start_date" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-1">
                            <label class="form-label">
                                <i class="fas fa-calendar-check me-1"></i>End Date
                            </label>
                            <input type="text" class="form-control" id="view_end_date" readonly>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-1">
                    <i class="fas fa-clock me-2"></i>Period Schedule Configuration
                </h6>
                
                <!-- Period Times -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-sun me-1"></i>Morning Period
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-1">
                                    <label class="form-label">Start Time</label>
                                    <input type="text" class="form-control" id="view_morning_period_start" readonly>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">End Time</label>
                                    <input type="text" class="form-control" id="view_morning_period_end" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-moon me-1"></i>Afternoon Period
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-1">
                                    <label class="form-label">Start Time</label>
                                    <input type="text" class="form-control" id="view_afternoon_period_start" readonly>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">End Time</label>
                                    <input type="text" class="form-control" id="view_afternoon_period_end" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- delete -->
<!-- <div class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="deleteSemesterForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSemesterModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Dangerous Action - Delete Semester
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Critical Warning!</h6>
                        <p class="mb-0">This action is <strong>IRREVERSIBLE</strong> and will permanently delete:</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="text-center p-1 bg-light rounded">
                                <i class="fas fa-graduation-cap fa-2x text-primary mb-2"></i>
                                <h6>Semester Data</h6>
                                <p class="text-muted mb-0">Name, dates, schedules</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-1 bg-light rounded">
                                <i class="fas fa-users fa-2x text-warning mb-2"></i>
                                <h6>All Students</h6>
                                <p class="text-muted mb-0">Enrolled in this semester</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-1 bg-light rounded">
                                <i class="fas fa-clipboard-check fa-2x text-danger mb-2"></i>
                                <h6>Attendance Records</h6>
                                <p class="text-muted mb-0">All historical data</p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Semester to Delete:</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <span id="deleteSemesterName" class="text-danger"></span></p>
                                    <p><strong>Duration:</strong> <span id="deleteSemesterDuration"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Students Enrolled:</strong> <span id="deleteSemesterStudents" class="badge bg-warning text-dark"></span></p>
                                    <p><strong>Attendance Records:</strong> <span id="deleteSemesterAttendance" class="badge bg-info"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label text-danger" for="confirmDelete">
                                <strong>I understand that this action cannot be undone and all related data will be permanently lost.</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel - Keep Semester
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash me-1"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </form>
    </div>
</div> -->

<!-- Add/Edit Section Modal -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-labelledby="sectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form id="sectionForm">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="sectionModalLabel">
                        <i class="fas fa-eye me-2"></i><span id="sectionModalTitle">View Section Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Section Details:</strong> View section information and time schedules. Contact your administrator to make changes.
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-1">
                                <label for="sectionName" class="form-label">
                                    <i class="fas fa-users me-1"></i>Section Name *
                                </label>
                                <input type="text" class="form-control" id="sectionName" placeholder="e.g., STEM, HUMMS, ABM" required>
                                <div class="form-text">Enter the section name (strand, track, or custom name)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-1">
                                <label for="sectionGradeLevel" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Grade Level *
                                </label>
                                <select class="form-select" id="sectionGradeLevel" required>
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
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-1">
                        <i class="fas fa-clock me-2"></i>Time Schedule Configuration
                    </h6>
                    
                    <!-- Morning Sessions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sun me-1"></i>Morning Time-In Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="amTimeInStart" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="amTimeInStart" name="am_time_in_start">
                                        </div>
                                        <div class="col-6">
                                            <label for="amTimeInEnd" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="amTimeInEnd" name="am_time_in_end">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sign-out-alt me-1"></i>Morning Time-Out Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="amTimeOutStart" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="amTimeOutStart" name="am_time_out_start">
                                        </div>
                                        <div class="col-6">
                                            <label for="amTimeOutEnd" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="amTimeOutEnd" name="am_time_out_end">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Afternoon Sessions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sun me-1"></i>Afternoon Time-In Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="pmTimeInStart" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="pmTimeInStart" name="pm_time_in_start">
                                        </div>
                                        <div class="col-6">
                                            <label for="pmTimeInEnd" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="pmTimeInEnd" name="pm_time_in_end">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sign-out-alt me-1"></i>Afternoon Time-Out Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="pmTimeOutStart" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="pmTimeOutStart" name="pm_time_out_start">
                                        </div>
                                        <div class="col-6">
                                            <label for="pmTimeOutEnd" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="pmTimeOutEnd" name="pm_time_out_end">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                  
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i><span id="sectionSubmitText">Close</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Section Management Functions
let sections = @json($sections ?? []);
let editingSection = null;
let loadingSections = false;

// Initial render of sections from Laravel data
function initializeSections() {
    // Sections are already rendered by Laravel, no need to do anything
    console.log('Sections initialized with Laravel data');
}

// Reload sections from database after CRUD operations
async function reloadSections() {
    // Simply reload the page to get fresh Laravel data
    window.location.reload();
}

function viewSection(sectionId) {
    // Find the section object
    const section = sections.find(s => s.id == sectionId);
    if (!section) {
        showAlert('info', 'Section not found.');
        return;
    }

    // Set modal to view mode
    document.getElementById('sectionModalTitle').textContent = 'View Section Details';
    document.getElementById('sectionSubmitText').textContent = 'Close';
    
    // Fill form with current data (read-only)
    document.getElementById('sectionName').value = section.name || '';
    document.getElementById('sectionName').disabled = true;
    document.getElementById('sectionGradeLevel').value = section.gradelevel || '';
    document.getElementById('sectionGradeLevel').disabled = true;
    
    // Time fields (read-only)
    document.getElementById('amTimeInStart').value = section.am_time_in_start || '';
    document.getElementById('amTimeInStart').disabled = true;
    document.getElementById('amTimeInEnd').value = section.am_time_in_end || '';
    document.getElementById('amTimeInEnd').disabled = true;
    document.getElementById('amTimeOutStart').value = section.am_time_out_start || '';
    document.getElementById('amTimeOutStart').disabled = true;
    document.getElementById('amTimeOutEnd').value = section.am_time_out_end || '';
    document.getElementById('amTimeOutEnd').disabled = true;
    document.getElementById('pmTimeInStart').value = section.pm_time_in_start || '';
    document.getElementById('pmTimeInStart').disabled = true;
    document.getElementById('pmTimeInEnd').value = section.pm_time_in_end || '';
    document.getElementById('pmTimeInEnd').disabled = true;
    document.getElementById('pmTimeOutStart').value = section.pm_time_out_start || '';
    document.getElementById('pmTimeOutStart').disabled = true;
    document.getElementById('pmTimeOutEnd').value = section.pm_time_out_end || '';
    document.getElementById('pmTimeOutEnd').disabled = true;
    
    var modal = new bootstrap.Modal(document.getElementById('sectionModal'));
    modal.show();
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

 

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSections();
});

// Semester Management Functions
function viewSemesterDetails(semesterId) {
    // Find the semester in the data
    const semester = @json($semesters).find(s => s.id == semesterId);
    if (!semester) {
        showAlert('info', 'Semester not found.');
        return;
    }

    // Populate the view form
    document.getElementById('view_name').value = semester.name || '';
    document.getElementById('view_status').value = semester.status || '';
    document.getElementById('view_start_date').value = semester.start_date || '';
    document.getElementById('view_end_date').value = semester.end_date || '';
    document.getElementById('view_morning_period_start').value = semester.morning_period_start || '';
    document.getElementById('view_morning_period_end').value = semester.morning_period_end || '';
    document.getElementById('view_afternoon_period_start').value = semester.afternoon_period_start || '';
    document.getElementById('view_afternoon_period_end').value = semester.afternoon_period_end || '';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('viewSemesterModal'));
    modal.show();
}

</script>

@endsection
