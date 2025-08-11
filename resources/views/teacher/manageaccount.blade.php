@extends('teacher.sidebar')
@section('title', 'Manage Account')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">⚙️</span>
                Account Settings
            </h4>
            <p class="subtitle fs-6 mb-0">Manage your profile and security settings</p>
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
            <form method="POST" action="{{ route('teacher.account.update') }}">
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
                    <label for="email" class="form-label fs-6">Email address</label>
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
                <div class="mb-2">
                    <label for="position" class="form-label fs-6">Position</label>
                    <input type="text" class="form-control form-control-sm @error('position') is-invalid @enderror"
                        id="position" name="position" value="{{ old('position', auth()->user()->position) }}">
                    @error('position')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label for="section_name" class="form-label fs-6">Section Name</label>
                    <input type="text" class="form-control form-control-sm @error('section_name') is-invalid @enderror"
                        id="section_name" name="section_name" value="{{ old('section_name', auth()->user()->section_name) }}">
                    @error('section_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-2 py-1">Update Profile</button>
                <button type="button" class="btn btn-secondary btn-sm px-2 py-1 float-end" data-bs-toggle="modal" data-bs-target="#passwordModal">
                    Change Password
                </button>
            </form>
        </div>
    </div>

   
</div>

<!-- Password Change Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="passwordChangeForm" method="POST" action="{{ route('teacher.account.password') }}">
        @csrf
        @method('PUT')
        <div class="modal-content">
          <div class="modal-header p-2">
            <h6 class="modal-title fs-6" id="passwordModalLabel">Change Password</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-2">
                <div id="passwordModalAlert"></div>
                <div class="mb-2 position-relative">
                    <label for="current_password" class="form-label fs-6">Current Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                            id="current_password" name="current_password" required>
                        <span class="input-group-text bg-white" style="cursor:pointer;" onclick="togglePassword('current_password', this)">
                            <i class="bi bi-eye-slash" id="icon_current_password"></i>
                        </span>
                    </div>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control @error('new_password') is-invalid @enderror"
                            id="new_password" name="new_password" required>
                        <span class="input-group-text bg-white" style="cursor:pointer; padding-left: 0.75rem; padding-right: 0.75rem;" onclick="togglePassword('new_password', this)">
                            <i class="bi bi-eye-slash" id="icon_new_password" style="font-size:1.2em;"></i>
                        </span>
                    </div>
                    @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3 position-relative">
                    <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control @error('new_password_confirmation') is-invalid @enderror"
                            id="new_password_confirmation" name="new_password_confirmation" required>
                        <span class="input-group-text bg-white" style="cursor:pointer;" onclick="togglePassword('new_password_confirmation', this)">
                            <i class="bi bi-eye-slash" id="icon_new_password_confirmation"></i>
                        </span>
                    </div>
                    @error('new_password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </div>
    </form>
  </div>
</div>

{{-- Bootstrap Icons CDN --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
function togglePassword(fieldId, el) {
    const input = document.getElementById(fieldId);
    const icon = el.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
}
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#passwordChangeForm').on('submit', function(e) {
    e.preventDefault();
    var $form = $(this);
    var $alert = $('#passwordModalAlert');
    $alert.html('');
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();

    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        headers: {'X-CSRF-TOKEN': $('input[name="_token"]').val()},
        success: function(response) {
            $alert.html('<div class="alert alert-success">Password updated successfully.</div>');
            $form[0].reset();
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                let firstError = '';
                $.each(errors, function(field, messages) {
                    let input = $form.find('[name="' + field + '"]');
                    input.addClass('is-invalid');
                    input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    if (!firstError) firstError = messages[0];
                });
                $alert.html('<div class="alert alert-danger">' + firstError + '</div>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                $alert.html('<div class="alert alert-danger">' + xhr.responseJSON.message + '</div>');
            } else {
                $alert.html('<div class="alert alert-danger">An error occurred.</div>');
            }
        }
    });
});
</script>
@endsection
