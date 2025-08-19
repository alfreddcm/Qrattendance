@extends('admin.sidebar')
@section('title', 'Manage Students')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-user-graduate me-2"></i>
                    Manage Students
                </h4>
                <p class="subtitle mb-0">View and manage student records with section assignment</p>
            </div>
            <div class="page-actions">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-1"></i>Add Student
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

        <!-- Search and Filters -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-search me-1"></i>Search & Filter
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.manage-students') }}" id="studentFilterForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Students</label>
                            <input type="text" name="search" id="search" placeholder="Name or ID..." class="form-control" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="school_id" class="form-label">School</label>
                            <select name="school_id" id="school_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Schools</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select name="teacher_id" id="teacher_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="grade_section" class="form-label">Grade & Section</label>
                            <select name="grade_section" id="grade_section" class="form-select" onchange="this.form.submit()">
                                <option value="">All Grade & Section</option>
                                @foreach($gradeSectionOptions as $option)
                                    <option value="{{ $option['value'] }}" {{ request('grade_section') == $option['value'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="qr_status" class="form-label">QR Status</label>
                            <select name="qr_status" id="qr_status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="with_qr" {{ request('qr_status') == 'with_qr' ? 'selected' : '' }}>With QR</option>
                                <option value="without_qr" {{ request('qr_status') == 'without_qr' ? 'selected' : '' }}>Without QR</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-1"></i>Students List</span>
                <span class="badge bg-primary">{{ $students->total() }} students</span>
            </div>
            <div class="card-body p-0">
                @if($students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID No</th>
                                    <th>Name</th>
                                    <th>Section</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                    <th>Contact</th>
                                    <th>QR Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                <tr>
                                    <td><span class="badge bg-info">{{ $student->id_no }}</span></td>
                                    <td><strong>{{ $student->name }}</strong></td>
                                    <td>
                                        @if($student->section)
                                            <span class="badge bg-success">{{ $student->section->name }} - Grade {{ $student->section->gradelevel }}</span>
                                        @else
                                            <span class="badge bg-warning">No Section</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->gender }}</td>
                                    <td>{{ $student->age }}</td>
                                    <td>{{ $student->cp_no ?? 'N/A' }}</td>
                                    <td>
                                        @if($student->qr_code)
                                            <span class="badge bg-success">Generated</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-warning btn-sm" onclick="editStudent({{ $student->id }})" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteStudent({{ $student->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        {{ $students->links() }}
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-2"></i>
                        <h5 class="text-muted">No students found</h5>
                        <p class="text-muted">Try adjusting your search criteria or add new students.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addStudentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New Student
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.students.store') }}" id="addStudentForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6 mb-3">
                            <label for="add_id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_id_no" name="id_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_age" class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add_age" name="age" min="1" max="100" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_cp_no" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="add_cp_no" name="cp_no">
                        </div>
                        
                        <!-- Section Assignment -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Section Assignment <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="section_option" id="add_existing_section" value="existing" checked>
                                    <label class="form-check-label" for="add_existing_section">
                                        Assign to Existing Section
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="section_option" id="add_create_section" value="create">
                                    <label class="form-check-label" for="add_create_section">
                                        Create New Section
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Existing Section Selection -->
                            <div id="add_existing_section_fields">
                                <select class="form-select" id="add_section_id" name="section_id">
                                    <option value="">Select Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }} - Grade {{ $section->gradelevel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- New Section Creation -->
                            <div id="add_new_section_fields" style="display: none;" class="section-creation-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="add_new_section_name" class="form-label">Section Name</label>
                                        <input type="text" class="form-control" id="add_new_section_name" name="new_section_name" placeholder="e.g., STEM, HUMSS">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="add_new_section_gradelevel" class="form-label">Grade Level</label>
                                        <select class="form-select" id="add_new_section_gradelevel" name="new_section_gradelevel">
                                            <option value="">Select Grade</option>
                                            <option value="7">Grade 7</option>
                                            <option value="8">Grade 8</option>
                                            <option value="9">Grade 9</option>
                                            <option value="10">Grade 10</option>
                                            <option value="11">Grade 11</option>
                                            <option value="12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="col-12 mb-3">
                            <label for="add_address" class="form-label">Address</label>
                            <textarea class="form-control" id="add_address" name="address" rows="2"></textarea>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_name" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="add_contact_person_name" name="contact_person_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_relationship" class="form-label">Relationship</label>
                            <input type="text" class="form-control" id="add_contact_person_relationship" name="contact_person_relationship">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_contact_person_contact" class="form-label">Emergency Contact</label>
                            <input type="text" class="form-control" id="add_contact_person_contact" name="contact_person_contact">
                        </div>
                        
                        <!-- School & Teacher Assignment -->
                        <div class="col-md-6 mb-3">
                            <label for="add_school_id" class="form-label">School</label>
                            <select class="form-select" id="add_school_id" name="school_id">
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_user_id" class="form-label">Assign to Teacher</label>
                            <select class="form-select" id="add_user_id" name="user_id">
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Add Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editStudentModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Student
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editStudentForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <!-- Similar structure to add form but with edit_ prefixes -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_id_no" class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_id_no" name="id_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <!-- Add similar fields as in add modal -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Section option toggle
document.addEventListener('DOMContentLoaded', function() {
    const existingRadio = document.getElementById('add_existing_section');
    const createRadio = document.getElementById('add_create_section');
    const existingFields = document.getElementById('add_existing_section_fields');
    const newFields = document.getElementById('add_new_section_fields');
    
    function toggleSectionFields() {
        if (createRadio.checked) {
            existingFields.style.display = 'none';
            newFields.style.display = 'block';
        } else {
            existingFields.style.display = 'block';
            newFields.style.display = 'none';
        }
    }
    
    existingRadio.addEventListener('change', toggleSectionFields);
    createRadio.addEventListener('change', toggleSectionFields);
});

// Students data for JavaScript
const students = @json($students->items());

function editStudent(studentId) {
    const student = students.find(s => s.id === studentId);
    if (!student) return;
    
    // Set form action
    document.getElementById('editStudentForm').action = `/admin/students/${studentId}`;
    
    // Fill form fields
    document.getElementById('edit_id_no').value = student.id_no || '';
    document.getElementById('edit_name').value = student.name || '';
    // Add more field population as needed
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        fetch(`/admin/students/${studentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting student: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting student');
        });
    }
}
</script>

@endsection
