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

    <!-- Overview Cards with Links -->
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

        <!-- System Status Module & Recent Attendance -->
        <div class="row g-3">
            <div class="col-lg-4">
                <!-- System Status -->
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
                            <span class="badge" id="databaseStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>QR Scanner</span>
                            <span class="badge" id="qrScannerStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>SMS Gateway</span>
                            <span class="badge" id="smsGatewayStatus">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>File Storage</span>
                            <span class="badge" id="fileStorageStatus">
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
                                                <small>
                                                    @if($attendance->am_time_in)
                                                        <div><i class="fas fa-sign-in-alt text-success"></i> AM In: {{ \Carbon\Carbon::parse($attendance->am_time_in)->format('h:i A') }}</div>
                                                    @endif
                                                    @if($attendance->am_time_out)
                                                        <div><i class="fas fa-sign-out-alt text-primary"></i> AM Out: {{ \Carbon\Carbon::parse($attendance->am_time_out)->format('h:i A') }}</div>
                                                    @endif
                                                    @if($attendance->pm_time_in)
                                                        <div><i class="fas fa-sign-in-alt text-success"></i> PM In: {{ \Carbon\Carbon::parse($attendance->pm_time_in)->format('h:i A') }}</div>
                                                    @endif
                                                    @if($attendance->pm_time_out)
                                                        <div><i class="fas fa-sign-out-alt text-danger"></i> PM Out: {{ \Carbon\Carbon::parse($attendance->pm_time_out)->format('h:i A') }}</div>
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                @php
                                                    $isComplete = $attendance->am_time_in && $attendance->am_time_out && $attendance->pm_time_in && $attendance->pm_time_out;
                                                @endphp
                                                <span class="badge {{ $isComplete ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $isComplete ? 'Complete' : 'Partial' }}
                                                </span>
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
    checkQRScannerStatus();  
    checkSMSGatewayStatus();
    checkFileStorageStatus();
}

async function checkDatabaseStatus() {
    try {
        const response = await fetch('{{ route("admin.system.status.database") }}');
        const data = await response.json();
        const statusElement = document.getElementById('databaseStatus');
        
        if (data.status === 'online') {
            statusElement.innerHTML = 'Online';
            statusElement.className = 'badge bg-success';
        } else {
            statusElement.innerHTML = 'Offline';
            statusElement.className = 'badge bg-danger';
        }
    } catch (error) {
        const statusElement = document.getElementById('databaseStatus');
        statusElement.innerHTML = 'Error';
        statusElement.className = 'badge bg-danger';
    }
}

async function checkQRScannerStatus() {
    try {
        // Check if camera/scanner is available
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            const statusElement = document.getElementById('qrScannerStatus');
            statusElement.innerHTML = 'Available';
            statusElement.className = 'badge bg-success';
        } else {
            const statusElement = document.getElementById('qrScannerStatus');
            statusElement.innerHTML = 'Not Available';
            statusElement.className = 'badge bg-warning';
        }
    } catch (error) {
        const statusElement = document.getElementById('qrScannerStatus');
        statusElement.innerHTML = 'Error';
        statusElement.className = 'badge bg-danger';
    }
}

async function checkSMSGatewayStatus() {
    try {
        const response = await fetch('{{ route("admin.system.status.sms") }}');
        const data = await response.json();
        const statusElement = document.getElementById('smsGatewayStatus');
        
        if (data.status === 'online') {
            statusElement.innerHTML = 'Active';
            statusElement.className = 'badge bg-success';
        } else {
            statusElement.innerHTML = 'Limited';
            statusElement.className = 'badge bg-warning';
        }
    } catch (error) {
        const statusElement = document.getElementById('smsGatewayStatus');
        statusElement.innerHTML = 'Offline';
        statusElement.className = 'badge bg-danger';
    }
}

async function checkFileStorageStatus() {
    try {
        const response = await fetch('{{ route("admin.system.status.storage") }}');
        const data = await response.json();
        const statusElement = document.getElementById('fileStorageStatus');
        
        if (data.status === 'online') {
            statusElement.innerHTML = 'Available';
            statusElement.className = 'badge bg-success';
        } else {
            statusElement.innerHTML = 'Limited';
            statusElement.className = 'badge bg-warning';
        }
    } catch (error) {
        const statusElement = document.getElementById('fileStorageStatus');
        statusElement.innerHTML = 'Error';
        statusElement.className = 'badge bg-danger';
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
}, 300000); // 5 minutes

// Initialize system status check on page load
document.addEventListener('DOMContentLoaded', function() {
    checkSystemStatus();
    
    // Auto-check system status every 2 minutes
    setInterval(checkSystemStatus, 120000);
});
</script>

@endsection
