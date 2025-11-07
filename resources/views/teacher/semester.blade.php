@extends('teacher/sidebar')
@section('title', 'Manage School Years & Sections')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-layer-group me-2"></i>
                School Year and Sections
            </h4>
            <p class="subtitle fs-6 mb-0">View school year information (Contact admin to create new school years)</p>
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
                                <strong>Note:</strong> Only administrators can create new semesters.
                            </div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>Semester Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
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
                                                @if($semester->status === 'inactive')
                                                    <span class="badge bg-warning">Inactive</span>
                                                @elseif($isActive)
                                                    <span class="badge bg-success">Ongoing</span>
                                                @elseif($isPast)
                                                    <span class="badge bg-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-info">Upcoming</span>
                                                @endif
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
                        <small>To create new semesters, please contact your administrator.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>


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
                                            <i class="fas fa-layer-group me-2"></i>No sections found.
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
                        <small>Sections help organize students and generate detailed analytics.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

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
 let sections = @json($sections ?? []);
let editingSection = null;
let loadingSections = false;

 function initializeSections() {
     console.log('Sections initialized with Laravel data');
}

 async function reloadSections() {
     window.location.reload();
}

function viewSection(sectionId) {
     const section = sections.find(s => s.id == sectionId);
    if (!section) {
        showAlert('info', 'Section not found.');
        return;
    }

     document.getElementById('sectionModalTitle').textContent = 'View Section Details';
    document.getElementById('sectionSubmitText').textContent = 'Close';

     document.getElementById('sectionName').value = section.name || '';
    document.getElementById('sectionName').disabled = true;
    document.getElementById('sectionGradeLevel').value = section.gradelevel || '';
    document.getElementById('sectionGradeLevel').disabled = true;

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

 function viewSemesterDetails(semesterId) {
     const semester = @json($semesters).find(s => s.id == semesterId);
    if (!semester) {
        showAlert('info', 'Semester not found.');
        return;
    }

     document.getElementById('view_name').value = semester.name || '';
    document.getElementById('view_status').value = semester.status || '';
    document.getElementById('view_start_date').value = semester.start_date || '';
    document.getElementById('view_end_date').value = semester.end_date || '';

     const modal = new bootstrap.Modal(document.getElementById('viewSemesterModal'));
    modal.show();
}

</script>

@endsection
