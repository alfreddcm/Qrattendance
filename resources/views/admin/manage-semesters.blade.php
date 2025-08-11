@extends('admin.sidebar')
@section('title', 'Manage Semesters')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-calendar-alt me-2"></i>
                Manage Semesters
            </h4>
            <p class="subtitle fs-6 mb-0">Create and manage academic semesters for schools</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary btn-sm px-2 py-1" data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                <i class="fas fa-plus me-1"></i>Add Semester
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

    <!-- Semesters Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white p-2">
            <h6 class="mb-0 fs-6"><i class="fas fa-list me-1"></i>All Semesters</h6>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th class="py-1 fs-6">Name</th>
                            <th class="py-1 fs-6">School</th>
                            <th class="py-1 fs-6">Duration</th>
                            <th class="py-1 fs-6">Status</th>
                            <th>Time Ranges</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                        <tr>
                            <td>
                                <strong>{{ $semester->name }}</strong>
                                @if(isset($semester->weekdays))
                                    <br><small class="text-muted">{{ $semester->weekdays }} weekdays</small>
                                @endif
                            </td>
                            <td>
                                @if($semester->school)
                                    <span class="badge bg-info">{{ $semester->school->name }}</span>
                                @else
                                    <span class="badge bg-secondary">No School</span>
                                @endif
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($semester->start_date)->format('M j, Y') }}
                                <br>
                                <small class="text-muted">to {{ \Carbon\Carbon::parse($semester->end_date)->format('M j, Y') }}</small>
                            </td>
                            <td>
                                @if($semester->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <small>
                                    @if(isset($semester->am_time_in_start_display) && isset($semester->am_time_in_end_display))
                                        <strong>AM:</strong> {{ $semester->am_time_in_start_display }} - {{ $semester->am_time_in_end_display }}<br>
                                    @elseif(isset($semester->am_time_in_start) && isset($semester->am_time_in_end))
                                        <strong>AM:</strong> {{ \Carbon\Carbon::parse($semester->am_time_in_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->am_time_in_end)->format('g:i A') }}<br>
                                    @endif
                                    
                                    @if(isset($semester->pm_time_out_start_display) && isset($semester->pm_time_out_end_display))
                                        <strong>PM:</strong> {{ $semester->pm_time_out_start_display }} - {{ $semester->pm_time_out_end_display }}
                                    @elseif(isset($semester->pm_time_out_start) && isset($semester->pm_time_out_end))
                                        <strong>PM:</strong> {{ \Carbon\Carbon::parse($semester->pm_time_out_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($semester->pm_time_out_end)->format('g:i A') }}
                                    @else
                                        <span class="text-muted">Time ranges not set</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editSemester({{ $semester->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteSemester({{ $semester->id }}, '{{ $semester->name }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                    <p>No semesters found. Create your first semester!</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $semesters->links() }}
            </div>
        </div>
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
            <form action="{{ route('admin.semester.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Semester Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="school_id" class="form-label">School</label>
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
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Time Ranges</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">AM Time In Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="time" class="form-control" name="am_time_in_start">
                                    <small class="text-muted">Start</small>
                                </div>
                                <div class="col-6">
                                    <input type="time" class="form-control" name="am_time_in_end">
                                    <small class="text-muted">End</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PM Time Out Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="time" class="form-control" name="pm_time_out_start">
                                    <small class="text-muted">Start</small>
                                </div>
                                <div class="col-6">
                                    <input type="time" class="form-control" name="pm_time_out_end">
                                    <small class="text-muted">End</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Semester</button>
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
                                <label for="edit_name" class="form-label">Semester Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_school_id" class="form-label">School</label>
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Time Ranges</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">AM Time In Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="time" class="form-control" id="edit_am_time_in_start" name="am_time_in_start">
                                    <small class="text-muted">Start</small>
                                </div>
                                <div class="col-6">
                                    <input type="time" class="form-control" id="edit_am_time_in_end" name="am_time_in_end">
                                    <small class="text-muted">End</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PM Time Out Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="time" class="form-control" id="edit_pm_time_out_start" name="pm_time_out_start">
                                    <small class="text-muted">Start</small>
                                </div>
                                <div class="col-6">
                                    <input type="time" class="form-control" id="edit_pm_time_out_end" name="pm_time_out_end">
                                    <small class="text-muted">End</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteSemesterModal" tabindex="-1" aria-labelledby="deleteSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSemesterModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the semester "<span id="deleteSemesterName"></span>"?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will affect all associated data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteSemesterForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Semester</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editSemester(semesterId) {
    fetch(`/admin/semesters/${semesterId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_start_date').value = data.start_date || '';
            document.getElementById('edit_end_date').value = data.end_date || '';
            document.getElementById('edit_school_id').value = data.school_id || '';
            document.getElementById('edit_am_time_in_start').value = data.am_time_in_start_input || '';
            document.getElementById('edit_am_time_in_end').value = data.am_time_in_end_input || '';
            document.getElementById('edit_pm_time_out_start').value = data.pm_time_out_start_input || '';
            document.getElementById('edit_pm_time_out_end').value = data.pm_time_out_end_input || '';
            
            document.getElementById('editSemesterForm').action = '/admin/semesters/' + semesterId;
            
            new bootstrap.Modal(document.getElementById('editSemesterModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading semester data');
        });
}

function deleteSemester(id, name) {
    try {
        document.getElementById('deleteSemesterName').textContent = name || 'this semester';
        document.getElementById('deleteSemesterForm').action = '/admin/semesters/' + id;
        
        new bootstrap.Modal(document.getElementById('deleteSemesterModal')).show();
    } catch (error) {
        console.error('Error opening delete modal:', error);
        alert('Error opening delete confirmation. Please refresh the page and try again.');
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add validation for time ranges
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const amStart = form.querySelector('[name="am_time_in_start"]');
            const amEnd = form.querySelector('[name="am_time_in_end"]');
            const pmStart = form.querySelector('[name="pm_time_out_start"]');
            const pmEnd = form.querySelector('[name="pm_time_out_end"]');
            
            if (amStart && amEnd && amStart.value >= amEnd.value) {
                e.preventDefault();
                alert('AM time in end must be after AM time in start');
                return false;
            }
            
            if (pmStart && pmEnd && pmStart.value >= pmEnd.value) {
                e.preventDefault();
                alert('PM time out end must be after PM time out start');
                return false;
            }
        });
    });
});
</script>

@endsection
