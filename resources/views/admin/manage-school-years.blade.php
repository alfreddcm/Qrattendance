@extends('admin.sidebar')
@section('title', 'Manage School Years')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-calendar-alt me-2"></i>
                Manage School Years
            </h4>
            <p class="subtitle fs-6 mb-0">Add, edit, and manage School Year schedules</p>
        </div>

    </div>
</div>

<div class="container-fluid">

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


    <div class="header-row">
        <div class="header-content">
            <div class="header-left">
                <i class="fas fa-calendar-alt me-2"></i>
                <span class="header-title">School Year(s)</span>
                <span class="header-count">{{ $schoolYears->total() ?? 0 }} total</span>
            </div>

            <div class="header-right">
                <button class="btn-compact-primary" data-bs-toggle="modal" data-bs-target="#addSchoolYearModal">
                    <i class="fas fa-plus me-1"></i>Add School Year
                </button>
            </div>
        </div>
    </div>


    <div class="table-container">
        <div class="card-header bg-primary text-white p-2">
            <h6 class="mb-0 fs-6"><i class="fas fa-list me-1"></i>School Years</h6>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="py-1 fs-6">School</th>
                            <th class="py-1 fs-6">School Year</th>
                            <th class="py-1 fs-6">Duration</th>
                            <th class="py-1 fs-6">Status</th>
                            <th class="py-1 fs-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schoolYears as $schoolYear)
                        <tr>
                            <td class="py-1">
                                <strong class="fs-6">{{ $schoolYear->school->name ?? 'N/A' }}</strong>
                            </td>
                            <td class="py-1">
                                <span class="badge bg-info fs-6">
                                    @if($schoolYear->school_year_start && $schoolYear->school_year_end)
                                        {{ $schoolYear->school_year_start }}–{{ $schoolYear->school_year_end }}
                                    @else
                                        {{ $schoolYear->name }}
                                    @endif
                                </span>
                            </td>
                            <td class="py-1">
                                <small class="text-muted fs-6">
                                    {{ \Carbon\Carbon::parse($schoolYear->start_date)->format('M d, Y') }} -
                                    {{ \Carbon\Carbon::parse($schoolYear->end_date)->format('M d, Y') }}
                                </small>
                            </td>
                            <td class="py-1">
                                @if($schoolYear->status === 'active')
                                    <span class="badge bg-success fs-6">Active</span>
                                @else
                                    <span class="badge bg-secondary fs-6">Inactive</span>
                                @endif
                            </td>
                            <td class="py-1">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" onclick="editSchoolYear(@js($schoolYear->uuid ?? $schoolYear->id))" title="Edit School Year">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="deleteSchoolYear(@js($schoolYear->uuid ?? $schoolYear->id), @js($schoolYear->name))">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">
                                <div class="text-muted">
                                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                    <p class="fs-6">No school years found. Add your first school year!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">
                {{ $schoolYears->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addSchoolYearModal" tabindex="-1" aria-labelledby="addSchoolYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSchoolYearModalLabel">Add New School Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSchoolYearForm" method="POST" action="{{ route('admin.school-year.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
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
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="school_year_start" class="form-label">Start Year <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="school_year_start" name="school_year_start" 
                                       min="2020" max="2100" value="{{ date('Y') }}" required 
                                       placeholder="e.g., 2024">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="school_year_end" class="form-label">End Year <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="school_year_end" name="school_year_end" 
                                       min="2020" max="2100" value="{{ date('Y') + 1 }}" required 
                                       placeholder="e.g., 2025">
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
                                  placeholder="Optional description (e.g., School Year 2024–2025)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-compact-primary">Create School Year</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSchoolYearModal" tabindex="-1" aria-labelledby="editSchoolYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSchoolYearModalLabel">Edit School Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSchoolYearForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
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
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="edit_school_year_start" class="form-label">Start Year <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_school_year_start" name="school_year_start" 
                                       min="2020" max="2100" required placeholder="e.g., 2024">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="edit_school_year_end" class="form-label">End Year <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_school_year_end" name="school_year_end" 
                                       min="2020" max="2100" required placeholder="e.g., 2025">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-compact-primary">Update School Year</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSchoolYearModal" tabindex="-1" aria-labelledby="deleteSchoolYearModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSchoolYearModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteSchoolYearName"></strong>?</p>
                <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> This action cannot be undone and will affect all attendance data associated with this School Year.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-compact-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSchoolYearForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-compact-danger">Delete School Year</button>
                </form>
            </div>
        </div>
    </div>
</div>


 <script>
// Global CSRF and session management
function handleAjaxError(response, submitBtn = null) {
    if (response.status === 419) {
        alert('Your session has expired. The page will refresh to restore your session.');
        window.location.reload();
        return;
    }
    
    if (response.status === 403) {
        alert('Access denied. Please check your permissions.');
        if (submitBtn) submitBtn.disabled = false;
        return;
    }
    
    if (response.status === 422) {
        response.json().then(data => {
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                alert('Validation errors:\n' + errorMessages);
            } else {
                alert('Validation failed. Please check your input.');
            }
        }).catch(() => {
            alert('Validation failed. Please check your input.');
        });
        if (submitBtn) submitBtn.disabled = false;
        return;
    }
    
    // Generic error handling
    response.json().then(data => {
        const msg = (data && (data.message || data.error)) || `Request failed (${response.status})`;
        alert(msg);
    }).catch(() => {
        alert(`Request failed with status ${response.status}`);
    });
    
    if (submitBtn) submitBtn.disabled = false;
}

// Enhanced CSRF token management
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
           document.querySelector('input[name="_token"]')?.value;
}

function updateCsrfTokens(newToken) {
    // Update meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        metaTag.setAttribute('content', newToken);
    }
    
    // Update all form tokens
    const tokenInputs = document.querySelectorAll('input[name="_token"]');
    tokenInputs.forEach(input => {
        input.value = newToken;
    });
}

function refreshCsrfToken() {
    return fetch('/admin/refresh-csrf', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.token) {
            updateCsrfTokens(data.token);
            return data.token;
        }
        throw new Error('No token received');
    });
}

// Time utilities
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

// School Year validation functions
function validateSchoolYearData(prefix) {
    const getTimeValue = (id) => {
        const element = document.getElementById(prefix === 'add' ? id : `${prefix}_${id}`);
        return element ? element.value : '';
    };

    const getDateValue = (id) => {
        const element = document.getElementById(prefix === 'add' ? id : `${prefix}_${id}`);
        return element ? element.value : '';
    };

    const getNumberValue = (id) => {
        const element = document.getElementById(prefix === 'add' ? id : `${prefix}_${id}`);
        return element ? parseInt(element.value) : 0;
    };

    const startYear = getNumberValue('school_year_start');
    const endYear = getNumberValue('school_year_end');

    if (startYear && endYear) {
        if (startYear >= endYear) {
            alert('Start year must be before end year (e.g., 2024–2025)');
            const endYearField = document.getElementById(prefix === 'add' ? 'school_year_end' : `${prefix}_school_year_end`);
            if (endYearField) endYearField.focus();
            return false;
        }

        if (endYear - startYear !== 1) {
            if (!confirm('School year typically spans one year (e.g., 2024-2025). Continue with ' + startYear + '–' + endYear + '?')) {
                return false;
            }
        }
    }

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

    return true;
}

// Setup form validation
function setupTimeValidation() {
    const addSchoolYearForm = document.querySelector('#addSchoolYearModal form');
    const editSchoolYearForm = document.querySelector('#editSchoolYearModal form');

    if (addSchoolYearForm) {
        addSchoolYearForm.addEventListener('submit', function(e) {
            if (!validateSchoolYearData('add')) {
                e.preventDefault();
            }
        });
    }

    if (editSchoolYearForm) {
        editSchoolYearForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateSchoolYearData('edit')) return;
            const submitBtn = editSchoolYearForm.querySelector('[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            const formData = new FormData(editSchoolYearForm);
            
            const csrfToken = getCsrfToken();
            
            fetch(editSchoolYearForm.action, {
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            })
            .then(async (res) => {
                if (res.ok) {
                    let data = {};
                    try { data = await res.json(); } catch (_) {}
                    if (data && data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Operation completed but response was unexpected.');
                        window.location.reload();
                    }
                } else {
                    handleAjaxError(res, submitBtn);
                }
            })
            .catch(err => {
                console.error('Network error:', err);
                alert('Network error submitting the form. Please check your connection and try again.');
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
}

// School Year CRUD functions
function editSchoolYear(schoolYearRouteKey) {
    fetch(`/admin/school-years/${schoolYearRouteKey}/edit`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_school_id').value = data.school_id || '';
            document.getElementById('edit_school_year_start').value = data.school_year_start || '';
            document.getElementById('edit_school_year_end').value = data.school_year_end || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_start_date').value = formatDateValue(data.start_date);
            document.getElementById('edit_end_date').value = formatDateValue(data.end_date);
            document.getElementById('edit_description').value = data.description || '';

            document.getElementById('editSchoolYearForm').action = '/admin/school-years/' + schoolYearRouteKey;

            new bootstrap.Modal(document.getElementById('editSchoolYearModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading school year data: ' + error.message);
        });
}

function deleteSchoolYear(schoolYearRouteKey, name) {
    document.getElementById('deleteSchoolYearName').textContent = name || 'this school year';
    document.getElementById('deleteSchoolYearForm').action = '/admin/school-years/' + schoolYearRouteKey;
    new bootstrap.Modal(document.getElementById('deleteSchoolYearModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    setupTimeValidation();
    
    // Auto-refresh CSRF token every 30 minutes to prevent expiration
    setInterval(function() {
        refreshCsrfToken().catch(error => {
            console.log('CSRF token refresh failed:', error);
        });
    }, 30 * 60 * 1000); // 30 minutes
});
</script>
@endsection
