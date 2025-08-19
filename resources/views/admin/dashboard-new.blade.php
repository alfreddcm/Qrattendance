@extends('admin/sidebar')
@section('title', 'Dashboard')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">📊</span>
                    Admin Dashboard
                </h4>
                <p class="subtitle mb-0">System overview and quick actions</p>
            </div>
            <div class="d-flex gap-2">
               
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshDashboard()">
                    <i class="fas fa-refresh me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-primary">
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
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="totalTeachers">{{ $totalTeachers ?? 0 }}</div>
                                <small>Active Teachers</small>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $totalTeachers > 0 ? min(($totalTeachers / 50) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="totalSections">{{ $totalSections ?? 0 }}</div>
                                <small>Active Sections</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $totalSections > 0 ? min(($totalSections / 20) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="attendanceToday">{{ $attendanceToday ?? 0 }}</div>
                                <small>Today's Attendance</small>
                            </div>
                            <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $attendanceToday > 0 && $totalStudents > 0 ? min(($attendanceToday / $totalStudents) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt me-1"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="{{ route('admin.manage-students-new') }}" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center p-3">
                            <i class="fas fa-user-plus fa-2x mb-2"></i>
                            <span>Add Student</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="{{ route('admin.manage-teachers') }}" class="btn btn-outline-success w-100 d-flex flex-column align-items-center p-3">
                            <i class="fas fa-user-tie fa-2x mb-2"></i>
                            <span>Manage Teachers</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <button class="btn btn-outline-info w-100 d-flex flex-column align-items-center p-3" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <span>Create Section</span>
                        </button>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="{{ route('admin.attendance') }}" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center p-3">
                            <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                            <span>Attendance</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary w-100 d-flex flex-column align-items-center p-3">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <span>Reports</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <button class="btn btn-outline-dark w-100 d-flex flex-column align-items-center p-3" onclick="openSettings()">
                            <i class="fas fa-cog fa-2x mb-2"></i>
                            <span>Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & System Status -->
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock me-1"></i>Recent Activity</span>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterActivity(this.value)">
                            <option value="all">All Activities</option>
                            <option value="students">Student Activities</option>
                            <option value="teachers">Teacher Activities</option>
                            <option value="attendance">Attendance</option>
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-feed" id="activityFeed">
                            @if(isset($recentActivities) && count($recentActivities) > 0)
                                @foreach($recentActivities as $activity)
                                <div class="activity-item d-flex align-items-start p-3 border-bottom">
                                    <div class="activity-icon me-3">
                                        <i class="fas {{ $activity['icon'] ?? 'fa-info' }} text-{{ $activity['type'] ?? 'primary' }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="activity-content">
                                            <strong>{{ $activity['title'] ?? 'Activity' }}</strong>
                                            <p class="mb-1 text-muted">{{ $activity['description'] ?? 'No description' }}</p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $activity['time'] ?? 'Unknown time' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                    <h6 class="text-muted">No recent activities</h6>
                                    <p class="text-muted small">Activities will appear here as they happen.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- System Status -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-heartbeat me-1"></i>System Status
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Database</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>QR Scanner</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>SMS Gateway</span>
                            <span class="badge bg-warning">Limited</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>File Storage</span>
                            <span class="badge bg-success">Available</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tachometer-alt me-1"></i>Quick Stats
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 text-center">
                                <div class="h5 mb-0 text-primary">{{ $studentsThisWeek ?? 0 }}</div>
                                <small class="text-muted">New Students<br>This Week</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h5 mb-0 text-success">{{ $attendanceRate ?? '0%' }}</div>
                                <small class="text-muted">Attendance<br>Rate</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h5 mb-0 text-info">{{ $activeTeachers ?? 0 }}</div>
                                <small class="text-muted">Active<br>Teachers</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h5 mb-0 text-warning">{{ $sectionsToday ?? 0 }}</div>
                                <small class="text-muted">Sections<br>Today</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

 

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="addSectionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Create New Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSectionForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="section_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="section_name" name="name" required placeholder="e.g., STEM, HUMSS, ABM">
                    </div>
                    <div class="mb-3">
                        <label for="section_gradelevel" class="form-label">Grade Level <span class="text-danger">*</span></label>
                        <select class="form-select" id="section_gradelevel" name="gradelevel" required>
                            <option value="">Select Grade Level</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="section_description" class="form-label">Description</label>
                        <textarea class="form-control" id="section_description" name="description" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save me-1"></i>Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add Section Form
document.getElementById('addSectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.sections.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating section');
    });
});

// Dashboard Functions
function refreshDashboard() {
    location.reload();
}

function filterActivity(type) {
    // Implementation for filtering activities
    console.log('Filter activity by:', type);
}

function openSettings() {
    // Implementation for opening settings
    window.location.href = '{{ route("admin.settings") }}';
}

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    // Update stats without full page reload
    fetch('{{ route("admin.dashboard.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalStudents').textContent = data.totalStudents;
                document.getElementById('totalTeachers').textContent = data.totalTeachers;
                document.getElementById('totalSections').textContent = data.totalSections;
                document.getElementById('attendanceToday').textContent = data.attendanceToday;
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 300000); // 5 minutes
</script>

@endsection
