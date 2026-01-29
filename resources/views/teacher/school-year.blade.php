@extends('teacher/sidebar')
@section('title', 'Manage School Years & Sections')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-layer-group me-2"></i>
                School Year and Sections
            </h4>
            <p class="subtitle fs-6 mb-0">View school year information (Contact admin to create new school years)</p>
        </div>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>School Year Overview
                    </h5>
                </div>
                <div class="card-body">
                    @if($schoolYears->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-1"></i>
                            <h5 class="text-muted">No School Years Found</h5>
                            <p class="text-muted">Contact your administrator to create school years for your school.</p>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Only administrators can create new school years.
                            </div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>School Year</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schoolYears as $schoolYear)
                                        @php
                                            $today = \Carbon\Carbon::now();
                                            $startDate = \Carbon\Carbon::parse($schoolYear->start_date);
                                            $endDate = \Carbon\Carbon::parse($schoolYear->end_date);
                                            $isActive = $today->between($startDate, $endDate);
                                            $isPast = $today->greaterThan($endDate);
                                            $isFuture = $today->lessThan($startDate);
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $schoolYear->school_year_start }}–{{ $schoolYear->school_year_end }}</strong>
                                              
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($schoolYear->start_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($schoolYear->end_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                @if($schoolYear->status === 'inactive')
                                                    <span class="badge bg-warning">Inactive</span>
                                                @elseif($isActive)
                                                    <span class="badge bg-success">Ongoing</span>
                                                @elseif($isPast)
                                                    <span class="badge bg-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-info">Upcoming</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>To create new school years, please contact your administrator.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$schoolYears->isEmpty())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-layer-group me-2"></i>Section Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="sectionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Section Name</th>
                                    <th width="10%">Grade Level</th>
                                    <th width="12%">AM Time In</th>
                                    <th width="12%">AM Time Out</th>
                                    <th width="12%">PM Time In</th>
                                    <th width="12%">PM Time Out</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sectionsTableBody">
                                @if($sections && count($sections) > 0)
                                    @foreach($sections as $section)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $section->name }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">Grade {{ $section->gradelevel }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->am_time_in_start && $section->am_time_in_end)
                                                        {{ \Carbon\Carbon::parse($section->am_time_in_start)->format('g:i A') }} -
                                                        {{ \Carbon\Carbon::parse($section->am_time_in_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->am_time_out_start && $section->am_time_out_end)
                                                        {{ \Carbon\Carbon::parse($section->am_time_out_start)->format('g:i A') }} -
                                                        {{ \Carbon\Carbon::parse($section->am_time_out_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->pm_time_in_start && $section->pm_time_in_end)
                                                        {{ \Carbon\Carbon::parse($section->pm_time_in_start)->format('g:i A') }} -
                                                        {{ \Carbon\Carbon::parse($section->pm_time_in_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($section->pm_time_out_start && $section->pm_time_out_end)
                                                        {{ \Carbon\Carbon::parse($section->pm_time_out_start)->format('g:i A') }} -
                                                        {{ \Carbon\Carbon::parse($section->pm_time_out_end)->format('g:i A') }}
                                                    @else
                                                        --
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-info" onclick="viewSection({{ $section->id }})" title="View Section Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-layer-group me-2"></i>No sections found.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Sections help organize students and generate detailed analytics.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
let sections = @json($sections ?? []);

function viewSection(sectionId) {
    const section = sections.find(s => s.id == sectionId);
    if (!section) {
        alert('Section not found.');
        return;
    }
    alert('Section: ' + section.name + '\nGrade Level: ' + section.gradelevel);
}
</script>

@endsection
