@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1">
                <i class="fas fa-user-circle me-2 text-primary"></i> My Account
            </h2>
            <p class="text-muted">View and manage your account settings</p>
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary btn-sm float-end">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <strong>Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2 text-primary"></i> Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Name</label>
                        <p class="form-control-plaintext">{{ $student->name }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Student ID (LRN)</label>
                        <p class="form-control-plaintext">{{ $student->id_no }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Student Code</label>
                        <p class="form-control-plaintext">{{ $student->stud_code ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Gender</label>
                        <p class="form-control-plaintext">
                            @if($student->gender)
                                {{ ucfirst($student->gender) }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Age</label>
                        <p class="form-control-plaintext">{{ $student->age ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-uppercase">Address</label>
                        <p class="form-control-plaintext">{{ $student->address ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-phone me-2 text-primary"></i> Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Phone Number</label>
                        <p class="form-control-plaintext">{{ $student->cp_no ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Emergency Contact Person</label>
                        <p class="form-control-plaintext">{{ $student->contact_person_name ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Relationship</label>
                        <p class="form-control-plaintext">{{ $student->contact_person_relationship ?? 'N/A' }}</p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-uppercase">Emergency Contact Number</label>
                        <p class="form-control-plaintext">{{ $student->contact_person_contact ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2 text-primary"></i> Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('student.account.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Current Password</label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required placeholder="Enter your current password">
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">New Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Enter new password (min. 8 characters)">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required placeholder="Confirm your new password">
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-lock me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section Information -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap me-2 text-primary"></i> Academic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Section</label>
                        <p class="form-control-plaintext">
                            {{ $student->section->name ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Grade Level</label>
                        <p class="form-control-plaintext">
                            {{ $student->section->gradelevel ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-uppercase">Class Adviser</label>
                        <p class="form-control-plaintext">
                            {{ $student->section->teacher->name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control-plaintext {
        padding: 0.375rem 0;
        border: none;
        font-weight: 500;
        color: #2d3436;
    }

    .card {
        border: 1px solid #e5e9f1;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .card:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    }

    .card-header {
        border-radius: 12px 12px 0 0;
    }
</style>
@endsection
