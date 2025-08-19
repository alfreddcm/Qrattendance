@extends('teacher/sidebar')
@section('title', 'Dashboard')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">🏫</span>
                    Teacher Dashboard
                </h4>
                <p class="subtitle mb-0">Welcome back, {{ Auth::user()->name ?? 'Teacher' }}!</p>
            </div>
            
        </div>
    </div>

    <div class="container-fluid">
        <!-- Quick Stats -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="myStudents">{{ $myStudents ?? 0 }}</div>
                                <small>My Students</small>
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
                                <div class="h4 mb-0" id="mySections">{{ $mySections ?? 0 }}</div>
                                <small>My Sections</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $mySections > 0 ? min(($mySections / 5) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="todayPresent">{{ $todayPresent ?? 0 }}</div>
                                <small>Present Today</small>
                            </div>
                            <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $todayPresent > 0 && $myStudents > 0 ? min(($todayPresent / $myStudents) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0" id="attendanceRate">{{ $attendanceRate ?? '0%' }}</div>
                                <small>Attendance Rate</small>
                            </div>
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ str_replace('%', '', $attendanceRate ?? '0') }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Sections Overview -->
        @if(isset($teacherSections) && $teacherSections->count() > 0)
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-chalkboard me-1"></i>My Sections
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($teacherSections as $section)
                    <div class="col-lg-4 col-md-6">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $section->name }}</h6>
                                        <small class="text-muted">{{ $section->name }} - Grade {{ $section->gradelevel }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ $section->students_count ?? 0 }} students</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Today: {{ $section->present_today ?? 0 }}/{{ $section->students_count ?? 0 }}
                                    </small>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('teacher.students', ['section_id' => $section->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="{{ route('teacher.attendance', ['section_id' => $section->id]) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-clipboard-check"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @else
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5 class="text-muted">No Sections Assigned</h5>
                <p class="text-muted">Please contact the administrator to assign sections to your account.</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Check Again
                </button>
            </div>
        </div>
        @endif

        <!-- Attendance Summary Chart -->
        @if(isset($attendanceChartData))
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>Weekly Attendance Trend
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="60"></canvas>
            </div>
        </div>
        @endif
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dashboard Functions
function refreshDashboard() {
    location.reload();
}

function filterActivity(type) {
    // Implementation for filtering activities
    console.log('Filter activity by:', type);
    
    const activities = document.querySelectorAll('.activity-item');
    activities.forEach(activity => {
        if (type === 'all') {
            activity.style.display = 'flex';
        } else {
            // Show/hide based on activity type
            const hasType = activity.dataset.type === type;
            activity.style.display = hasType ? 'flex' : 'none';
        }
    });
}

function generateQRCodes() {
    if (confirm('Generate QR codes for all students in your sections?')) {
        fetch('{{ route("teacher.generate-qr-codes") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('QR codes generated successfully!');
                location.reload();
            } else {
                alert('Error generating QR codes: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating QR codes');
        });
    }
}

// Attendance Chart
@if(isset($attendanceChartData))
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($attendanceChartData['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']) !!},
        datasets: [{
            label: 'Attendance Rate',
            data: {!! json_encode($attendanceChartData['data'] ?? [0, 0, 0, 0, 0]) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
@endif

// Auto-refresh stats every 5 minutes
setInterval(function() {
    fetch('{{ route("teacher.dashboard.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('myStudents').textContent = data.myStudents;
                document.getElementById('mySections').textContent = data.mySections;
                document.getElementById('todayPresent').textContent = data.todayPresent;
                document.getElementById('attendanceRate').textContent = data.attendanceRate;
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 300000); // 5 minutes
</script>

@endsection
