@extends('teacher/sidebar')
@section('title', 'Manage Semesters')
@section('content')

<title>@yield('title')</title>

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <span class="me-2">📚</span>
                Manage Semesters
            </h2>
            <p class="subtitle">View and edit semester information (Contact admin to create new semesters)</p>
        </div>
        
    </div>
</div>

<div class="container">
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Semester Overview
                    </h5>
                </div>
                <div class="card-body">
                    @if($semesters->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Semesters Found</h5>
                            <p class="text-muted">Contact your administrator to create semesters for your school.</p>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Only administrators can create new semesters. You can edit existing semester details.
                            </div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%">Semester Name</th>
                                        <th width="15%">Start Date</th>
                                        <th width="15%">End Date</th>
                                        <th width="10%">Status</th>
                                        <th width="20%">Time Schedule</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($semesters as $semester)
                                        @php
                                            $today = \Carbon\Carbon::now();
                                            $startDate = \Carbon\Carbon::parse($semester->start_date);
                                            $endDate = \Carbon\Carbon::parse($semester->end_date);
                                            $isActive = $today->between($startDate, $endDate);
                                            $isPast = $today->greaterThan($endDate);
                                            $isFuture = $today->lessThan($startDate);
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $semester->name }}</strong>
                                                @if($isActive)
                                                    <span class="badge bg-success ms-2">Current</span>
                                                @endif
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-start me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($semester->start_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-check me-1 text-muted"></i>
                                                {{ \Carbon\Carbon::parse($semester->end_date)->format('M j, Y') }}
                                            </td>
                                            <td>
                                                @if($isActive)
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($isPast)
                                                    <span class="badge bg-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-warning">Upcoming</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <div>AM: {{ \Carbon\Carbon::parse($semester->am_time_in_start ?? '07:00:00')->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->am_time_in_end ?? '07:30:00')->format('g:i A') }}</div>
                                                    <div>PM: {{ \Carbon\Carbon::parse($semester->pm_time_out_start ?? '16:30:00')->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->pm_time_out_end ?? '17:00:00')->format('g:i A') }}</div>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editSemester({{ $semester->id }})" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>To create new semesters, please contact your administrator. You can edit existing semester details only.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Edit Semester Modal -->
<div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editSemesterForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editSemesterModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Semester
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Semester Name
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="edit_name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">
                                    <i class="fas fa-toggle-on me-1"></i>Status
                                </label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="edit_status" name="status" required>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Start Date
                                </label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                       id="edit_start_date" name="start_date" value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>End Date
                                </label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                       id="edit_end_date" name="end_date" value="{{ old('end_date') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3">
                        <i class="fas fa-clock me-2"></i>Time Schedule Configuration
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sun me-1"></i>Morning Time-In Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="edit_am_time_in_start" class="form-label">Start Time</label>
                                        <input type="time" class="form-control @error('am_time_in_start') is-invalid @enderror" 
                                               id="edit_am_time_in_start" name="am_time_in_start" value="{{ old('am_time_in_start') }}"
                                               onchange="validateTimeOnChange('edit_am_time_in_start', 'edit_am_time_in_end', 'Start time must be before end time')">
                                        @error('am_time_in_start')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_am_time_in_end" class="form-label">End Time</label>
                                        <input type="time" class="form-control @error('am_time_in_end') is-invalid @enderror" 
                                               id="edit_am_time_in_end" name="am_time_in_end" value="{{ old('am_time_in_end') }}"
                                               onchange="validateTimeOnChange('edit_am_time_in_start', 'edit_am_time_in_end', 'End time must be after start time')">
                                        @error('am_time_in_end')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-moon me-1"></i>Afternoon Time-Out Period
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="edit_pm_time_out_start" class="form-label">Start Time</label>
                                        <input type="time" class="form-control @error('pm_time_out_start') is-invalid @enderror" 
                                               id="edit_pm_time_out_start" name="pm_time_out_start" value="{{ old('pm_time_out_start') }}"
                                               onchange="validateTimeOnChange('edit_pm_time_out_start', 'edit_pm_time_out_end', 'Start time must be before end time')">
                                        @error('pm_time_out_start')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_pm_time_out_end" class="form-label">End Time</label>
                                        <input type="time" class="form-control @error('pm_time_out_end') is-invalid @enderror" 
                                               id="edit_pm_time_out_end" name="pm_time_out_end" value="{{ old('pm_time_out_end') }}"
                                               onchange="validateTimeOnChange('edit_pm_time_out_start', 'edit_pm_time_out_end', 'End time must be after start time')">
                                        @error('pm_time_out_end')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Semester
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="deleteSemesterForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSemesterModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Dangerous Action - Delete Semester
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Critical Warning!</h6>
                        <p class="mb-0">This action is <strong>IRREVERSIBLE</strong> and will permanently delete:</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-graduation-cap fa-2x text-primary mb-2"></i>
                                <h6>Semester Data</h6>
                                <p class="text-muted mb-0">Name, dates, schedules</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-users fa-2x text-warning mb-2"></i>
                                <h6>All Students</h6>
                                <p class="text-muted mb-0">Enrolled in this semester</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-clipboard-check fa-2x text-danger mb-2"></i>
                                <h6>Attendance Records</h6>
                                <p class="text-muted mb-0">All historical data</p>
                            </div>
                        </div>
                    </div>

                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Semester to Delete:</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <span id="deleteSemesterName" class="text-danger"></span></p>
                                    <p><strong>Duration:</strong> <span id="deleteSemesterDuration"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Students Enrolled:</strong> <span id="deleteSemesterStudents" class="badge bg-warning text-dark"></span></p>
                                    <p><strong>Attendance Records:</strong> <span id="deleteSemesterAttendance" class="badge bg-info"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label text-danger" for="confirmDelete">
                                <strong>I understand that this action cannot be undone and all related data will be permanently lost.</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel - Keep Semester
                    </button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash me-1"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced time validation function with previous value storage
function validateTimeOnChange(startId, endId, errorMessage) {
    const startInput = document.getElementById(startId);
    const endInput = document.getElementById(endId);
    const startTime = startInput.value;
    const endTime = endInput.value;
    
    // Store previous value if not already stored
    if (!startInput.previousValue) startInput.previousValue = startInput.value;
    if (!endInput.previousValue) endInput.previousValue = endInput.value;
    
    if (startTime && endTime) {
        if (startTime >= endTime) {
            alert(errorMessage);
            // Revert to previous valid value instead of clearing
            event.target.value = event.target.previousValue || '';
            event.target.focus();
            return false;
        }
    }
    
    // Update previous value only if validation passes
    event.target.previousValue = event.target.value;
    return true;
}

function editSemester(semesterId) {
    fetch(`/teacher/semester/${semesterId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_start_date').value = data.start_date;
            document.getElementById('edit_end_date').value = data.end_date;
            document.getElementById('edit_am_time_in_start').value = data.am_time_in_start_input || '';
            document.getElementById('edit_am_time_in_end').value = data.am_time_in_end_input || '';
            document.getElementById('edit_pm_time_out_start').value = data.pm_time_out_start_input || '';
            document.getElementById('edit_pm_time_out_end').value = data.pm_time_out_end_input || '';
            
            // Initialize previous values after setting the data
            initializeEditTimeValues();
            
            document.getElementById('editSemesterForm').action = `/teacher/semester/${semesterId}`;
            
            var modal = new bootstrap.Modal(document.getElementById('editSemesterModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading semester data');
        });
}

function deleteSemester(semesterId, semesterName) {
    fetch(`/teacher/semester/${semesterId}/edit`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
             document.getElementById('deleteSemesterName').textContent = data.name;
            document.getElementById('deleteSemesterDuration').textContent = 
                new Date(data.start_date).toLocaleDateString() + ' - ' + new Date(data.end_date).toLocaleDateString();
            
            // Display the counts from API
            document.getElementById('deleteSemesterStudents').textContent = data.student_count || '0';
            document.getElementById('deleteSemesterAttendance').textContent = data.attendance_count || '0';
            
            document.getElementById('deleteSemesterForm').action = `/teacher/semester/${semesterId}`;
            
            // Reset checkbox
            document.getElementById('confirmDelete').checked = false;
            document.getElementById('confirmDeleteBtn').disabled = true;
            
            var modal = new bootstrap.Modal(document.getElementById('deleteSemesterModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
             document.getElementById('deleteSemesterName').textContent = semesterName;
            document.getElementById('deleteSemesterDuration').textContent = 'Unknown';
            document.getElementById('deleteSemesterStudents').textContent = 'Unknown';
            document.getElementById('deleteSemesterAttendance').textContent = 'Unknown';
            document.getElementById('deleteSemesterForm').action = `/teacher/semester/${semesterId}`;
            
             document.getElementById('confirmDelete').checked = false;
            document.getElementById('confirmDeleteBtn').disabled = true;
            
            var modal = new bootstrap.Modal(document.getElementById('deleteSemesterModal'));
            modal.show();
        });
}

 document.getElementById('start_date').addEventListener('change', function() {
    document.getElementById('end_date').min = this.value;
    validateDateRange('start_date', 'end_date');
});

document.getElementById('end_date').addEventListener('change', function() {
    validateDateRange('start_date', 'end_date');
});

document.getElementById('edit_start_date').addEventListener('change', function() {
    document.getElementById('edit_end_date').min = this.value;
    validateDateRange('edit_start_date', 'edit_end_date');
});

document.getElementById('edit_end_date').addEventListener('change', function() {
    validateDateRange('edit_start_date', 'edit_end_date');
});

// Date range validation function
function validateDateRange(startId, endId) {
    const startDate = document.getElementById(startId).value;
    const endDate = document.getElementById(endId).value;
    
    if (startDate && endDate) {
        if (new Date(startDate) >= new Date(endDate)) {
            alert('End date must be after start date');
            document.getElementById(endId).value = '';
            return false;
        }
        
        const timeDiff = new Date(endDate) - new Date(startDate);
        const daysDiff = timeDiff / (1000 * 3600 * 24);
        
        if (daysDiff < 1) {
            alert('Semester must be at least 1 day long');
            document.getElementById(endId).value = '';
            return false;
        }
    }
    return true;
}

document.getElementById('confirmDelete').addEventListener('change', function() {
    document.getElementById('confirmDeleteBtn').disabled = !this.checked;
});

// Add loading states for forms
document.getElementById('addSemesterModal').addEventListener('shown.bs.modal', function() {
    // Reset form when modal opens
    this.querySelector('form').reset();
    document.getElementById('am_time_in_start').value = '07:00';
    document.getElementById('am_time_in_end').value = '07:30';
    document.getElementById('pm_time_out_start').value = '16:30';
    document.getElementById('pm_time_out_end').value = '17:00';
    
    // Initialize previous values for validation
    document.getElementById('am_time_in_start').previousValue = '07:00';
    document.getElementById('am_time_in_end').previousValue = '07:30';
    document.getElementById('pm_time_out_start').previousValue = '16:30';
    document.getElementById('pm_time_out_end').previousValue = '17:00';
});

// Initialize previous values for edit modal when data is loaded
function initializeEditTimeValues() {
    const timeInputs = ['edit_am_time_in_start', 'edit_am_time_in_end', 'edit_pm_time_out_start', 'edit_pm_time_out_end'];
    timeInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input && input.value) {
            input.previousValue = input.value;
        }
    });
}

// Form submission loading states
function setFormLoading(formId, isLoading) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    if (isLoading) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Add form submission handlers with validation
document.querySelector('#addSemesterModal form').addEventListener('submit', function(e) {
    // Validate date range before submission
    if (!validateDateRange('start_date', 'end_date')) {
        e.preventDefault();
        return false;
    }
    setFormLoading('addSemesterModal', true);
});

document.querySelector('#editSemesterModal form').addEventListener('submit', function(e) {
    // Validate date range before submission
    if (!validateDateRange('edit_start_date', 'edit_end_date')) {
        e.preventDefault();
        return false;
    }
    setFormLoading('editSemesterModal', true);
});

document.querySelector('#deleteSemesterModal form').addEventListener('submit', function() {
    setFormLoading('deleteSemesterModal', true);
});

// Auto-show error alerts with enhanced styling
document.addEventListener('DOMContentLoaded', function() {
    // Show error popup if there are validation errors
    @if($errors->any())
        // Show error toast/alert
        const errorAlert = document.querySelector('.alert-danger');
        if (errorAlert) {
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Add a pulsing animation to draw attention
            errorAlert.style.animation = 'pulse 2s ease-in-out 3';
        }
        
        // If there were errors on edit form, reopen the edit modal with the old input
        @if(old('_method') === 'PUT')
            // Find the semester ID from old input or URL
            const oldSemesterId = '{{ old("semester_id") ?? request()->route("id") }}';
            if (oldSemesterId) {
                // Reopen edit modal with previous data
                setTimeout(function() {
                    // Find the edit button for this semester and trigger it
                    const editBtn = document.querySelector(`button[onclick*="editSemester(${oldSemesterId})"]`);
                    if (editBtn) {
                        editBtn.click();
                    } else {
                        // Alternative: just show the modal directly
                        const editModal = new bootstrap.Modal(document.getElementById('editSemesterModal'));
                        editModal.show();
                    }
                }, 100);
            }
        @endif
    @endif
    
    // Enhanced error display for form fields
    const errorFields = document.querySelectorAll('.is-invalid');
    errorFields.forEach(field => {
        field.addEventListener('focus', function() {
            // Remove the error styling when user starts typing
            field.classList.remove('is-invalid');
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.style.display = 'none';
            }
        });
    });
});

// Add CSS for pulse animation
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.02); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .alert-danger {
        border-left: 5px solid #dc3545;
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
    }
    
    .is-invalid {
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);
</script>

@endsection