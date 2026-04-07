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
            @unless($requiresPasswordChange)
                <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary btn-sm float-end">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            @endunless
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
        <div class="col-md-6 mb-4">
            <div class="password-panel h-100">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="password-icon me-3">
                            <i class="fas fa-key"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Change Password</h5>
                            <p class="text-muted mb-0">Open the password modal to update your login credentials.</p>
                        </div>
                    </div>

                    @if($requiresPasswordChange)
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You are still using your temporary LRN password. Set a new password to continue.
                        </div>
                    @endif

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#passwordModal">
                        <i class="fas fa-lock me-1"></i>
                        {{ $requiresPasswordChange ? 'Set New Password' : 'Change Password' }}
                    </button>
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

    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true" data-auto-show="{{ $requiresPasswordChange || $errors->hasAny(['current_password', 'password', 'password_confirmation']) ? '1' : '0' }}" @if($requiresPasswordChange) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="passwordModalLabel">
                        <i class="fas fa-key me-2"></i>
                        {{ $requiresPasswordChange ? 'Set New Password' : 'Change Password' }}
                    </h5>
                    @unless($requiresPasswordChange)
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endunless
                </div>
                <div class="modal-body p-4">
                    @if($requiresPasswordChange)
                        <div class="alert alert-info">
                            <i class="fas fa-shield-alt me-2"></i>
                            You logged in with your temporary LRN password. Set a new password now to continue using your account.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('student.account.password') }}">
                        @csrf
                        @method('PUT')

                        @unless($requiresPasswordChange)
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-uppercase">Current Password</label>
                                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Enter your current password">
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @endunless

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
                            <i class="fas fa-lock me-1"></i>
                            {{ $requiresPasswordChange ? 'Save New Password' : 'Update Password' }}
                        </button>
                    </form>
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

    .password-panel {
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(13, 110, 253, 0.04), rgba(13, 110, 253, 0.01));
        padding: 1.5rem;
        display: flex;
        align-items: center;
    }

    .password-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex: 0 0 auto;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordModal = document.getElementById('passwordModal');

        if (passwordModal && passwordModal.dataset.autoShow === '1') {
            const modal = new bootstrap.Modal(passwordModal);
            modal.show();
        }
    });
</script>
@endsection
