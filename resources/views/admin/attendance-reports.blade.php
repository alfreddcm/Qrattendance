@extends('admin.sidebar')
@section('title', 'Attendance Reports')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-chart-bar me-2"></i>
                Attendance Reports
            </h4>
            <p class="subtitle fs-6 mb-0">Generate and view attendance reports across schools and teachers</p>
        </div>
        <div class="page-actions">
            @if(isset($attendanceData) && $attendanceData->count() > 0)
                <button type="button" class="btn btn-success btn-sm px-2 py-1" onclick="exportReport()">
                    <i class="fas fa-download me-1"></i>Export Report
                </button>
            @endif
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

    <!-- Filters Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0 fs-6">
                        <i class="fas fa-filter me-1"></i>
                        Report Filters
                    </h6>
                </div>
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('admin.attendance-reports') }}" id="filtersForm">
                        <div class="row">
                            <!-- Date Range -->
                            <div class="col-md-3 mb-2">
                                <label for="start_date" class="form-label fs-6">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" 
                                       value="{{ request('start_date') }}" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ request('end_date') }}" required>
                            </div>
                            
                            <!-- School Filter -->
                            <div class="col-md-3 mb-3">
                                <label for="school_id" class="form-label">School</label>
                                <select class="form-select" id="school_id" name="school_id">
                                    <option value="">All Schools</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->school_id }}" 
                                                {{ request('school_id') == $school->school_id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Teacher Filter -->
                            <div class="col-md-3 mb-3">
                                <label for="teacher_id" class="form-label">Teacher</label>
                                <select class="form-select" id="teacher_id" name="teacher_id">
                                    <option value="">All Teachers</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" 
                                                {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Generate Report
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($attendanceData) && $attendanceData->count() > 0)
        <!-- Summary Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card text-center shadow-sm h-100 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title text-primary mb-0">Total Records</h6>
                            <i class="fas fa-list text-primary"></i>
                        </div>
                        <h2 class="display-4 text-primary">{{ $attendanceData->total() }}</h2>
                        <small class="text-muted">Attendance entries</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card text-center shadow-sm h-100 border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title text-success mb-0">Unique Students</h6>
                            <i class="fas fa-users text-success"></i>
                        </div>
                        <h2 class="display-4 text-success">{{ $attendanceData->pluck('student_id')->unique()->count() }}</h2>
                        <small class="text-muted">Students attended</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card text-center shadow-sm h-100 border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title text-info mb-0">Date Range</h6>
                            <i class="fas fa-calendar text-info"></i>
                        </div>
                        <h6 class="text-info">{{ request('start_date') }}</h6>
                        <h6 class="text-info">to {{ request('end_date') }}</h6>
                        <small class="text-muted">{{ \Carbon\Carbon::parse(request('start_date'))->diffInDays(\Carbon\Carbon::parse(request('end_date'))) + 1 }} days</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card text-center shadow-sm h-100 border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title text-warning mb-0">Avg Daily</h6>
                            <i class="fas fa-chart-line text-warning"></i>
                        </div>
                        @php
                            $days = \Carbon\Carbon::parse(request('start_date'))->diffInDays(\Carbon\Carbon::parse(request('end_date'))) + 1;
                            $avgDaily = $days > 0 ? round($attendanceData->total() / $days, 1) : 0;
                        @endphp
                        <h2 class="display-4 text-warning">{{ $avgDaily }}</h2>
                        <small class="text-muted">Attendance per day</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>
                            Attendance Records
                        </h5>
                        <span class="badge bg-primary">{{ $attendanceData->total() }} records</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>School</th>
                                        <th>Teacher</th>
                                        <th>Section</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendanceData as $record)
                                    <tr>
                                        <td>
                                            <strong>{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</small>
                                        </td>
                                        <td>
                                            @if($record->time_in)
                                                <span class="badge bg-success">{{ \Carbon\Carbon::parse($record->time_in)->format('H:i') }}</span>
                                            @else
                                                <span class="badge bg-secondary">--:--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->student)
                                                <div class="d-flex align-items-center">
                                                    @if($record->student->picture)
                                                        <img src="{{ asset('storage/' . $record->student->picture) }}" 
                                                             alt="{{ $record->student->name }}" 
                                                             class="rounded-circle me-2" 
                                                             style="width: 30px; height: 30px; object-fit: cover;">
                                                    @endif
                                                    <strong>{{ $record->student->name }}</strong>
                                                </div>
                                            @else
                                                <span class="text-muted">Unknown Student</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->student && $record->student->id_no)
                                                <span class="badge bg-info">{{ $record->student->id_no }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->student && $record->student->school)
                                                <span class="badge bg-primary">{{ $record->student->school->name }}</span>
                                            @else
                                                <span class="badge bg-warning">No School</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->student && $record->student->user)
                                                <span class="badge bg-success">{{ $record->student->user->name }}</span>
                                            @else
                                                <span class="badge bg-danger">No Teacher</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->student && $record->student->user && $record->student->user->section_name)
                                                {{ $record->student->user->section_name }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Present</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="card-footer">
                            {{ $attendanceData->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @elseif(request()->filled(['start_date', 'end_date']))
        <!-- No Data Found -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No attendance records found</h5>
                        <p class="text-muted">
                            No attendance data matches your filter criteria for the selected date range.
                            <br>Try adjusting your filters or selecting a different date range.
                        </p>
                        <button type="button" class="btn btn-primary" onclick="clearFilters()">
                            <i class="fas fa-refresh me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @else
        <!-- Initial State -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                        <h5 class="text-primary">Generate Attendance Report</h5>
                        <p class="text-muted">
                            Select a date range and optional filters above to generate an attendance report.
                            <br>You can filter by specific schools or teachers to narrow down the results.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
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
    
    .form-control, .form-select {
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
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
</style>

<script>
function clearFilters() {
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('school_id').value = '';
    document.getElementById('teacher_id').value = '';
    
    // Remove query parameters from URL
    window.location.href = "{{ route('admin.attendance-reports') }}";
}

function exportReport() {
    // Get current filter values
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const schoolId = document.getElementById('school_id').value;
    const teacherId = document.getElementById('teacher_id').value;
    
    // Build export URL with current filters
    let exportUrl = "{{ route('admin.attendance-reports') }}?export=1";
    if (startDate) exportUrl += "&start_date=" + startDate;
    if (endDate) exportUrl += "&end_date=" + endDate;
    if (schoolId) exportUrl += "&school_id=" + schoolId;
    if (teacherId) exportUrl += "&teacher_id=" + teacherId;
    
    // Open in new window to download
    window.open(exportUrl, '_blank');
}

// Set default date range to current month
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('start_date').value) {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];
    }
});
</script>

@endsection
