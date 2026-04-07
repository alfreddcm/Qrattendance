@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1">
                <i class="fas fa-calendar-check me-2 text-primary"></i> Attendance History
            </h2>
            <p class="text-muted">View and track your attendance records</p>
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary btn-sm float-end">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('student.attendance') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('student.attendance') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fw-semibold">Date</th>
                        <th class="fw-semibold">Day</th>
                        <th class="fw-semibold">Morning In</th>
                        <th class="fw-semibold">Morning Out</th>
                        <th class="fw-semibold">PM In</th>
                        <th class="fw-semibold">PM Out</th>
                        <th class="fw-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                        <tr>
                            <td>
                                <span class="fw-semibold">
                                    {{ $attendance->date->format('M d, Y') }}
                                </span>
                            </td>
                            <td>{{ $attendance->date->format('l') }}</td>
                            <td>
                                @if($attendance->time_in_am)
                                    <span class="badge bg-success">{{ $attendance->time_in_am }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->time_out_am)
                                    <span class="badge bg-info">{{ $attendance->time_out_am }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->time_in_pm)
                                    <span class="badge bg-success">{{ $attendance->time_in_pm }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($attendance->time_out_pm)
                                    <span class="badge bg-info">{{ $attendance->time_out_pm }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $morning_in = $attendance->time_in_am;
                                    $morning_out = $attendance->time_out_am;
                                    $pm_in = $attendance->time_in_pm;
                                    $pm_out = $attendance->time_out_pm;

                                    if ($morning_in && $morning_out && $pm_in && $pm_out) {
                                        $status = 'Full Day';
                                        $badge = 'bg-success';
                                    } elseif (($morning_in && $morning_out) || ($pm_in && $pm_out)) {
                                        $status = 'Partial';
                                        $badge = 'bg-warning';
                                    } elseif ($morning_in || $morning_out || $pm_in || $pm_out) {
                                        if ($morning_in && !$pm_in) {
                                            $status = 'Morning Only';
                                        } elseif ($pm_in && !$morning_in) {
                                            $status = 'Afternoon Only';
                                        } else {
                                            $status = 'Partial';
                                        }
                                        $badge = 'bg-info';
                                    } else {
                                        $status = 'Absent';
                                        $badge = 'bg-danger';
                                    }
                                @endphp
                                <span class="badge {{ $badge }}">{{ $status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox me-2"></i> No attendance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($attendances->count() > 0)
        <div class="d-flex justify-content-center mt-4">
            {{ $attendances->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<style>
    .table th {
        border-top: none;
        background-color: #f8f9fa;
    }

    .table tbody tr:hover {
        background-color: #f5f7fa;
    }

    .badge {
        font-weight: 500;
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
    }
</style>
@endsection
