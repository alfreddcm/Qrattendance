@extends('teacher.sidebar')
@section('title', 'Manage Account')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-cog me-2"></i>
                Account Settings
            </h4>
            <p class="subtitle fs-6 mb-0">Manage your profile and security settings</p>
        </div>
    </div>
</div>

<div class="px-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('teacher.account.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <label for="name" class="form-label small mb-1">Name</label>
                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label for="email" class="form-label small mb-1">Email</label>
                            <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label for="phone_number" class="form-label small mb-1">Phone Number</label>
                            <input type="number" class="form-control form-control-sm @error('phone_number') is-invalid @enderror"
                                id="phone_number" name="phone_number" value="{{ old('phone_number', auth()->user()->phone_number) }}" min="0" placeholder="09123456789">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label for="position" class="form-label small mb-1">Position</label>
                            <input type="text" class="form-control form-control-sm @error('position') is-invalid @enderror"
                                id="position" name="position" value="{{ old('position', auth()->user()->position) }}">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1">Assigned Sections</label>
                            <div class="form-control form-control-sm bg-light" style="min-height: 38px; padding: 8px;">
                                @if(auth()->user()->sections && auth()->user()->sections->count() > 0)
                                    @foreach(auth()->user()->sections as $section)
                                        <span class="me-1 mb-1">
                                            {{ $section->name }} (Grade {{ $section->gradelevel }})
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No sections assigned</span>
                                @endif
                            </div>
                            <div class="form-text small">Section assignments are managed by administrators</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('teacher.account.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <label for="current_password" class="form-label small mb-1">Current Password</label>
                            <input type="password" class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                                id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label for="new_password" class="form-label small mb-1">New Password</label>
                            <input type="password" class="form-control form-control-sm @error('new_password') is-invalid @enderror"
                                id="new_password" name="new_password" minlength="8" required>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small">At least 8 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label small mb-1">Confirm New Password</label>
                            <input type="password" class="form-control form-control-sm"
                                id="new_password_confirmation" name="new_password_confirmation" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fas fa-key me-1"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted small" width="120">Role:</td>
                            <td><span class="badge bg-success">{{ ucfirst(auth()->user()->role) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Created:</td>
                            <td class="small">{{ auth()->user()->created_at->format('F j, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Last Updated:</td>
                            <td class="small">{{ auth()->user()->updated_at->format('F j, Y g:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-secondary text-white py-2">
                    <h6 class="mb-0"><i class="fas fa-user-shield me-2"></i>Administrator Contact</h6>
                </div>
                <div class="card-body p-3">
                    @php
                        $admin = \App\Models\User::where('role', 'admin')->first();
                    @endphp
                    @if($admin)
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted small" width="120">Name:</td>
                                <td class="small">{{ $admin->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Email:</td>
                                <td class="small">{{ $admin->email }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Phone:</td>
                                <td class="small">{{ $admin->phone_number ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="small text-muted mb-0">No administrator contact information available.</p>
                    @endif
                </div>
            </div>

            <div class="alert alert-info mt-3 py-2" role="alert">
                <div class="d-flex">
                    <i class="fas fa-info-circle me-2 mt-1"></i>
                    <div>
                        <strong class="small">Need Help?</strong>
                        <p class="small mb-0">Contact the administrator above for account issues, section assignments, or other assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
