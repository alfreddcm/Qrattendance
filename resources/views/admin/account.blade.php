@extends('admin.sidebar')
@section('title', 'Manage Account')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center " style="margin-left: 1rem;" >
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">⚙️</span>
                Account Settings
            </h4>
            <p class="subtitle fs-6 mb-0">Manage your admin profile and security settings</p>
        </div>
    </div>
</div>

<div class="container" style="max-width: 600px;">

    <!-- Success/Error Alerts -->
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

    <!-- Account Info Card -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-primary text-white p-2">
            <h6 class="mb-0 fs-6">
                <i class="fas fa-user me-1"></i>
                Profile Information
            </h6>
        </div>
        <div class="card-body p-2">
            <form method="POST" action="{{ route('admin.account.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-2">
                    <label for="name" class="form-label fs-6">Name</label>
                    <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                        id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label for="username" class="form-label fs-6">Username</label>
                    <input type="text" class="form-control form-control-sm @error('username') is-invalid @enderror"
                        id="username" name="username" value="{{ old('username', auth()->user()->username) }}" required>
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label for="email" class="form-label fs-6">Email</label>
                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                        id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label for="phone_number" class="form-label fs-6">Phone Number</label>
                    <input type="text" class="form-control form-control-sm @error('phone_number') is-invalid @enderror"
                        id="phone_number" name="phone_number" value="{{ old('phone_number', auth()->user()->phone_number) }}">
                    @error('phone_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Change Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark p-2">
            <h6 class="mb-0 fs-6">
                <i class="fas fa-lock me-1"></i>
                Change Password
            </h6>
        </div>
        <div class="card-body p-2">
            <form method="POST" action="{{ route('admin.account.password') }}">
                @csrf
                @method('PUT')
                <div class="mb-2">
                    <label for="current_password" class="form-label fs-6">Current Password</label>
                    <input type="password" class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                        id="current_password" name="current_password" required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label for="new_password" class="form-label fs-6">New Password</label>
                    <input type="password" class="form-control form-control-sm @error('new_password') is-invalid @enderror"
                        id="new_password" name="new_password" minlength="8" required>
                    @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text fs-7">Password must be at least 8 characters long.</div>
                </div>
                <div class="mb-2">
                    <label for="new_password_confirmation" class="form-label fs-6">Confirm New Password</label>
                    <input type="password" class="form-control form-control-sm"
                        id="new_password_confirmation" name="new_password_confirmation" minlength="8" required>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-key me-1"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Information Display -->
    <div class="card mt-3 shadow-sm">
        <div class="card-header bg-info text-white p-2">
            <h6 class="mb-0 fs-6">
                <i class="fas fa-info-circle me-1"></i>
                Account Information
            </h6>
        </div>
        <div class="card-body p-2">
            <div class="row">
                <div class="col-sm-4">
                    <strong class="fs-6">Role:</strong>
                </div>
                <div class="col-sm-8">
                    <span class="badge bg-danger fs-7">{{ ucfirst(auth()->user()->role) }}</span>
                </div>
            </div>
            <hr class="my-1">
            <div class="row">
                <div class="col-sm-4">
                    <strong class="fs-6">Created:</strong>
                </div>
                <div class="col-sm-8">
                    <span class="fs-6">{{ auth()->user()->created_at->format('F j, Y') }}</span>
                </div>
            </div>
            <hr class="my-1">
            <div class="row">
                <div class="col-sm-4">
                    <strong class="fs-6">Last Updated:</strong>
                </div>
                <div class="col-sm-8">
                    <span class="fs-6">{{ auth()->user()->updated_at->format('F j, Y g:i A') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="alert alert-info mt-3" role="alert">
        <i class="fas fa-shield-alt me-2"></i>
        <strong>Security Notice:</strong> As an administrator, your account has elevated privileges. 
        Please use a strong password and keep your account information secure.
    </div>

</div>

<style>
    .sticky-header {
        background: #f8fffe;
        padding: 20px 0 10px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 999;
    }

    .subtitle {
        color: #6c757d;
    }

    .fs-7 {
        font-size: 0.8rem;
    }

    .card {
        border: none;
        border-radius: 8px;
    }

    .card-header {
        border-radius: 8px 8px 0 0 !important;
        border-bottom: 1px solid rgba(0,0,0,.125);
    }

    .form-control-sm {
        border-radius: 4px;
    }

    .btn-sm {
        border-radius: 4px;
        font-size: 0.875rem;
    }

    .alert {
        border-radius: 6px;
        border: none;
    }

    .badge {
        font-size: 0.8em;
    }

    hr {
        margin: 0.5rem 0;
        opacity: 0.3;
    }
</style>

@endsection
