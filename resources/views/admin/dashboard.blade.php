@extends('admin/sidebar')
@section('title', 'Dashboard')
@section('content')

<div class="sticky-header" >
    <div class="d-flex justify-content-between align-items-center" style="margin-left: 1rem;" >
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-tachometer-alt me-2"></i>
                Admin Dashboard
            </h4>
            <p class="subtitle fs-6 mb-0">System Overview</p>
        </div>
        
    </div>
</div>

<div class="container-fluid">

     <div class="row g-3 mb-3" style="margin-left: 1rem; margin-right: 1rem;">
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('admin.manage-students') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="totalStudents">{{ $totalStudents ?? 0 }}</div>
                                    <small>Total Students</small>
                                </div>
                                <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('admin.manage-sections') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="totalSections">{{ $totalSections ?? 0 }}</div>
                                    <small>Total Grade Sections</small>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: {{ $totalSections > 0 ? min(($totalSections / 20) * 100, 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('admin.manage-teachers') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="totalTeachers">{{ $totalTeachers ?? 0 }}</div>
                                    <small>Total Teachers</small>
                                </div>
                                <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: {{ $totalTeachers > 0 ? min(($totalTeachers / 50) * 100, 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="##" class="text-decoration-none">
                    <div class="card stat-card text-white bg-warning h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="todaySessionCount">{{ $todaySessionCount ?? 0 }}</div>
                                    <small>Active Sessions Today</small>
                                </div>
                                <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: {{ $todaySessionCount > 0 ? min(($todaySessionCount / 4) * 100, 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('admin.teacher-attendance-reports') }}" class="text-decoration-none">
                    <div class="card stat-card text-white bg-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="h4 mb-0" id="totalAttendanceRecords">{{ $totalAttendanceRecords ?? 0 }}</div>
                                    <small>Total Attendance Records</small>
                                </div>
                                <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-white" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

         <div class="row g-3">
            <div class="col-lg-4">
                 <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-heartbeat me-1"></i>System Status</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="checkSystemStatus()">
                            <i class="fas fa-sync fa-sm"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Database</span>
                            <span class="badge bg-secondary text-white" id="databaseStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>SMS Gateway</span>
                            <span class="badge bg-secondary text-white" id="smsGatewayStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>File Storage</span>
                            <span class="badge bg-secondary text-white" id="fileStorageStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <!-- Recent Attendance List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-1"></i>Recent Attendance List</span>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" onchange="filterBySchool(this.value)" id="schoolFilter">
                                <option value="">All Schools</option>
                                @if(isset($schools))
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshAttendanceList()">
                                <i class="fas fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="recentAttendanceTable">
                                <thead class="table-light">
                                    <tr>
                                        <th onclick="sortTable('school')" style="cursor: pointer;">
                                            School Name <i class="fas fa-sort"></i>
                                        </th>
                                        <th>Student Name</th>
                                        <th>Grade - Section</th>
                                        <th>Time Recorded</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceTableBody">
                                    @if(isset($recentAttendance) && count($recentAttendance) > 0)
                                        @foreach($recentAttendance as $attendance)
                                        <tr>
                                            <td><strong>{{ $attendance->student->user->school->name ?? 'N/A' }}</strong></td>
                                            <td>{{ $attendance->student->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    Grade {{ $attendance->student->section->gradelevel ?? 'N/A' }} - {{ $attendance->student->section->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="time-records">
                                                    @if($attendance->time_in_am)
                                                        <div class="time-entry mb-1">
                                                            <span class="badge bg-success text-white">
                                                                <i class="fas fa-sun me-1"></i>AM In: {{ \Carbon\Carbon::parse($attendance->time_in_am)->format('h:i A') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if($attendance->time_out_am)
                                                        <div class="time-entry mb-1">
                                                            <span class="badge bg-info text-white">
                                                                <i class="fas fa-sign-out-alt me-1"></i>AM Out: {{ \Carbon\Carbon::parse($attendance->time_out_am)->format('h:i A') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if($attendance->time_in_pm)
                                                        <div class="time-entry mb-1">
                                                            <span class="badge bg-warning text-white">
                                                                <i class="fas fa-moon me-1"></i>PM In: {{ \Carbon\Carbon::parse($attendance->time_in_pm)->format('h:i A') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if($attendance->time_out_pm)
                                                        <div class="time-entry mb-1">
                                                            <span class="badge bg-danger text-white">
                                                                <i class="fas fa-sign-out-alt me-1"></i>PM Out: {{ \Carbon\Carbon::parse($attendance->time_out_pm)->format('h:i A') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if(!$attendance->time_in_am && !$attendance->time_out_am && !$attendance->time_in_pm && !$attendance->time_out_pm)
                                                        <span class="badge bg-secondary text-white">
                                                            <i class="fas fa-question me-1"></i>No Records
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $hasAMIn = $attendance->time_in_am;
                                                    $hasAMOut = $attendance->time_out_am;
                                                    $hasPMIn = $attendance->time_in_pm;
                                                    $hasPMOut = $attendance->time_out_pm;
                                                    $totalEntries = ($hasAMIn ? 1 : 0) + ($hasAMOut ? 1 : 0) + ($hasPMIn ? 1 : 0) + ($hasPMOut ? 1 : 0);
                                                    
                                                    if ($totalEntries === 4) {
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Complete';
                                                        $statusIcon = 'fas fa-check-circle';
                                                    } elseif ($totalEntries >= 2) {
                                                        $statusClass = 'bg-warning';
                                                        $statusText = 'Partial';
                                                        $statusIcon = 'fas fa-clock';
                                                    } elseif ($totalEntries === 1) {
                                                        $statusClass = 'bg-info';
                                                        $statusText = 'Started';
                                                        $statusIcon = 'fas fa-play';
                                                    } else {
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = 'No Records';
                                                        $statusIcon = 'fas fa-question';
                                                    }
                                                @endphp
                                                <span class="badge {{ $statusClass }} text-white">
                                                    <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $totalEntries }}/4 entries</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center py-3">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                                <span class="text-muted">No recent attendance records</span>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
 
</div>

<script>
// Dashboard Functions
function refreshDashboard() {
    location.reload();
}

// System Status Check Functions
async function checkSystemStatus() {
    checkDatabaseStatus();
    checkSMSGatewayStatus();
    checkFileStorageStatus();
}

async function checkDatabaseStatus() {
    try {
        const statusElement = document.getElementById('databaseStatus');
        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        statusElement.className = 'badge bg-secondary';
        
        const response = await fetch('{{ route("admin.system.status.database") }}');
        const data = await response.json();
        
        if (data.status === 'online') {
            statusElement.innerHTML = '<i class="fas fa-check-circle me-1"></i>Online';
            statusElement.className = 'badge bg-success text-white';
        } else {
            statusElement.innerHTML = '<i class="fas fa-times-circle me-1"></i>Offline';
            statusElement.className = 'badge bg-danger text-white';
        }
    } catch (error) {
        const statusElement = document.getElementById('databaseStatus');
        statusElement.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Error';
        statusElement.className = 'badge bg-danger text-white';
        console.error('Database status check failed:', error);
    }
}

async function checkSMSGatewayStatus() {
    try {
        const statusElement = document.getElementById('smsGatewayStatus');
        statusElement.innerHTML = '<i class="fas fa-satellite-dish fa-spin"></i> Pinging...';
        statusElement.className = 'badge bg-secondary text-white';
        
        const response = await fetch('{{ route("admin.system.status.sms") }}');
        const data = await response.json();
        
        if (response.ok && data.status === 'online') {
            const responseTime = data.response_time || 'N/A';
            const httpCode = data.http_code || '';
            
            statusElement.innerHTML = `<i class="fas fa-signal me-1"></i>Online`;
            statusElement.className = 'badge bg-success text-white';
            
            // Add detailed tooltip with all information
            statusElement.title = `SMS Gateway Status: ${data.message}
Gateway URL: ${data.gateway_url}
Response Time: ${responseTime}
HTTP Code: ${httpCode}
Last Checked: ${new Date().toLocaleTimeString()}`;
            
        } else {
            statusElement.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Offline';
            statusElement.className = 'badge bg-danger text-white';
            
            // Add tooltip with error details
            statusElement.title = `SMS Gateway Error: ${data.message}
Gateway URL: ${data.gateway_url || 'Not configured'}
Timeout: ${data.timeout || 'N/A'}
Last Checked: ${new Date().toLocaleTimeString()}`;
        }
        
        // Log the result for debugging
        console.log('SMS Gateway Status:', data);
        
    } catch (error) {
        const statusElement = document.getElementById('smsGatewayStatus');
        statusElement.innerHTML = '<i class="fas fa-times-circle me-1"></i>Error';
        statusElement.className = 'badge bg-danger text-white';
        statusElement.title = `Network error occurred while pinging SMS gateway
Error: ${error.message}
Last Checked: ${new Date().toLocaleTimeString()}`;
        console.error('SMS Gateway ping failed:', error);
    }
}

async function checkFileStorageStatus() {
    try {
        const statusElement = document.getElementById('fileStorageStatus');
        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        statusElement.className = 'badge bg-secondary';
        
        const response = await fetch('{{ route("admin.system.status.storage") }}');
        const data = await response.json();
        
        if (data.status === 'online') {
            statusElement.innerHTML = '<i class="fas fa-hdd me-1"></i>Available';
            statusElement.className = 'badge bg-success text-white';
            if (data.usage) {
                statusElement.title = `Storage Usage: ${data.usage}`;
            }
        } else {
            statusElement.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Limited';
            statusElement.className = 'badge bg-warning text-white';
        }
    } catch (error) {
        const statusElement = document.getElementById('fileStorageStatus');
        statusElement.innerHTML = '<i class="fas fa-times-circle me-1"></i>Error';
        statusElement.className = 'badge bg-danger text-white';
        console.error('File Storage status check failed:', error);
    }
}

// Recent Attendance Functions
function filterBySchool(schoolId) {
    const tableBody = document.getElementById('attendanceTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const schoolCell = rows[i].getElementsByTagName('td')[0];
        if (schoolCell && schoolId !== '') {
            const schoolName = schoolCell.textContent.trim();
            // Simple filtering - in production, you might want to use data attributes
            rows[i].style.display = schoolName.includes(schoolId) ? '' : 'none';
        } else {
            rows[i].style.display = '';
        }
    }
}

function sortTable(column) {
    const table = document.getElementById('recentAttendanceTable');
    const tbody = table.getElementsByTagName('tbody')[0];
    const rows = Array.from(tbody.getElementsByTagName('tr'));
    
    let columnIndex = 0;
    switch(column) {
        case 'school': columnIndex = 0; break;
        case 'student': columnIndex = 1; break;
        case 'section': columnIndex = 2; break;
        case 'time': columnIndex = 3; break;
        case 'status': columnIndex = 4; break;
    }
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        return aText.localeCompare(bText);
    });
    
    // Clear and re-append sorted rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    rows.forEach(row => tbody.appendChild(row));
}

function refreshAttendanceList() {
    fetch('{{ route("admin.attendance.recent") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById('attendanceTableBody');
                tableBody.innerHTML = data.html;
            }
        })
        .catch(error => console.error('Error refreshing attendance list:', error));
}

// Auto-refresh functions
setInterval(function() {
    fetch('{{ route("admin.dashboard.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalStudents').textContent = data.totalStudents;
                document.getElementById('totalTeachers').textContent = data.totalTeachers;
                document.getElementById('totalSections').textContent = data.totalSections;
                document.getElementById('todaySessionCount').textContent = data.todaySessionCount;
                document.getElementById('totalAttendanceRecords').textContent = data.totalAttendanceRecords;
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 300000); 

 document.addEventListener('DOMContentLoaded', function() {
    checkSystemStatus();
    
     setInterval(checkSystemStatus, 120000);
});
</script>

<style>
 .stat-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: none;
    border-radius: 12px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.stat-card .card-body {
    position: relative;
    overflow: hidden;
}

.stat-card .card-body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

/* Time Records Styling */
.time-records {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.time-entry .badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 500;
    min-width: 80px;
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
}

/* Enhanced System Status Badges */
.badge {
    transition: all 0.3s ease;
    cursor: help;
    position: relative;
}

.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Tooltip Styling */
.badge[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0,0,0,0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    white-space: pre-line;
    z-index: 1000;
    min-width: 200px;
    text-align: left;
    line-height: 1.4;
}

.badge[title]:hover::before {
    content: '';
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: rgba(0,0,0,0.9);
    z-index: 1000;
}

/* SMS Gateway Specific Styling */
#smsGatewayStatus {
    min-width: 80px;
    font-weight: 600;
}

/* Pulsing Animation for Checking States */
@keyframes pulse-check {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.02); }
    100% { opacity: 1; transform: scale(1); }
}

.badge .fa-satellite-dish {
    animation: pulse-check 1.5s ease-in-out infinite;
}

/* Status Color Indicators */
.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    border: 1px solid rgba(255,255,255,0.2);
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    border: 1px solid rgba(255,255,255,0.2);
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
    border: 1px solid rgba(255,255,255,0.2);
    color: #212529 !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
    border: 1px solid rgba(255,255,255,0.2);
}

/* System Status Card */
.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    border-radius: 8px 8px 0 0 !important;
}

/* Recent Attendance Table */
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

/* Responsive Badge Text */
@media (max-width: 768px) {
    .time-entry .badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        min-width: 70px;
    }
    
    .badge {
        font-size: 0.7rem;
    }
}

/* Loading Animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.badge .fa-spinner {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Card Header Icons */
.card-header i {
    margin-right: 8px;
    opacity: 0.9;
}

/* Progress Bars in Stat Cards */
.progress {
    background-color: rgba(255,255,255,0.2);
    border-radius: 2px;
}

.progress-bar {
    background-color: rgba(255,255,255,0.8) !important;
    border-radius: 2px;
    transition: width 0.6s ease;
}

/* Enhanced Table Styling */
.table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    border-color: #f1f3f4;
}

/* Button Enhancements */
.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

/* Badge Color Variations for Time Entries */
.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}
</style>

@endsection
