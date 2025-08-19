@extends('admin/sidebar')
@section('title', 'Reports')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">📈</span>
                    Reports Dashboard
                </h4>
                <p class="subtitle mb-0">Generate and view system reports</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                    <i class="fas fa-plus me-1"></i>Generate Report
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Statistics Overview -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $totalStudents ?? 0 }}</div>
                                <small>Total Students</small>
                            </div>
                            <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $totalTeachers ?? 0 }}</div>
                                <small>Active Teachers</small>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $totalSections ?? 0 }}</div>
                                <small>Total Sections</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $attendanceToday ?? 0 }}</div>
                                <small>Today's Attendance</small>
                            </div>
                            <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Report Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-bolt me-1"></i>Quick Reports
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-lg-3 col-md-6">
                        <button class="btn btn-outline-primary w-100" onclick="generateReport('attendance')">
                            <i class="fas fa-clipboard-check fa-2x mb-2 d-block"></i>
                            <span>Attendance Report</span>
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button class="btn btn-outline-success w-100" onclick="generateReport('students')">
                            <i class="fas fa-user-graduate fa-2x mb-2 d-block"></i>
                            <span>Student Report</span>
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button class="btn btn-outline-info w-100" onclick="generateReport('sections')">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            <span>Section Report</span>
                        </button>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button class="btn btn-outline-warning w-100" onclick="generateReport('teachers')">
                            <i class="fas fa-chalkboard-teacher fa-2x mb-2 d-block"></i>
                            <span>Teacher Report</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Reports -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-alt me-1"></i>Available Report Types
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-chart-bar me-1"></i>Analytics Reports</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right me-2 text-primary"></i>Daily Attendance Summary</li>
                            <li><i class="fas fa-arrow-right me-2 text-primary"></i>Weekly Attendance Trends</li>
                            <li><i class="fas fa-arrow-right me-2 text-primary"></i>Monthly Performance Overview</li>
                            <li><i class="fas fa-arrow-right me-2 text-primary"></i>Student Attendance Patterns</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-download me-1"></i>Export Options</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right me-2 text-success"></i>PDF Reports</li>
                            <li><i class="fas fa-arrow-right me-2 text-success"></i>Excel Spreadsheets</li>
                            <li><i class="fas fa-arrow-right me-2 text-success"></i>CSV Data Files</li>
                            <li><i class="fas fa-arrow-right me-2 text-success"></i>Printable Formats</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Report Modal -->
<div class="modal fade" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="generateReportModalLabel">
                    <i class="fas fa-plus me-2"></i>Generate New Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="generateReportForm">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="">Select Report Type</option>
                            <option value="attendance">Attendance Report</option>
                            <option value="students">Student Report</option>
                            <option value="sections">Section Report</option>
                            <option value="teachers">Teacher Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date_range" class="form-label">Date Range</label>
                        <select class="form-select" id="date_range" name="date_range" required>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="mb-3" id="custom_date_range" style="display: none;">
                        <div class="row">
                            <div class="col-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            <div class="col-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Export Format</label>
                        <select class="form-select" id="export_format" name="export_format" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReport()">
                    <i class="fas fa-download me-1"></i>Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Handle date range selection
document.getElementById('date_range').addEventListener('change', function() {
    const customDateRange = document.getElementById('custom_date_range');
    if (this.value === 'custom') {
        customDateRange.style.display = 'block';
    } else {
        customDateRange.style.display = 'none';
    }
});

// Quick report generation
function generateReport(type) {
    document.getElementById('report_type').value = type;
    const modal = new bootstrap.Modal(document.getElementById('generateReportModal'));
    modal.show();
}

// Submit report generation
function submitReport() {
    const form = document.getElementById('generateReportForm');
    const formData = new FormData(form);
    
    // Here you would typically send the request to generate the report
    alert('Report generation functionality will be implemented based on your requirements.');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('generateReportModal'));
    modal.hide();
}
</script>

@endsection
