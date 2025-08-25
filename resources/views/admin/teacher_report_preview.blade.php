@if(isset($records) && count($records) > 0)
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-table me-2"></i>
                @if($type === 'daily')
                    Daily Attendance Report
                @elseif($type === 'monthly')
                    Monthly Attendance Summary
                @elseif($type === 'quarterly')
                    Quarterly Attendance Report
                @endif
                <span class="badge bg-primary ms-2">{{ count($records) }} Records</span>
            </h6>
            <div class="text-muted small">
                Generated: {{ now()->format('M d, Y h:i A') }}
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark sticky-top">
                    <tr>
                        @if($type === 'daily')
                            <th width="8%">Date</th>
                            <th width="12%">ID No</th>
                            <th width="20%">Student Name</th>
                            <th width="10%">Grade</th>
                            <th width="10%">Section</th>
                            <th width="8%">AM In</th>
                            <th width="8%">AM Out</th>
                            <th width="8%">PM In</th>
                            <th width="8%">PM Out</th>
                            <th width="8%">Status</th>
                        @elseif($type === 'monthly')
                            <th width="12%">ID No</th>
                            <th width="25%">Student Name</th>
                            <th width="10%">Grade</th>
                            <th width="10%">Section</th>
                            <th width="8%">Total Days</th>
                            <th width="8%">Present</th>
                            <th width="8%">Absent</th>
                            <th width="8%">Partial</th>
                            <th width="11%">Remarks</th>
                        @elseif($type === 'quarterly')
                            <th width="12%">ID No</th>
                            <th width="25%">Student Name</th>
                            <th width="10%">Grade</th>
                            <th width="10%">Section</th>
                            <th width="8%">Total Days</th>
                            <th width="8%">Present</th>
                            <th width="8%">Absent</th>
                            <th width="8%">Partial</th>
                            <th width="11%">Remarks</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            @if($type === 'daily')
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('M d') }}</td>
                                <td><small class="text-muted">{{ $record->id_no }}</small></td>
                                <td>
                                    <div class="fw-medium">{{ $record->name }}</div>
                                    @if(isset($record->teacher_name))
                                        <small class="text-muted">Teacher: {{ $record->teacher_name }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-info">{{ $record->grade_level }}</span></td>
                                <td><span class="badge bg-secondary">{{ $record->section }}</span></td>
                                <td>
                                    @if($record->am_in)
                                        <span class="badge bg-success">{{ $record->am_in }}</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->am_out)
                                        <span class="badge bg-warning">{{ $record->am_out }}</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->pm_in)
                                        <span class="badge bg-success">{{ $record->pm_in }}</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->pm_out)
                                        <span class="badge bg-warning">{{ $record->pm_out }}</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->status === 'Present')
                                        <span class="badge bg-success">✓ Present</span>
                                    @elseif($record->status === 'Partial')
                                        <span class="badge bg-warning">~ Partial</span>
                                    @elseif($record->status === 'Absent')
                                        <span class="badge bg-danger">✗ Absent</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $record->status }}</span>
                                    @endif
                                </td>
                            @elseif($type === 'monthly' || $type === 'quarterly')
                                <td><small class="text-muted">{{ $record->id_no }}</small></td>
                                <td>
                                    <div class="fw-medium">{{ $record->name }}</div>
                                    @if(isset($record->teacher_name))
                                        <small class="text-muted">Teacher: {{ $record->teacher_name }}</small>
                                    @endif
                                </td>
                                <td><span class="badge bg-info">{{ $record->grade_level }}</span></td>
                                <td><span class="badge bg-secondary">{{ $record->section }}</span></td>
                                <td><span class="badge bg-primary">{{ $record->total_day }}</span></td>
                                <td><span class="badge bg-success">{{ $record->present }}</span></td>
                                <td><span class="badge bg-danger">{{ $record->absent }}</span></td>
                                <td><span class="badge bg-warning">{{ $record->partial }}</span></td>
                                <td>
                                    @if($record->remarks === 'Good')
                                        <span class="badge bg-success">{{ $record->remarks }}</span>
                                    @elseif($record->remarks === 'Poor')
                                        <span class="badge bg-warning">{{ $record->remarks }}</span>
                                    @elseif($record->remarks === 'Bad')
                                        <span class="badge bg-danger">{{ $record->remarks }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $record->remarks }}</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light">
        <div class="row text-center">
            @if($type === 'daily')
                @php
                    $presentCount = collect($records)->where('status', 'Present')->count();
                    $absentCount = collect($records)->where('status', 'Absent')->count();
                    $partialCount = collect($records)->where('status', 'Partial')->count();
                    $totalCount = count($records);
                @endphp
                <div class="col-md-3">
                    <div class="text-success fw-bold">{{ $presentCount }}</div>
                    <small class="text-muted">Present</small>
                </div>
                <div class="col-md-3">
                    <div class="text-warning fw-bold">{{ $partialCount }}</div>
                    <small class="text-muted">Partial</small>
                </div>
                <div class="col-md-3">
                    <div class="text-danger fw-bold">{{ $absentCount }}</div>
                    <small class="text-muted">Absent</small>
                </div>
                <div class="col-md-3">
                    <div class="text-primary fw-bold">{{ number_format($totalCount > 0 ? ($presentCount / $totalCount) * 100 : 0, 1) }}%</div>
                    <small class="text-muted">Attendance Rate</small>
                </div>
            @elseif($type === 'monthly' || $type === 'quarterly')
                @php
                    $totalDays = collect($records)->sum('total_day');
                    $totalPresent = collect($records)->sum('present');
                    $totalAbsent = collect($records)->sum('absent');
                    $totalPartial = collect($records)->sum('partial');
                    $attendanceRate = $totalDays > 0 ? ($totalPresent / $totalDays) * 100 : 0;
                @endphp
                <div class="col-md-3">
                    <div class="text-primary fw-bold">{{ $totalDays }}</div>
                    <small class="text-muted">Total Class Days</small>
                </div>
                <div class="col-md-3">
                    <div class="text-success fw-bold">{{ $totalPresent }}</div>
                    <small class="text-muted">Present Days</small>
                </div>
                <div class="col-md-3">
                    <div class="text-danger fw-bold">{{ $totalAbsent }}</div>
                    <small class="text-muted">Absent Days</small>
                </div>
                <div class="col-md-3">
                    <div class="text-primary fw-bold">{{ number_format($attendanceRate, 1) }}%</div>
                    <small class="text-muted">Overall Rate</small>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
