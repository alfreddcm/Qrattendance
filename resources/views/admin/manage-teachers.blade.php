@extends('admin.sidebar')
@section('title', 'Manage Teachers')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-chalkboard-teacher me-2"></i>
                Manage Teachers
            </h4>
            <p class="subtitle fs-6 mb-0">Add, edit, and manage teacher accounts</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                <i class="fas fa-plus me-1"></i>Add Teacher
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

    <!-- Teachers List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center p-2">
                    <h6 class="mb-0 fs-6">
                        <i class="fas fa-users me-1"></i>
                        Teachers List
                    </h6>
                    <span class="badge bg-primary fs-6">{{ $teachers->total() }} teachers</span>
                </div>
                <div class="card-body p-0">
                    @if($teachers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-1 fs-6">Name</th>
                                        <th class="py-1 fs-6">Username</th>
                                        <th class="py-1 fs-6">Email</th>
                                        <th class="py-1 fs-6">School</th>
                                        <th class="py-1 fs-6">Position</th>
                                        <th class="py-1 fs-6">Section</th>
                                        <th class="py-1 fs-6">Phone</th>
                                        <th class="py-1 fs-6" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($teachers as $teacher)
                                    <tr>
                                        <td class="py-1">
                                            <strong class="fs-6">{{ $teacher->name }}</strong>
                                        </td>
                                        <td class="py-1">
                                            <span class="badge bg-info fs-6">{{ $teacher->username }}</span>
                                        </td>
                                        <td class="py-1 fs-6">{{ $teacher->email }}</td>
                                        <td class="py-1">
                                            @if($teacher->school)
                                                <span class="badge bg-success fs-6">{{ $teacher->school->name }}</span>
                                            @else
                                                <span class="badge bg-warning fs-6">No School</span>
                                            @endif
                                        </td>
                                        <td class="py-1 fs-6">{{ $teacher->position ?? 'N/A' }}</td>
                                        <td class="py-1 fs-6">
                                            @if($teacher->sections && $teacher->sections->count() > 0)
                                                @foreach($teacher->sections as $section)
                                                    <span class="badge bg-info me-1">{{ $section->name }} - Grade {{ $section->gradelevel }}</span>
                                                @endforeach
                                            @elseif($teacher->section)
                                                <span class="badge bg-secondary">{{ $teacher->section->name }} - Grade {{ $teacher->section->gradelevel }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="py-1 fs-6">{{ $teacher->phone_number ?? 'N/A' }}</td>
                                        <td class="py-1">
                                            <div class="d-flex gap-1">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary px-2 py-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTeacherModal"
                                                        onclick="editTeacher({{ $teacher->id }})"
                                                        title="Edit Teacher">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" 
                                                      action="{{ route('admin.delete-teacher', $teacher->id) }}" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this teacher?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger px-2 py-1" 
                                                            title="Delete Teacher">
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
                        
                        <!-- Pagination -->
                        <div class="card-footer">
                            {{ $teachers->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No teachers found</h5>
                            <p class="text-muted">Start by adding your first teacher to the system.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                                <i class="fas fa-plus me-1"></i>Add First Teacher
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addTeacherModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.store-teacher') }}" id="addTeacherForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label for="add_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_username" name="username" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="add_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="add_email" name="email" required>
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="add_password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="add_password" name="password" required>
                        </div>
                        
                        <!-- School -->
                        <div class="col-md-6 mb-3">
                            <label for="add_school_id" class="form-label">School <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_school_id" name="school_id" required>
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Position -->
                        <div class="col-md-6 mb-3">
                            <label for="add_position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="add_position" name="position" placeholder="e.g., Math Teacher">
                        </div>
                        
                        <!-- Sections (Multiple Selection) -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Available Sections</label>
                            @if($sections->count() > 0)
                                <div class="row">
                                    @foreach($sections as $section)
                                        @php
                                            $isAssigned = in_array($section->id, $assignedSectionIds ?? []);
                                        @endphp
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    value="{{ $section->id }}" 
                                                    id="add_section_{{ $section->id }}" 
                                                    name="sections[]"
                                                    {{ $isAssigned ? 'disabled' : '' }}
                                                >
                                                <label class="form-check-label" for="add_section_{{ $section->id }}">
                                                    {{ $section->name }} - Grade {{ $section->gradelevel }}
                                                    @if($isAssigned)
                                                        <span class="badge bg-warning ms-1">Already Assigned</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">Select sections to assign to this teacher. Sections already assigned to other teachers are disabled.</small>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please add sections first before assigning teachers.
                                </div>
                            @endif
                        </div>
                        
                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <label for="add_phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="add_phone_number" name="phone_number" placeholder="e.g., +1234567890">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Add Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editTeacherModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Teacher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editTeacherForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        
                        <!-- School -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_school_id" class="form-label">School <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_school_id" name="school_id" required>
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Position -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="edit_position" name="position" placeholder="e.g., Math Teacher">
                        </div>
                        
                        <!-- Sections (Multiple Selection) -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Available Sections</label>
                            @if($sections->count() > 0)
                                <div class="row" id="edit_sections_container">
                                    @foreach($sections as $section)
                                        @php
                                            $isAssigned = in_array($section->id, $assignedSectionIds ?? []);
                                        @endphp
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    value="{{ $section->id }}" 
                                                    id="edit_section_{{ $section->id }}" 
                                                    name="sections[]"
                                                    data-section-id="{{ $section->id }}"
                                                    {{ $isAssigned ? 'disabled' : '' }}
                                                >
                                                <label class="form-check-label" for="edit_section_{{ $section->id }}">
                                                    {{ $section->name }} - Grade {{ $section->gradelevel }}
                                                    @if($isAssigned)
                                                        <span class="badge bg-warning ms-1">Already Assigned</span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">Select sections to assign to this teacher. Sections already assigned to other teachers are disabled.</small>
                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please add sections first before assigning teachers.
                                </div>
                            @endif
                        </div>
     
                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone_number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="edit_phone_number" name="phone_number" placeholder="e.g., +1234567890">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Teacher
                    </button>
                </div>
            </form>
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
    
    .modal-content {
        border-radius: 10px;
        border: none;
    }
    
    .modal-header {
        border-radius: 10px 10px 0 0 !important;
    }
</style>

<script>
const teachers = @json($teachers->items());

function editTeacher(teacherId) {
    const teacher = teachers.find(t => t.id === teacherId);
    if (!teacher) return;
    
    // Set form action
    document.getElementById('editTeacherForm').action = `/admin/teachers/${teacherId}`;
    
    // Fill form fields
    document.getElementById('edit_name').value = teacher.name || '';
    document.getElementById('edit_username').value = teacher.username || '';
    document.getElementById('edit_email').value = teacher.email || '';
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_school_id').value = teacher.school_id || '';
    document.getElementById('edit_position').value = teacher.position || '';
    document.getElementById('edit_phone_number').value = teacher.phone_number || '';
    
    // Handle multiple sections selection with checkboxes
    const editSectionsContainer = document.getElementById('edit_sections_container');
    if (editSectionsContainer) {
        // Clear all checkbox selections first
        const checkboxes = editSectionsContainer.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            // Only uncheck and enable if it's not disabled (not assigned to another teacher)
            if (!checkbox.disabled) {
                checkbox.checked = false;
            }
        });
        
        // Check sections that the teacher is assigned to
        if (teacher.sections && teacher.sections.length > 0) {
            teacher.sections.forEach(section => {
                const checkbox = document.querySelector(`input[data-section-id="${section.id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    // Enable the checkbox since this teacher owns this section
                    checkbox.disabled = false;
                    // Update label to remove "Already Assigned" badge for owned sections
                    const label = checkbox.nextElementSibling;
                    if (label) {
                        const badge = label.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                }
            });
        }
        // Fallback to legacy section_id if sections array is not available
        else if (teacher.section_id) {
            const checkbox = document.querySelector(`input[data-section-id="${teacher.section_id}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.disabled = false;
            }
        }
    }
}

// Add form validation for section conflicts
document.addEventListener('DOMContentLoaded', function() {
    // Validate Add Teacher form
    const addTeacherForm = document.getElementById('addTeacherForm');
    if (addTeacherForm) {
        addTeacherForm.addEventListener('submit', function(e) {
            const checkedSections = addTeacherForm.querySelectorAll('input[name="sections[]"]:checked');
            const disabledCheckedSections = Array.from(checkedSections).filter(cb => cb.disabled);
            
            if (disabledCheckedSections.length > 0) {
                e.preventDefault();
                alert('Error: You have selected sections that are already assigned to other teachers. Please uncheck disabled sections and try again.');
                return false;
            }
        });
    }
    
    // Validate Edit Teacher form
    const editTeacherForm = document.getElementById('editTeacherForm');
    if (editTeacherForm) {
        editTeacherForm.addEventListener('submit', function(e) {
            const checkedSections = editTeacherForm.querySelectorAll('input[name="sections[]"]:checked');
            const disabledCheckedSections = Array.from(checkedSections).filter(cb => cb.disabled);
            
            if (disabledCheckedSections.length > 0) {
                e.preventDefault();
                alert('Error: You have selected sections that are already assigned to other teachers. Please uncheck disabled sections and try again.');
                return false;
            }
        });
    }
    
    // Add visual feedback for disabled checkboxes
    const disabledCheckboxes = document.querySelectorAll('input[type="checkbox"]:disabled');
    disabledCheckboxes.forEach(function(checkbox) {
        const label = checkbox.nextElementSibling;
        if (label) {
            label.style.opacity = '0.6';
            label.style.textDecoration = 'line-through';
        }
    });
});
</script>

@endsection
