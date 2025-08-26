@extends('admin.sidebar')
@section('title', 'Edit School')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <span class="me-2">🏫</span>
                Edit School
            </h2>
            <p class="subtitle">Update school information</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit School Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.update-school', $school->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- School Logo Upload -->
                            <div class="col-md-12 mb-4">
                                <label for="logo" class="form-label">
                                    <i class="fas fa-image me-1"></i>School Logo
                                </label>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="logo-preview" id="logoPreview">
                                        @if($school->logo)
                                            <img src="{{ asset('storage/' . $school->logo) }}" alt="Current Logo">
                                        @else
                                            <div class="placeholder-logo">
                                                <i class="fas fa-school fa-3x text-muted"></i>
                                                <p class="mt-2 mb-0 text-muted">No logo uploaded</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" 
                                               class="form-control @error('logo') is-invalid @enderror" 
                                               id="logo" 
                                               name="logo" 
                                               accept="image/*"
                                               onchange="previewLogo(this)">
                                        <div class="form-text">
                                            Upload a new school logo (JPG, PNG, GIF. Max: 2MB). Leave empty to keep current logo.
                                        </div>
                                        @error('logo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- School ID -->
                            <div class="col-md-6 mb-3">
                                <label for="school_id" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>School ID <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('school_id') is-invalid @enderror" 
                                       id="school_id" 
                                       name="school_id" 
                                       value="{{ old('school_id', $school->school_id) }}" 
                                       placeholder="e.g., SCH0001"
                                       required>
                                <div class="form-text">
                                    Unique identifier for the school
                                </div>
                                @error('school_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- School Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-school me-1"></i>School Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $school->name) }}" 
                                       placeholder="Enter school name"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- School Address -->
                            <div class="col-md-12 mb-4">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>School Address <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" 
                                          name="address" 
                                          rows="3" 
                                          placeholder="Enter complete school address"
                                          required>{{ old('address', $school->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Update School
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .logo-preview {
        width: 120px;
        height: 120px;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        overflow: hidden;
    }
    
    .logo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }
    
    .placeholder-logo {
        text-align: center;
        padding: 10px;
    }
    
    .placeholder-logo p {
        font-size: 0.8em;
    }
    
    .card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    
    .btn {
        border-radius: 6px;
    }
    
    .form-control {
        border-radius: 6px;
    }
    
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
</style>

<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

@endsection
