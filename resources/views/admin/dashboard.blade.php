@extends('admin.sidebar')
@section('title', 'Admin Dashboard')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <i class="fas fa-home me-2"></i>
                Admin Dashboard
            </h2>
            <p class="subtitle">Welcome back! Here's your system overview</p>
        </div>
        <div class="page-actions">
        
        </div>
    </div>
</div>

<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main Statistics Cards -->
    <div class="row g-2 mb-3">
        <!-- Total Schools Card -->
        <div class="col-lg-4 col-md-6">
            <div class="card stats-card text-center shadow-sm h-100 border-primary">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="card-title text-primary mb-0 fs-6">Total Schools</h6>
                        <i class="fas fa-school text-primary"></i>
                    </div>
                    <h2 class="h3 text-primary">{{ $totalSchools ?? 0 }}</h2>
                    <small class="text-muted fs-6">Registered schools</small>
                </div>
            </div>
        </div>
        
        <!-- Total Teachers Card -->
        <div class="col-lg-4 col-md-6">
            <div class="card stats-card text-center shadow-sm h-100 border-success">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="card-title text-success mb-0 fs-6">Total Teachers</h6>
                        <i class="fas fa-chalkboard-teacher text-success"></i>
                    </div>
                    <h2 class="h3 text-success">{{ $totalTeachers ?? 0 }}</h2>
                    <small class="text-muted fs-6">Active teachers</small>
                </div>
            </div>
        </div>
        
        <!-- Total Students Card -->
        <div class="col-lg-4 col-md-6">
            <div class="card stats-card text-center shadow-sm h-100 border-info">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="card-title text-info mb-0 fs-6">Total Students</h6>
                        <i class="fas fa-users text-info"></i>
                    </div>
                    <h2 class="h3 text-info">{{ $totalStudents ?? 0 }}</h2>
                    <small class="text-muted fs-6">Enrolled students</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Schools List Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center p-2">
                    <h5 class="mb-0 fs-6">
                        <i class="fas fa-school me-2"></i>
                        Schools Management
                    </h5>
                    <span class="badge bg-primary fs-6">{{ $schools->count() }} schools</span>
                </div>
                <div class="card-body p-1">
                    @if($schools->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Logo</th>
                                        <th>School ID</th>
                                        <th>School Name</th>
                                        <th>Address</th>
                                        <th>Teachers</th>
                                        <th>Students</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schools as $school)
                                    <tr>
                                        <td>
                                            @if($school->logo)
                                                <img src="{{ asset('storage/' . $school->logo) }}" 
                                                     alt="{{ $school->name }} Logo" 
                                                     class="rounded-circle" 
                                                     style="width: 30px; height: 30px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                                     style="width: 30px; height: 30px; font-size: 11px;">
                                                    {{ substr($school->name, 0, 2) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info fs-6">{{ $school->school_id }}</span>
                                        </td>
                                        <td>
                                            <strong class="fs-6">{{ $school->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted fs-6">{{ Str::limit($school->address, 40) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">{{ $school->teachers_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6">{{ $school->students_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.edit-school', $school->id) }}" 
                                                   class="btn btn-sm btn-outline-primary px-2 py-1" 
                                                   title="Edit School">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="{{ route('admin.delete-school', $school->id) }}" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this school?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger px-2 py-1" 
                                                            title="Delete School">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-school fa-2x text-muted mb-2"></i>
                            <h5 class="text-muted fs-6">No schools found</h5>
                            <p class="text-muted fs-6">Start by adding your first school to the system.</p>
                            <a href="{{ route('admin.add-school') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add First School
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('admin.add-school') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-plus fa-2x mb-2 d-block"></i>
                                Add New School
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.manage-teachers') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                Manage Teachers
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.manage-students') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                Manage Students
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.attendance-reports') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        border-bottom: 1px solid #e9ecef;
    }
    
    .btn {
        border-radius: 6px;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
</style>

@endsection
