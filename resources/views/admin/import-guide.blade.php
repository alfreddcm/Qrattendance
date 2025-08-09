@extends('layouts.app')

@section('title', 'Import Guide - Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Student Import Guide
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Overview -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Overview</h5>
                        <p class="mb-0">This guide will help you properly format and import student data into the system using Excel or CSV files.</p>
                    </div>

                    <!-- Step by Step Guide -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-list-ol me-2"></i>Step-by-Step Process</h5>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">
                                    <strong>Download Template</strong>
                                    <p class="mb-0 text-muted">Get the properly formatted template file with headers and examples.</p>
                                </li>
                                <li class="list-group-item">
                                    <strong>Fill in Data</strong>
                                    <p class="mb-0 text-muted">Add your student information following the format guidelines.</p>
                                </li>
                                <li class="list-group-item">
                                    <strong>Validate Data</strong>
                                    <p class="mb-0 text-muted">Check that all required fields are completed and IDs are valid.</p>
                                </li>
                                <li class="list-group-item">
                                    <strong>Upload File</strong>
                                    <p class="mb-0 text-muted">Upload your completed file and preview before final import.</p>
                                </li>
                            </ol>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-table me-2"></i>Required Columns</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Column</th>
                                            <th>Required</th>
                                            <th>Format</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>id_no</td>
                                            <td><span class="badge bg-warning">Optional</span></td>
                                            <td>Unique identifier</td>
                                        </tr>
                                        <tr>
                                            <td>name</td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Full name</td>
                                        </tr>
                                        <tr>
                                            <td>gender</td>
                                            <td><span class="badge bg-warning">Optional</span></td>
                                            <td>M or F</td>
                                        </tr>
                                        <tr>
                                            <td>age</td>
                                            <td><span class="badge bg-warning">Optional</span></td>
                                            <td>Number</td>
                                        </tr>
                                        <tr>
                                            <td>school_id</td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Valid school ID</td>
                                        </tr>
                                        <tr>
                                            <td>teacher_id</td>
                                            <td><span class="badge bg-warning">Optional</span></td>
                                            <td>Valid teacher ID</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Available Schools -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-school me-2"></i>Available Schools</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>School ID</th>
                                            <th>School Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($schools as $school)
                                            <tr>
                                                <td><code>{{ $school->id }}</code></td>
                                                <td>{{ $school->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-chalkboard-teacher me-2"></i>Available Teachers</h5>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-secondary sticky-top">
                                        <tr>
                                            <th>Teacher ID</th>
                                            <th>Name</th>
                                            <th>School</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($schools as $school)
                                            @foreach($school->users->where('role', 'teacher') as $teacher)
                                                <tr>
                                                    <td><code>{{ $teacher->id }}</code></td>
                                                    <td>{{ $teacher->name }}</td>
                                                    <td><small class="text-muted">{{ $school->name }}</small></td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Import Rules -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Important Rules & Notes</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6>File Format:</h6>
                                        <ul class="mb-0">
                                            <li>Supported: CSV, Excel (.xls, .xlsx)</li>
                                            <li>Maximum file size: 5MB</li>
                                            <li>First row must contain headers</li>
                                            <li>Remove instruction rows before import</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-danger">
                                        <h6>Data Validation:</h6>
                                        <ul class="mb-0">
                                            <li>Name and School ID are required</li>
                                            <li>School ID and Teacher ID must exist</li>
                                            <li>Gender: Use "M" or "F" only</li>
                                            <li>Age must be a number</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="{{ route('admin.students.downloadTemplate') }}" class="btn btn-success btn-lg me-3">
                            <i class="fas fa-download me-2"></i>Download Template
                        </a>
                        <a href="{{ route('admin.students.downloadSampleData') }}" class="btn btn-info btn-lg me-3">
                            <i class="fas fa-eye me-2"></i>View Sample Data
                        </a>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.close()">
                            <i class="fas fa-times me-2"></i>Close Guide
                        </button>
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
    }
    
    .list-group-numbered .list-group-item {
        border-radius: 8px;
        margin-bottom: 0.5rem;
        border: 1px solid #dee2e6;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
        border-radius: 12px;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
    }
    
    .alert {
        border-radius: 8px;
        border: none;
    }
    
    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }
</style>
@endsection
