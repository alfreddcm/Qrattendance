@extends('admin.sidebar')
@section('title', 'Manage Semesters')
@section('content')

<div class="sticky-header" >
    <div class="d-flex justify-content-between align-items-center" style="margin-left: 1rem;" >
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-calendar-alt me-2"></i>
                Manage Semesters
            </h4>
            <p class="subtitle fs-6 mb-0">Add, edit, and manage semester schedules</p>
        </div>
        
    </div>
</div>

<div class="container-fluid">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Compact Header -->
    <div class="header-row">
        <div class="header-content">
            <div class="header-left">
                <i class="fas fa-calendar-alt me-2"></i>
                <span class="header-title">Semesters</span>
                <span class="header-count">{{ $semesters->total() ?? 0 }} total</span>
            </div>
            
            <div class="header-right">
                <button class="btn-compact-primary" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                    <i class="fas fa-plus me-1"></i>Add Semester
                </button>
            </div>
        </div>
    </div>

    <!-- Semesters Table -->
    <div class="table-container">
        @if($semesters && $semesters->count() > 0)
            <table class="table-compact">
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>SCHOOL</th>
                        <th>DURATION</th>
                        <th>STATUS</th>
                        <th>TIME RANGES</th>
                        <th style="width: 120px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semesters as $semester)
                    <tr>
                        <td>
                            <div class="name">{{ $semester->name }}</div>
                            @if($semester->description)
                                <div class="details">{{ $semester->description }}</div>
                            @endif
                        </td>
                        <td>{{ $semester->school->name ?? 'N/A' }}</td>
                        <td>
                            <div class="date-range">
                                {{ \Carbon\Carbon::parse($semester->start_date)->format('M d, Y') }} - 
                                {{ \Carbon\Carbon::parse($semester->end_date)->format('M d, Y') }}
                            </div>
                        </td>
                        <td>
                            @if($semester->status === 'active')
                                <span class="badge-success">Active</span>
                            @else
                                <span class="badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if($semester->morning_period_start && $semester->morning_period_end)
                                <div class="time-ranges">
                                    <div><strong>Morning:</strong> {{ \Carbon\Carbon::parse($semester->morning_period_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->morning_period_end)->format('g:i A') }}</div>
                                    @if($semester->afternoon_period_start && $semester->afternoon_period_end)
                                        <div><strong>Afternoon:</strong> {{ \Carbon\Carbon::parse($semester->afternoon_period_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->afternoon_period_end)->format('g:i A') }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Time ranges not set</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn-compact-primary" onclick="editSemester({{ $semester->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-compact-danger" onclick="deleteSemester({{ $semester->id }}, '{{ $semester->name }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($semesters instanceof \Illuminate\Pagination\LengthAwarePaginator && $semesters->hasPages())
                <div class="pagination-wrapper">
                    {{ $semesters->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h5>No Semesters Found</h5>
                <p>Create your first semester to get started with academic period management.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Semester Modal -->
<div class="modal fade" id="addSemesterModal" tabindex="-1" aria-labelledby="addSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSemesterModalLabel">Add New Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSemesterForm" method="POST" action="{{ route('admin.semester.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Semester Name <span class="text-danger">*</span></label>
                                <select class="form-control" id="name" name="name" required>
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school_id" class="form-label">School <span class="text-danger">*</span></label>
                                <select class="form-control" id="school_id" name="school_id" required>
                                    <option value="">Select School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" 
                                  placeholder="Optional description"></textarea>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Attendance Time Schedules</h6>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle"></i> Set the general time periods for this semester. These will define the overall attendance schedule framework.</small>
                    </div>
                    
                    <!-- Morning Period -->
                    <h6 class="mt-3 mb-2 text-primary">Morning Period</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="morning_period_start" class="form-label">Morning Start</label>
                                <input type="time" class="form-control" id="morning_period_start" name="morning_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="morning_period_end" class="form-label">Morning End</label>
                                <input type="time" class="form-control" id="morning_period_end" name="morning_period_end">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Afternoon Period -->
                    <h6 class="mt-3 mb-2 text-success">Afternoon Period</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="afternoon_period_start" class="form-label">Afternoon Start</label>
                                <input type="time" class="form-control" id="afternoon_period_start" name="afternoon_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="afternoon_period_end" class="form-label">Afternoon End</label>
                                <input type="time" class="form-control" id="afternoon_period_end" name="afternoon_period_end">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-compact-primary">Create Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Semester Modal -->
<div class="modal fade" id="editSemesterModal" tabindex="-1" aria-labelledby="editSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSemesterModalLabel">Edit Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSemesterForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Semester Name <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_name" name="name" required>
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_school_id" class="form-label">School <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_school_id" name="school_id" required>
                                    <option value="">Select School</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Attendance Time Schedules</h6>
                    <div class="alert alert-warning">
                        <small><i class="fas fa-exclamation-triangle"></i> Modifying time schedules will affect all attendance data. Ensure no conflicts exist.</small>
                    </div>
                    
                    <!-- Morning Period -->
                    <h6 class="mt-3 mb-2 text-primary">Morning Period</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_morning_period_start" class="form-label">Morning Start</label>
                                <input type="time" class="form-control" id="edit_morning_period_start" name="morning_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_morning_period_end" class="form-label">Morning End</label>
                                <input type="time" class="form-control" id="edit_morning_period_end" name="morning_period_end">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Afternoon Period -->
                    <h6 class="mt-3 mb-2 text-success">Afternoon Period</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_afternoon_period_start" class="form-label">Afternoon Start</label>
                                <input type="time" class="form-control" id="edit_afternoon_period_start" name="afternoon_period_start">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_afternoon_period_end" class="form-label">Afternoon End</label>
                                <input type="time" class="form-control" id="edit_afternoon_period_end" name="afternoon_period_end">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-compact-primary">Update Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Semester Confirmation Modal -->
<div class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSemesterModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteSemesterName"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> This action cannot be undone and will affect all attendance data associated with this semester.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSemesterForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-compact-danger">Delete Semester</button>
                </form>
            </div>
        </div>
    </div>
</div>

 
 <script>
 function formatTime(timeString) {
    if (!timeString) return '';
    if (timeString.length > 5) {
        return timeString.substring(0, 5); 
    }
    return timeString;
}

function timeToMinutes(timeStr) {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

function addMinutes(timeStr, minutes) {
    if (!timeStr) return '';
    const totalMinutes = timeToMinutes(timeStr) + minutes;
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

function subtractMinutes(timeStr, minutes) {
    if (!timeStr) return '';
    const totalMinutes = timeToMinutes(timeStr) - minutes;
    if (totalMinutes < 0) return '00:00';
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

function setTimeFieldValue(fieldId, value, defaultValue) {
    const field = document.getElementById(fieldId);
    if (field) {
        const formattedValue = formatTime(value);
        const formattedDefault = formatTime(defaultValue);
        field.value = formattedValue || formattedDefault || '';
    }
}

// Ensure dates coming from the backend (e.g., "YYYY-MM-DD HH:MM:SS" or ISO) fit <input type="date">
function formatDateValue(dateInput) {
    if (!dateInput) return '';
    try {
        // If already in YYYY-MM-DD, keep it
        if (typeof dateInput === 'string') {
            const ymd = dateInput.match(/^\d{4}-\d{2}-\d{2}/);
            if (ymd) return ymd[0];

            // If in dd/mm/yyyy, convert to YYYY-MM-DD
            const dmy = dateInput.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (dmy) return `${dmy[3]}-${dmy[2]}-${dmy[1]}`;

            // Fall back to Date parsing
            const parsed = new Date(dateInput);
            if (!isNaN(parsed.getTime())) return parsed.toISOString().slice(0, 10);
        }
    } catch (e) {
        // Ignore and return empty on failure
    }
    return '';
}

// Semester validation functions
function validateSemesterTimes(prefix) {
    const getTimeValue = (id) => {
        const element = document.getElementById(prefix === 'add' ? id : `${prefix}_${id}`);
        return element ? element.value : '';
    };
    
    const getDateValue = (id) => {
        const element = document.getElementById(prefix === 'add' ? id : `${prefix}_${id}`);
        return element ? element.value : '';
    };
    
    // Check date fields first (most important validation)
    const startDate = getDateValue('start_date');
    const endDate = getDateValue('end_date');
    
    if (startDate && endDate) {
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);
        
        if (startDateObj >= endDateObj) {
            alert('Start date must be before end date');
             const endDateField = document.getElementById(prefix === 'add' ? 'end_date' : `${prefix}_end_date`);
            if (endDateField) endDateField.focus();
            return false;
        }
        
         const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (startDateObj < today) {
            if (!confirm('Start date is in the past. Are you sure you want to continue?')) {
                return false;
            }
        }
    }
    
    // Check time periods (only if values are provided)
    const morningStart = getTimeValue('morning_period_start');
    const morningEnd = getTimeValue('morning_period_end');
    const afternoonStart = getTimeValue('afternoon_period_start');
    const afternoonEnd = getTimeValue('afternoon_period_end');
    
    // Check morning period
    if (morningStart && morningEnd) {
        if (timeToMinutes(morningStart) >= timeToMinutes(morningEnd)) {
            alert('Morning period start time must be before end time');
            const morningEndField = document.getElementById(prefix === 'add' ? 'morning_period_end' : `${prefix}_morning_period_end`);
            if (morningEndField) morningEndField.focus();
            return false;
        }
    }
    
    // Check afternoon period
    if (afternoonStart && afternoonEnd) {
        if (timeToMinutes(afternoonStart) >= timeToMinutes(afternoonEnd)) {
            alert('Afternoon period start time must be before end time');
            const afternoonEndField = document.getElementById(prefix === 'add' ? 'afternoon_period_end' : `${prefix}_afternoon_period_end`);
            if (afternoonEndField) afternoonEndField.focus();
            return false;
        }
    }
    
    // Check morning and afternoon don't overlap (only if both periods are defined)
    if (morningEnd && afternoonStart) {
        if (timeToMinutes(afternoonStart) <= timeToMinutes(morningEnd)) {
            alert('Afternoon period must start after morning period ends. Please ensure there is a break between periods.');
            const afternoonStartField = document.getElementById(prefix === 'add' ? 'afternoon_period_start' : `${prefix}_afternoon_period_start`);
            if (afternoonStartField) afternoonStartField.focus();
            return false;
        }
        
        // Suggest minimum gap (optional)
        const gap = timeToMinutes(afternoonStart) - timeToMinutes(morningEnd);
        if (gap < 30) { // Less than 30 minutes
            if (!confirm(`Only ${gap} minutes break between morning and afternoon periods. Recommended minimum is 30 minutes. Continue?`)) {
                return false;
            }
        }
    }
    
    return true;
}

// Setup form validation
function setupTimeValidation() {
    // Semester form validation
    const addSemesterForm = document.querySelector('#addSemesterModal form');
    const editSemesterForm = document.querySelector('#editSemesterModal form');
    
    if (addSemesterForm) {
        addSemesterForm.addEventListener('submit', function(e) {
            if (!validateSemesterTimes('add')) {
                e.preventDefault();
            }
        });
    }
    
    if (editSemesterForm) {
        editSemesterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateSemesterTimes('edit')) return;
            // Submit via AJAX and reload on success
            const submitBtn = editSemesterForm.querySelector('[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            const formData = new FormData(editSemesterForm);
            fetch(editSemesterForm.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
                credentials: 'same-origin'
            })
            .then(async (res) => {
                let data = {};
                try { data = await res.json(); } catch (_) {}
                if (res.ok && data && data.success) {
                    window.location.reload();
                } else {
                    const msg = (data && (data.message || data.error)) || `Request failed (${res.status})`;
                    alert(msg);
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error submitting the form.');
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
}

// Semester CRUD functions
function editSemester(semesterId) {
    fetch(`/admin/semesters/${semesterId}/edit`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_start_date').value = formatDateValue(data.start_date);
            document.getElementById('edit_end_date').value = formatDateValue(data.end_date);
            document.getElementById('edit_school_id').value = data.school_id || '';
            document.getElementById('edit_description').value = data.description || '';
            
             document.getElementById('edit_morning_period_start').value = formatTime(data.morning_period_start);
            document.getElementById('edit_morning_period_end').value = formatTime(data.morning_period_end);
            document.getElementById('edit_afternoon_period_start').value = formatTime(data.afternoon_period_start);
            document.getElementById('edit_afternoon_period_end').value = formatTime(data.afternoon_period_end);
            
            document.getElementById('editSemesterForm').action = '/admin/semesters/' + semesterId;
            
            new bootstrap.Modal(document.getElementById('editSemesterModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading semester data: ' + error.message);
        });
}

function deleteSemester(id, name) {
    document.getElementById('deleteSemesterName').textContent = name || 'this semester';
    document.getElementById('deleteSemesterForm').action = '/admin/semesters/' + id;
    new bootstrap.Modal(document.getElementById('deleteSemesterModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    setupTimeValidation();
});
</script>
@endsection
