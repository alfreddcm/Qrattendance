@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<div class="container-fluid py-4">
    @php
        $rawLogoPath = $school?->logo;
        $normalizedLogoPath = $rawLogoPath
            ? ltrim(preg_replace('#^/?storage/#', '', $rawLogoPath), '/')
            : null;

        $schoolLogoUrl = $normalizedLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedLogoPath)
            ? \Illuminate\Support\Facades\Storage::url($normalizedLogoPath)
            : null;
    @endphp

    <!-- Header Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="rounded-circle"
                                style="width: 80px; height: 80px; object-fit: cover;">
                            @else
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;">
                                <i class="fas fa-school text-white" style="font-size: 2rem;"></i>
                            </div>
                            @endif
                        </div>
                        <div class="col">
                            <h4 class="mb-1">{{ $school?->name ?? 'School' }}</h4>
                            <p class="text-secondary mb-0">Student Attendance Portal</p>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <h5 class="mb-1">{{ Auth::user()->name }}</h5>
                                <small class="text-secondary d-block">Welcome back</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-calendar-check text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Total School Days</small>
                            <h5 class="mb-0">{{ $attendanceSummary['totalSchoolDays'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Total Present</small>
                            <h5 class="mb-0">{{ $attendanceSummary['totalPresent'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="fas fa-user-xmark text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Total Absent</small>
                            <h5 class="mb-0">{{ $attendanceSummary['totalAbsent'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-percent text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Overall Attendance Rate</small>
                            <h5 class="mb-0">{{ number_format($attendanceSummary['attendanceRate'], 2) }}%</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm student-info-card">
                <div class="card-body p-3 p-md-4">
                    <div class="student-layout-grid">
                        <div class="student-context-panel">
                            <div class="context-heading">
                                <i class="fas fa-calendar-check me-2"></i>
                                {{ $schoolYear?->name ?? $schoolYear?->school_year ?? 'Not Set' }}
                            </div>
                            <div class="context-school">
                                <i class="fas fa-school me-2"></i>
                                {{ $school?->name ?? 'N/A' }}
                            </div>
                            <div class="context-meta-row">
                                <div class="context-meta-box">
                                    <span class="context-meta-label">Year</span>
                                    <span class="context-meta-value">{{ $schoolYear?->name ?? $schoolYear?->school_year ?? 'Not Set' }}</span>
                                </div>
                                <div class="context-meta-box">
                                    <span class="context-meta-label">Period</span>
                                    <span class="context-meta-value">{{ $attendanceDateRange }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="student-info-grid student-info-grid-compact">
                            <div class="student-info-item">
                                <span class="student-info-label">Student ID</span>
                                <span class="student-info-value">{{ $student->id_no }}</span>
                            </div>
                            <div class="student-info-item">
                                <span class="student-info-label">LRN / Code</span>
                                <span class="student-info-value">{{ $student->stud_code ?? 'N/A' }}</span>
                            </div>
                            <div class="student-info-item">
                                <span class="student-info-label">Section</span>
                                <span class="student-info-value">{{ $section?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="student-info-item">
                                <span class="student-info-label">Adviser</span>
                                <span class="student-info-value">{{ $section?->teacher?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        Today's Attendance
                    </h5>
                </div>
                <div class="card-body">
                    @if($todayAttendance)
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <small class="text-secondary">Attendance Monitoring (Today)</small>
                        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                            {{ $todayAttendance->attendance_status }}
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Morning In -->
                        <div class="col-md-6 col-lg-3">
                            <div class="attendance-box p-3 border rounded text-center h-100">
                                <small class="text-secondary d-block mb-2">Morning In</small>
                                @if($todayAttendance->time_in_am)
                                <h5 class="text-success mb-0">
                                    <i class="fas fa-arrow-right-to-bracket me-1"></i>
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_in_am)->format('g:i A') }}
                                </h5>
                                @else
                                <h5 class="text-danger mb-0">
                                    <i class="fas fa-clock me-1"></i>
                                    No Entry
                                </h5>
                                @endif
                            </div>
                        </div>

                        <!-- Morning Out -->
                        <div class="col-md-6 col-lg-3">
                            <div class="attendance-box p-3 border rounded text-center h-100">
                                <small class="text-secondary d-block mb-2">Morning Out</small>
                                @if($todayAttendance->time_out_am)
                                <h5 class="text-info mb-0">
                                    <i class="fas fa-arrow-right-from-bracket me-1"></i>
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_out_am)->format('g:i A') }}
                                </h5>
                                @else
                                <h5 class="text-secondary mb-0">
                                    <i class="fas fa-clock me-1"></i>
                                    -
                                </h5>
                                @endif
                            </div>
                        </div>

                        <!-- PM In -->
                        <div class="col-md-6 col-lg-3">
                            <div class="attendance-box p-3 border rounded text-center h-100">
                                <small class="text-secondary d-block mb-2">PM In</small>
                                @if($todayAttendance->time_in_pm)
                                <h5 class="text-success mb-0">
                                    <i class="fas fa-arrow-right-to-bracket me-1"></i>
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_in_pm)->format('g:i A') }}
                                </h5>
                                @else
                                <h5 class="text-secondary mb-0">
                                    <i class="fas fa-clock me-1"></i>
                                    -
                                </h5>
                                @endif
                            </div>
                        </div>

                        <!-- PM Out -->
                        <div class="col-md-6 col-lg-3">
                            <div class="attendance-box p-3 border rounded text-center h-100">
                                <small class="text-secondary d-block mb-2">PM Out</small>
                                @if($todayAttendance->time_out_pm)
                                <h5 class="text-info mb-0">
                                    <i class="fas fa-arrow-right-from-bracket me-1"></i>
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_out_pm)->format('g:i A') }}
                                </h5>
                                @else
                                <h5 class="text-secondary mb-0">
                                    <i class="fas fa-clock me-1"></i>
                                    -
                                </h5>
                                @endif
                            </div>
                        </div>
                    </div>

                    @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        No attendance record for today yet.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Trends -->
    <div class="row mb-4 g-3">
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100 trend-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Today Time Map
                    </h5>
                    <small class="text-secondary">AM and PM in/out intervals for today</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap chart-wrap-sm">
                        <canvas id="todayAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100 trend-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-success"></i>
                        Weekly Time Map
                    </h5>
                    <small class="text-secondary">Attendance times mapped per day for the last 7 days</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="weeklyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100 trend-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2 text-warning"></i>
                        Monthly Time Map
                    </h5>
                    <small class="text-secondary">Attendance times mapped per day for the last 30 days</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="monthlyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-history fa-3x text-primary mb-3"></i>
                    <h6 class="card-title">Attendance History</h6>
                    <p class="card-text text-secondary small">View your attendance records</p>
                    <a href="{{ route('student.attendance') }}" class="btn btn-sm btn-outline-primary">View History</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-3x text-success mb-3"></i>
                    <h6 class="card-title">My Account</h6>
                    <p class="card-text text-secondary small">Update your profile</p>
                    <a href="{{ route('student.account') }}" class="btn btn-sm btn-outline-success">Edit Account</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                    <h6 class="card-title">Sign Out</h6>
                    <p class="card-text text-secondary small">Log out from your account</p>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sign Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.attendance-box {
    background: #f8fafc;
    transition: all 0.3s ease;
}

.student-info-card {
    border-radius: 16px;
    overflow: hidden;
}

.student-layout-grid {
    display: grid;
    grid-template-columns: 1.25fr 2fr;
    gap: 14px;
    align-items: stretch;
}

.student-context-panel {
    background: #1e3a8a;
    border-radius: 14px;
    padding: 14px;
    color: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.context-heading {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
}

.context-school {
    background: #3153ad;
    border-radius: 10px;
    padding: 8px 10px;
    font-weight: 600;
    font-size: 0.95rem;
}

.context-meta-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.context-meta-box {
    background: #2749a1;
    border-radius: 10px;
    padding: 8px 10px;
}

.context-meta-label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.9;
}

.context-meta-value {
    display: block;
    font-weight: 700;
    font-size: 1rem;
}

.student-info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.student-info-item {
    background: #ffffff;
    border: 1px solid #e9edf3;
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.student-info-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6c757d;
    margin-bottom: 4px;
}

.student-info-value {
    font-size: 0.98rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.25;
}

.attendance-box:hover {
    background: #f1f5f9;
}

.trend-card {
    border-radius: 18px;
    overflow: hidden;
}

.chart-wrap {
    position: relative;
    width: 100%;
    min-height: 280px;
}

.chart-wrap-sm {
    min-height: 220px;
}

@media (max-width: 767.98px) {
    .chart-wrap {
        min-height: 240px;
    }

    .chart-wrap-sm {
        min-height: 200px;
    }

    .student-layout-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .context-heading {
        font-size: 1.5rem;
    }

    .student-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {
    .student-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const todayData = @json($attendanceCharts['today']);
        const weeklyData = @json($attendanceCharts['weekly']);
        const monthlyData = @json($attendanceCharts['monthly']);

        const formatMinutes = function (value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            const totalMinutes = Number(value);
            if (Number.isNaN(totalMinutes)) {
                return '';
            }

            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            const suffix = hours >= 12 ? 'PM' : 'AM';
            const normalizedHours = hours % 12 || 12;

            return `${normalizedHours}:${String(minutes).padStart(2, '0')} ${suffix}`;
        };

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                    }
                }
            }
        };

        const todayCanvas = document.getElementById('todayAttendanceChart');
        if (todayCanvas) {
            new Chart(todayCanvas, {
                type: 'line',
                data: todayData,
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            min: 0,
                            max: 1440,
                            ticks: {
                                callback: function (value) {
                                    return formatMinutes(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        const weeklyCanvas = document.getElementById('weeklyAttendanceChart');
        if (weeklyCanvas) {
            new Chart(weeklyCanvas, {
                type: 'line',
                data: weeklyData,
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            min: 0,
                            max: 1440,
                            ticks: {
                                callback: function (value) {
                                    return formatMinutes(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        const monthlyCanvas = document.getElementById('monthlyAttendanceChart');
        if (monthlyCanvas) {
            new Chart(monthlyCanvas, {
                type: 'line',
                data: monthlyData,
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            min: 0,
                            max: 1440,
                            ticks: {
                                callback: function (value) {
                                    return formatMinutes(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection