@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @if($school && $school->logo)
                            <img src="{{ asset('storage/' . $school->logo) }}" alt="School Logo" class="rounded-circle"
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
                                <small class="text-secondary">Welcome back</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Info Cards -->
    <div class="row mb-4">
        <!-- Student ID Card -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-id-card text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Student ID</small>
                            <h6 class="mb-0">{{ $student->id_no }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LRN Card -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-barcode text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">LRN / Code</small>
                            <h6 class="mb-0">{{ $student->stud_code ?? 'N/A' }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Card -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-users text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Section</small>
                            <h6 class="mb-0">{{ $section?->name ?? 'N/A' }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adviser Card -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-chalkboard-user text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <small class="text-secondary">Adviser</small>
                            <h6 class="mb-0">{{ $section?->teacher?->name ?? 'N/A' }}</h6>
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
                    <div class="row">
                        <!-- Morning In -->
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="attendance-box p-3 border rounded text-center">
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
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="attendance-box p-3 border rounded text-center">
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
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="attendance-box p-3 border rounded text-center">
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
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="attendance-box p-3 border rounded text-center">
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

                    <div class="row mt-3">
                        <div class="col-12">
                            <small class="text-secondary">
                                Status: <strong>{{ $todayAttendance->attendance_status }}</strong>
                            </small>
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
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafb 100%);
    transition: all 0.3s ease;
}

.attendance-box:hover {
    background: linear-gradient(135deg, #ececf1 0%, #f1f3f5 100%);
}
</style>
@endsection