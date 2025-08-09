@extends('teacher/sidebar')
@section('title', 'Attendance Reports')
@section('content')

<div class="container-fluid">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2>
                    <span class="me-2">📊</span>
                    Attendance Reports
                </h2>
                <p class="subtitle">Generate and export attendance reports</p>
            </div>
           
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <span class="me-2">🔍</span>
                        Report Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="type" class="form-label fw-bold">
                                <i class="fas fa-filter me-1"></i>Report Type
                            </label>
                            <select name="type" id="type" class="form-select">
                                <option value="daily" {{ request('type', 'daily') == 'daily' ? 'selected' : '' }}>
                                    📅 Daily Report
                                </option>
                                <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>
                                    📊 Monthly Summary
                                </option>
                                <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>
                                    📈 Quarterly Tracking
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <div id="dateField" style="display:none;">
                                <label for="date" class="form-label fw-bold">
                                    <i class="fas fa-calendar me-1"></i>Select Date
                                </label>
                                <input type="date" name="date" id="date" value="{{ request('date', now()->toDateString()) }}" class="form-control">
                            </div>
                            <div id="monthField" style="display:none;">
                                <label for="month" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Select Month
                                </label>
                                <input type="month" name="month" id="month" value="{{ request('month', now()->format('Y-m')) }}" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="semester_id" class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-1"></i>Semester
                            </label>
                            <select name="semester_id" id="semester_id" class="form-select">
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                        {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div id="previewArea">
                @include('teacher.report_preview', ['records' => $records])
            </div>
        </div>
    </div>
    
    @if(isset($records) && count($records))
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>Export Options
                        </h5>
                        <form method="POST" action="{{ route('teacher.attendance.export.csv') }}">
                            @csrf
                            <input type="hidden" name="type" id="export_type" value="{{ request('type', 'daily') }}">
                            <input type="hidden" name="semester_id" id="export_semester_id" value="{{ request('semester_id') }}">
                            <input type="hidden" name="date" id="export_date" value="{{ request('date') }}">
                            <input type="hidden" name="month" id="export_month" value="{{ request('month') }}">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-csv me-2"></i>Export to CSV
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateFilterFields() {
    var type = $('#type').val();
    $('#dateField').hide();
    $('#monthField').hide();
    if(type === 'daily') {
        $('#dateField').show();
    } else if(type === 'monthly') {
        $('#monthField').show();
    }
}
$(function() {
    updateFilterFields();

    $('#type').on('change', function() {
        updateFilterFields();
    });

    $('#filterForm').on('change', 'select, input[type=date], input[type=month]', function() {
        // Update export form values
        $('#export_type').val($('#type').val());
        $('#export_semester_id').val($('#semester_id').val());
        $('#export_date').val($('#date').val());
        $('#export_month').val($('#month').val());
        
        $('#filterForm').submit();
    });

    // Initial update of export form values
    $('#export_type').val($('#type').val());
    $('#export_semester_id').val($('#semester_id').val());
    $('#export_date').val($('#date').val());
    $('#export_month').val($('#month').val());
});
</script>
@endsection