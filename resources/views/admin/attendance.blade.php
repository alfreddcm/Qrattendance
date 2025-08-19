@extends('admin/sidebar')
@section('title', 'Attendance Overview')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">📊</span>
                    Attendance Overview
                </h4>
                <p class="subtitle mb-0">Monitor attendance statistics and trends</p>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $totalAttendanceToday ?? 0 }}</div>
                                <small>Present Today</small>
                            </div>
                            <i class="fas fa-user-check fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $totalStudents ?? 0 }}</div>
                                <small>Total Students</small>
                            </div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ $attendanceRate ?? 0 }}%</div>
                                <small>Attendance Rate</small>
                            </div>
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 mb-0">{{ now()->format('M d, Y') }}</div>
                                <small>Today's Date</small>
                            </div>
                            <i class="fas fa-calendar fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock me-1"></i>Recent Attendance (Today)
            </div>
            <div class="card-body">
                @if(isset($recentAttendance) && $recentAttendance->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Section</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAttendance as $attendance)
                                <tr>
                                    <td>
                                        <strong>{{ $attendance->student->name ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $attendance->student->id_no ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($attendance->student->section)
                                            <span class="badge bg-info">{{ $attendance->student->section->name }} - Grade {{ $attendance->student->section->gradelevel }}</span>
                                        @else
                                            <span class="badge bg-secondary">No Section</span>
                                        @endif
                                    </td>
                                    <td>{{ $attendance->created_at->format('h:i A') }}</td>
                                    <td>
                                        <span class="badge bg-success">Present</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-2"></i>
                        <h6 class="text-muted">No attendance records for today</h6>
                        <p class="text-muted small">Attendance records will appear here as students check in.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
