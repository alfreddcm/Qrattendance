@extends('layouts.app')

@section('title', 'Import Preview - Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Import Preview
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Summary Section -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-list me-2"></i>Total Records</h6>
                                <h4 class="mb-0">{{ $totalRows }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check me-2"></i>Valid Records</h6>
                                <h4 class="mb-0">{{ count($data) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-{{ count($errors) > 0 ? 'danger' : 'success' }}">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Errors</h6>
                                <h4 class="mb-0">{{ count($errors) }}</h4>
                            </div>
                        </div>
                    </div>

                    @if(count($errors) > 0)
                        <!-- Errors Section -->
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Import Errors Found</h6>
                            <ul class="mb-0">
                                @foreach($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(count($data) > 0)
                        <!-- Preview Table -->
                        <div class="table-responsive">
                            <h6><i class="fas fa-table me-2"></i>Preview of Valid Records (First 10)</h6>
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID No</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>School ID</th>
                                        <th>Teacher ID</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($data, 0, 10) as $student)
                                        <tr>
                                            <td>{{ $student['id_no'] }}</td>
                                            <td>{{ $student['name'] }}</td>
                                            <td>
                                                @if($student['gender'] == 'M')
                                                    <span class="badge bg-primary">Male</span>
                                                @elseif($student['gender'] == 'F')
                                                    <span class="badge bg-pink">Female</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $student['gender'] }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $student['age'] }}</td>
                                            <td>{{ $student['school_id'] }}</td>
                                            <td>{{ $student['user_id'] ?: 'N/A' }}</td>
                                            <td>{{ $student['address'] }}</td>
                                            <td>{{ $student['cp_no'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            @if(count($data) > 10)
                                <p class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Showing first 10 records. Total {{ count($data) }} records ready for import.
                                </p>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <form action="{{ route('admin.students.import') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="confirm_import" value="1">
                                        <!-- You'd need to store the processed data in session or temp file -->
                                        <button type="submit" class="btn btn-success btn-lg me-2" 
                                                @if(count($errors) > 0) disabled title="Fix errors before importing" @endif>
                                            <i class="fas fa-check me-2"></i>Confirm Import ({{ count($data) }} records)
                                        </button>
                                    </form>
                                    
                                    <a href="{{ route('admin.manage-students') }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-outline-primary" onclick="window.history.back()">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>No Valid Records Found</h5>
                            <p>Please check your file format and try again.</p>
                            <a href="{{ route('admin.manage-students') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Students
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-pink {
        background-color: #e91e63 !important;
    }
    
    .card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
        border-radius: 15px;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
    }
    
    .alert {
        border-radius: 8px;
        border: none;
    }
</style>
@endsection
