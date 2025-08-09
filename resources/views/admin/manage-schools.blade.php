@extends('admin.sidebar')
@section('title', 'Manage Schools')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <i class="fas fa-school me-2"></i>
                Manage Schools
            </h2>
            <p class="subtitle">Create and manage schools in the system</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
                <i class="fas fa-plus me-1"></i>Add School
            </button>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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

    <!-- Schools Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Schools</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>School ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>
                                @if($school->logo)
                                    <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo" class="school-logo">
                                @else
                                    <div class="school-logo-placeholder">
                                        <i class="fas fa-school"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $school->school_id }}</span>
                            </td>
                            <td>
                                <strong>{{ $school->name }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($school->address, 50) }}</small>
                            </td>
                            <td>
                                <small>{{ $school->created_at->format('M j, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editSchool({{ $school->id }}, '{{ $school->school_id }}', '{{ $school->name }}', '{{ addslashes($school->address) }}', '{{ $school->logo }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteSchool({{ $school->id }}, '{{ $school->name }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-school fa-3x mb-3"></i>
                                    <p>No schools found. Create your first school!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $schools->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Add School Modal -->
<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSchoolModalLabel">Add New School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.store-school') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="logo" class="form-label">School Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Upload school logo (optional)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school_id" class="form-label">School ID</label>
                                <input type="text" class="form-control" id="school_id" name="school_id" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit School Modal -->
<div class="modal fade" id="editSchoolModal" tabindex="-1" aria-labelledby="editSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSchoolModalLabel">Edit School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSchoolForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_logo" class="form-label">School Logo</label>
                            <input type="file" class="form-control" id="edit_logo" name="logo" accept="image/*">
                            <small class="text-muted">Upload new logo (leave empty to keep current)</small>
                            <div id="current_logo_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_school_id" class="form-label">School ID</label>
                                <input type="text" class="form-control" id="edit_school_id" name="school_id" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_address" class="form-label">Address</label>
                                <textarea class="form-control" id="edit_address" name="address" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSchoolModal" tabindex="-1" aria-labelledby="deleteSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSchoolModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the school "<span id="deleteSchoolName"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will affect all associated data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSchoolForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete School</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.school-logo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.school-logo-placeholder {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1.2em;
}

.current-logo-preview {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}
</style>

<script>
function editSchool(id, schoolId, name, address, logo) {
    try {
        document.getElementById('edit_school_id').value = schoolId || '';
        document.getElementById('edit_name').value = name || '';
        document.getElementById('edit_address').value = address || '';
        
        // Show current logo preview
        const logoPreview = document.getElementById('current_logo_preview');
        if (logo) {
            logoPreview.innerHTML = `<img src="{{ asset('storage/') }}/${logo}" alt="Current Logo" class="current-logo-preview">`;
        } else {
            logoPreview.innerHTML = '<small class="text-muted">No logo currently uploaded</small>';
        }
        
        document.getElementById('editSchoolForm').action = `/admin/update-school/${id}`;
        
        new bootstrap.Modal(document.getElementById('editSchoolModal')).show();
    } catch (error) {
        console.error('Error opening edit modal:', error);
        alert('Error opening edit form. Please refresh the page and try again.');
    }
}

function deleteSchool(id, name) {
    try {
        document.getElementById('deleteSchoolName').textContent = name || 'this school';
        document.getElementById('deleteSchoolForm').action = `/admin/delete-school/${id}`;
        
        new bootstrap.Modal(document.getElementById('deleteSchoolModal')).show();
    } catch (error) {
        console.error('Error opening delete modal:', error);
        alert('Error opening delete confirmation. Please refresh the page and try again.');
    }
}

// Form validation and preview functionality
document.addEventListener('DOMContentLoaded', function() {
    // Logo preview for add form
    document.getElementById('logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // You can add preview functionality here if needed
            };
            reader.readAsDataURL(file);
        }
    });

    // Logo preview for edit form
    document.getElementById('edit_logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const logoPreview = document.getElementById('current_logo_preview');
                logoPreview.innerHTML = `<img src="${e.target.result}" alt="New Logo Preview" class="current-logo-preview">`;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

@endsection
