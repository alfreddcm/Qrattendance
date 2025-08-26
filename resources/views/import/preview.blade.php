<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Imported Students</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Materialize-inspired compact design */
        body {
            background: #f8f9fa;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .container {
            max-width: 100%;
            margin: 12px auto 0 auto;
            padding: 8px 16px 16px 16px;
            background: transparent;
        }
        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0;
            color: #263238;
        }
        .form-select, .form-control {
            padding: 2px 8px;
            font-size: 0.95rem;
            border-radius: 4px;
            min-height: 28px;
        }
        .form-select-sm, .form-control-sm {
            padding: 1px 6px;
            font-size: 0.92rem;
            min-height: 24px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th, .table td {
             vertical-align: middle;
            font-size: 0.97rem;
        }
        .table thead th {
            background: #1976d2 !important;
            color: #fff !important;
            border-bottom: 2px solid #1565c0;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f0f4f8;
        }
        .table-secondary {
            background: #e3eaf2 !important;
        }
        .btn {
            border-radius: 4px;
            font-size: 0.97rem;
            padding: 4px 14px;
            min-width: 80px;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-primary {
            background: #1976d2;
            border: none;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .btn-success {
            background: #43a047;
            border: none;
        }
        .btn-success:hover {
            background: #388e3c;
        }
        .btn-info {
            background: #00bcd4;
            border: none;
            color: #fff;
        }
        .btn-info:hover {
            background: #0097a7;
        }
        .btn-danger {
            background: #e53935;
            border: none;
        }
        .btn-danger:hover {
            background: #b71c1c;
        }
        .btn:focus {
            box-shadow: 0 0 0 2px #1976d2aa;
        }
        .alert {
            padding: 6px 16px;
            font-size: 0.98rem;
            margin-bottom: 10px;
        }
        .d-flex {
            gap: 8px;
        }
        .mb-3, .mt-4, .mb-2 {
            margin-bottom: 10px !important;
            margin-top: 10px !important;
        }
        .py-4 {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
        }
        .fw-bold {
            font-weight: 500 !important;
        }
        .form-select, .form-control {
            box-shadow: none;
        }
        .form-select:focus, .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 1.5px #1976d2aa;
        }
        /* Configuration Panel Styles */
        .card {
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-label {
            color: #495057;
            margin-bottom: 4px;
        }
        .form-text {
            color: #6c757d;
            margin-top: 2px;
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        /* Enhanced form controls */
        .form-select:focus, .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
        }
        
        /* Status indicator styles */
        #selectionStatus {
            transition: all 0.3s ease;
        }
        #selectionStatus.ready {
            background-color: #28a745 !important;
        }
        #selectionStatus.warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        #selectionStatus.danger {
            background-color: #dc3545 !important;
        }
        
        /* Disabled state improvements */
        .form-select:disabled, .form-control:disabled {
            background-color: #f8f9fa;
            opacity: 0.6;
        }
        
        /* Disabled button styling */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #addRowBtn {
            min-width: 90px;
        }
        @media (max-width: 900px) {
            .container { padding: 6px 2px; }
            .table th, .table td { font-size: 0.93rem; }
        }
    </style>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</head>
<body>
    <div class="container">
        {{-- Success/Error Message --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @elseif(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @elseif(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Section -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">Preview & Edit Students</h2>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Configure assignment details and review student data before importing</p>
                </div>
                <div class="text-end">
                    <div class="badge bg-light text-dark" style="font-size: 0.85rem;">
                        <i class="bi bi-people-fill"></i> Total: <span id="totalStudentsHeader">0</span> students
                    </div>
                </div>
            </div>

            <!-- Assignment Configuration Panel -->
            <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-header bg-transparent border-0 pb-1">
                    <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                        <i class="bi bi-gear-fill me-2"></i>Assignment Configuration
                    </h6>
                </div>
                <div class="card-body pt-2 pb-3">
                    @php
                        $user = Auth::user();
                        $userSections = $user->role === 'teacher' ? \App\Models\Section::where('teacher_id', $user->id)->get() : collect();
                    @endphp
                    
                    @if($user->role === 'admin')
                        <!-- Admin Configuration Grid -->
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="admin_school_id" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-building me-1"></i>School
                                </label>
                                <select id="admin_school_id" name="school_id" class="form-select" onchange="loadSchoolData()" style="font-size: 0.9rem;">
                                    <option value="">Choose school...</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Select the target school</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="admin_semester_id" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-calendar3 me-1"></i>Semester
                                </label>
                                <select id="admin_semester_id" name="semester_id" class="form-select" onchange="loadTeachersBySchool()" style="font-size: 0.9rem;">
                                    <option value="">Choose semester...</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Select academic period</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="admin_teacher_id" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-person-badge me-1"></i>Teacher
                                </label>
                                <select id="admin_teacher_id" name="user_id" class="form-select" onchange="loadSectionsByTeacher()" disabled style="font-size: 0.9rem;">
                                    <option value="">Choose teacher...</option>
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Select assigned teacher</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="admin_section_id" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-diagram-3 me-1"></i>Section
                                </label>
                                <select id="admin_section_id" name="section_id" class="form-select" onchange="updateSectionInfo()" disabled style="font-size: 0.9rem;">
                                    <option value="">Choose section...</option>
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Select target section</div>
                            </div>
                        </div>
                        
                    @elseif($user->role === 'teacher')
                        <!-- Teacher Configuration Grid -->
                        <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
                        
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="teacher_school" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-building me-1"></i>School
                                </label>
                                <input type="text" id="teacher_school" class="form-control" value="{{ $user->school->name ?? 'No School Assigned' }}" readonly style="background-color: #f8f9fa; font-size: 0.9rem;">
                                <div class="form-text" style="font-size: 0.75rem;">Your assigned school</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="teacher_name" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-person-badge me-1"></i>Teacher
                                </label>
                                <input type="text" id="teacher_name" class="form-control" value="{{ $user->name }}" readonly style="background-color: #f8f9fa; font-size: 0.9rem;">
                                <div class="form-text" style="font-size: 0.75rem;">Current user</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="teacher_semester" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-calendar3 me-1"></i>Semester
                                </label>
                                <select id="teacher_semester" name="semester_id" class="form-select" onchange="updateTeacherSections()" style="font-size: 0.9rem;">
                                    <option value="">Choose semester...</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Select academic period</div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="teacher_section" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 600;">
                                    <i class="bi bi-diagram-3 me-1"></i>Section
                                </label>
                                <select id="teacher_section" name="section_id" class="form-select" onchange="updateTeacherSectionInfo()" style="font-size: 0.9rem;">
                                    <option value="">Choose section...</option>
                                    @if($userSections->count() > 0)
                                        @foreach($userSections as $section)
                                            <option value="{{ $section->id }}" data-teacher="{{ $section->teacher_id }}" data-semester="{{ $section->semester_id }}" data-gradelevel="{{ $section->gradelevel }}" data-name="{{ $section->name }}">
                                                Grade {{ $section->gradelevel }} - {{ $section->name }}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">No sections assigned</option>
                                    @endif
                                </select>
                                <div class="form-text" style="font-size: 0.75rem;">Your assigned sections</div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Selection Status Indicator -->
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-2" id="selectionStatus">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Complete selection required
                                </span>
                                <small class="text-muted" id="selectionDetails">Please select all required fields to proceed with import</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <small class="text-muted me-2">Ready to import:</small>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="readyToImport" disabled>
                                    <label class="form-check-label" for="readyToImport"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form id="studentsForm" action="{{ route('import.import') }}" method="POST">
            @csrf
            @if(auth()->user()->role === 'admin')
                <input type="hidden" name="user_id" id="selectedUserId" value="">
                <input type="hidden" name="semester_id" id="selectedSemesterId" value="">
                <input type="hidden" name="section_id" id="selectedSectionId" value="">
                <input type="hidden" name="school_id" id="selectedSchoolId" value="">
            @else
                <input type="hidden" name="user_id" id="selecteduser_id" value="{{ $user->id }}">
                <input type="hidden" name="semester_id" id="selectedSemester" value="">
                <input type="hidden" name="section_id" id="selectedSection" value="">
            @endif
            <div class="table-responsive" style="height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-striped align-middle mb-0" id="studentsTable" style="border-radius: 1px; overflow: hidden;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="width: 20px; background: #212529; color: #fff;">#</th>
                            <th style="width: 120px; background: #212529; color: #fff;">ID No</th>
                            <th style="width: 180px; background: #212529; color: #fff;">Name</th>
                            <th style="width: 80px; background: #212529; color: #fff;">Gender</th>
                            <th style="width: 70px; background: #212529; color: #fff;">Age</th>
                            <th style="min-width: 150px; background: #212529; color: #fff;">Address</th>
                            <th style="width: 120px; background: #212529; color: #fff;">CP No</th>
                            <th style="width: 120px; background: #212529; color: #fff;">Contact Name</th>
                            <th style="width: 100px; background: #212529; color: #fff;">Relationship</th>
                            <th style="width: 120px; background: #212529; color: #fff;">Contact Phone</th>
                            <th style="width: 100px; background: #212529; color: #fff;">Grade Level</th>
                            <th style="width: 120px; background: #212529; color: #fff;">Section Name</th>
                            <th style="width: 60px; background: #212529; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($data, 1) as $i => $row)
                            @if(count($row) >= 3)
                            <tr>
                                <td class="row-number"></td>
                                <td><input type="text" name="students[{{ $i }}][0]" value="{{ $row[0] ?? '' }}" class="form-control form-control-sm w-100" required></td>
                                <td><input type="text" name="students[{{ $i }}][1]" value="{{ $row[1] ?? '' }}" class="form-control form-control-sm w-100" required></td>
                                <td>
                                    <select name="students[{{ $i }}][2]" class="form-select form-select-sm w-100" required>
                                        <option value="M" {{ (isset($row[2]) && (strtolower($row[2]) == 'male' || strtolower($row[2]) == 'm')) ? 'selected' : '' }}>Male</option>
                                        <option value="F" {{ (isset($row[2]) && (strtolower($row[2]) == 'female' || strtolower($row[2]) == 'f')) ? 'selected' : '' }}>Female</option>
                                    </select>
                                </td>
                                <td><input type="number" name="students[{{ $i }}][3]" value="{{ $row[3] ?? '' }}" class="form-control form-control-sm w-100" required></td>
                                <td><input type="text" name="students[{{ $i }}][4]" value="{{ $row[4] ?? '' }}" class="form-control form-control-sm w-100"></td>
                                <td><input type="text" name="students[{{ $i }}][5]" value="{{ $row[5] ?? '' }}" class="form-control form-control-sm w-100"></td>
                                <td><input type="text" name="students[{{ $i }}][6]" value="{{ $row[6] ?? '' }}" class="form-control form-control-sm w-100"></td>
                                <td><input type="text" name="students[{{ $i }}][7]" value="{{ $row[7] ?? '' }}" class="form-control form-control-sm w-100"></td>
                                <td><input type="text" name="students[{{ $i }}][8]" value="{{ $row[8] ?? '' }}" class="form-control form-control-sm w-100"></td>
                                <td class="grade-level-cell">
                                    <span class="text-muted">Select section</span>
                                </td>
                                <td class="section-name-cell">
                                    <span class="text-muted">Select section</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5.5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6zm3 .5a.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6zm-7-1A1.5 1.5 0 0 1 5.5 4h5A1.5 1.5 0 0 1 12 5.5V6h1a.5.5 0 0 1 0 1h-1v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7H3a.5.5 0 0 1 0-1h1v-.5zM5.5 5a.5.5 0 0 0-.5.5V6h6v-.5a.5.5 0 0 0-.5-.5h-5z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                        <tr>
                            <td colspan="13" class="text-center">
                                <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                    Add Row
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary" style="position: sticky; bottom: 0; z-index: 2;">
                            <td colspan="13" class="text-end fw-bold">
                                Total Students: <span id="totalStudents">0</span>
                            </td>
                            
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <a href="{{ route('teacher.students') }}" class="btn btn-info d-flex align-items-center" style="min-width: 110px;">
                    <i class="bi bi-arrow-left me-2"></i>Return
                </a>
                <button type="submit" class="btn btn-success d-flex align-items-center" id="addToListBtn" disabled style="min-width: 130px;">
                    <i class="bi bi-plus-circle me-2"></i>Add to List
                </button>
            </div>
            </form>
    </div>
    <script>
        // Global validation state
        let validationState = {
            school: false,
            semester: false,
            teacher: false,
            section: false
        };

        // Update selection status and enable/disable submit button
        function updateSelectionStatus() {
            const status = document.getElementById('selectionStatus');
            const details = document.getElementById('selectionDetails');
            const readySwitch = document.getElementById('readyToImport');
            const submitBtn = document.getElementById('addToListBtn');
            
            @if($user->role === 'admin')
                const allSelected = validationState.school && validationState.semester && validationState.teacher && validationState.section;
            @else
                const allSelected = validationState.semester && validationState.section;
            @endif
            
            if (allSelected) {
                status.className = 'badge bg-success me-2';
                status.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Ready to import';
                details.textContent = 'All required selections completed. You can now proceed with the import.';
                readySwitch.checked = true;
                readySwitch.disabled = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add to List';
            } else {
                status.className = 'badge bg-warning text-dark me-2';
                status.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Complete selection required';
                details.textContent = 'Please select all required fields to proceed with import';
                readySwitch.checked = false;
                readySwitch.disabled = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Complete Selection First';
            }
        }

        // Initialize validation state on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectionStatus();
            updateRowNumbers(); // Initialize row numbers and totals
        });

        // Teacher-specific functions
        @if($user->role === 'teacher')
        document.getElementById('teacher_semester').addEventListener('change', function() {
            document.getElementById('selectedSemester').value = this.value;
            validationState.semester = this.value !== '';
            updateTeacherSections();
            updateSelectionStatus();
        });
        
        document.getElementById('teacher_section').addEventListener('change', function() {
            document.getElementById('selectedSection').value = this.value;
            validationState.section = this.value !== '';
            updateTeacherSectionInfo();
            updateSelectionStatus();
        });

        function updateTeacherSections() {
            const semesterSelect = document.getElementById('teacher_semester');
            const sectionSelect = document.getElementById('teacher_section');
            const selectedSemesterId = semesterSelect.value;
            
            // Reset section validation when semester changes
            validationState.section = false;
            
            if (!selectedSemesterId) {
                sectionSelect.innerHTML = '<option value="">Choose semester first...</option>';
                sectionSelect.disabled = true;
                updateSelectionStatus();
                return;
            }
            
            // Filter sections by selected semester
            sectionSelect.innerHTML = '<option value="">Choose section...</option>';
            sectionSelect.disabled = false;
            @foreach($userSections as $section)
                if ('{{ $section->semester_id }}' === selectedSemesterId) {
                    sectionSelect.innerHTML += '<option value="{{ $section->id }}" data-gradelevel="{{ $section->gradelevel }}" data-name="{{ $section->name }}">Grade {{ $section->gradelevel }} - {{ $section->name }}</option>';
                }
            @endforeach
        }

        function updateTeacherSectionInfo() {
            const sectionSelect = document.getElementById('teacher_section');
            const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                const gradeLevel = selectedOption.getAttribute('data-gradelevel') || 'N/A';
                const sectionName = selectedOption.getAttribute('data-name') || 'N/A';
                
                // Update all existing rows with section info
                document.querySelectorAll('.grade-level-cell').forEach(cell => {
                    cell.textContent = gradeLevel;
                });
                document.querySelectorAll('.section-name-cell').forEach(cell => {
                    cell.textContent = sectionName;
                });
            } else {
                document.querySelectorAll('.grade-level-cell').forEach(cell => {
                    cell.innerHTML = '<span class="text-muted">Select section</span>';
                });
                document.querySelectorAll('.section-name-cell').forEach(cell => {
                    cell.innerHTML = '<span class="text-muted">Select section</span>';
                });
            }
        }
        @endif

        // Admin-specific functions
        @if($user->role === 'admin')
        function loadSchoolData() {
            const schoolSelect = document.getElementById('admin_school_id');
            const semesterSelect = document.getElementById('admin_semester_id');
            const teacherSelect = document.getElementById('admin_teacher_id');
            const sectionSelect = document.getElementById('admin_section_id');
            
            document.getElementById('selectedSchoolId').value = schoolSelect.value;
            validationState.school = schoolSelect.value !== '';
            
            // Reset dependent validations
            validationState.teacher = false;
            validationState.section = false;
            
            if (!schoolSelect.value) {
                resetDropdown(teacherSelect, 'Choose school first...');
                resetDropdown(sectionSelect, 'Choose teacher first...');
                semesterSelect.disabled = true;
                updateSelectionStatus();
                return;
            }
            
            // Enable semester if school is selected
            semesterSelect.disabled = false;
            loadTeachersBySchool();
        }
        
        function loadTeachersBySchool() {
            const schoolSelect = document.getElementById('admin_school_id');
            const semesterSelect = document.getElementById('admin_semester_id');
            const teacherSelect = document.getElementById('admin_teacher_id');
            const sectionSelect = document.getElementById('admin_section_id');
            
            document.getElementById('selectedSemesterId').value = semesterSelect.value;
            validationState.semester = semesterSelect.value !== '';
            
            // Reset dependent validations
            validationState.teacher = false;
            validationState.section = false;
            
            if (!schoolSelect.value || !semesterSelect.value) {
                resetDropdown(teacherSelect, 'Choose school and semester first...');
                resetDropdown(sectionSelect, 'Choose teacher first...');
                updateSelectionStatus();
                return;
            }
            
            // Show loading state
            teacherSelect.innerHTML = '<option value="">Loading teachers...</option>';
            teacherSelect.disabled = true;
            resetDropdown(sectionSelect, 'Choose teacher first...');
            
            // Fetch teachers for the selected school
            fetch(`/admin/schools/${schoolSelect.value}/teachers`)
                .then(response => response.json())
                .then(data => {
                    teacherSelect.innerHTML = '<option value="">Choose teacher...</option>';
                    data.forEach(teacher => {
                        teacherSelect.innerHTML += `<option value="${teacher.id}">${teacher.name}</option>`;
                    });
                    teacherSelect.disabled = false;
                    updateSelectionStatus();
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                    teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
                    teacherSelect.disabled = false;
                    updateSelectionStatus();
                });
        }
                });
        }
        
        function loadSectionsByTeacher() {
            const teacherSelect = document.getElementById('admin_teacher_id');
            const semesterSelect = document.getElementById('admin_semester_id');
            const sectionSelect = document.getElementById('admin_section_id');
            
            document.getElementById('selectedUserId').value = teacherSelect.value;
            validationState.teacher = teacherSelect.value !== '';
            
            // Reset section validation
            validationState.section = false;
            
            if (!teacherSelect.value) {
                resetDropdown(sectionSelect, 'Choose teacher first...');
                updateSelectionStatus();
                return;
            }
            
            // Show loading state
            sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
            sectionSelect.disabled = true;
            
            // Fetch sections for the selected teacher and semester
            fetch(`/admin/teachers/${teacherSelect.value}/sections?semester_id=${semesterSelect.value}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Choose section...</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section.id}" data-gradelevel="${section.gradelevel}" data-name="${section.name}">Grade ${section.gradelevel} - ${section.name}</option>`;
                    });
                    sectionSelect.disabled = false;
                    updateSelectionStatus();
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    sectionSelect.disabled = false;
                    updateSelectionStatus();
                });
        }
        
        function updateSectionInfo() {
            const sectionSelect = document.getElementById('admin_section_id');
            const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
            
            document.getElementById('selectedSectionId').value = sectionSelect.value;
            validationState.section = sectionSelect.value !== '';
            
            if (selectedOption && selectedOption.value) {
                const gradeLevel = selectedOption.getAttribute('data-gradelevel') || 'N/A';
                const sectionName = selectedOption.getAttribute('data-name') || 'N/A';
                
                // Update all existing rows with section info
                document.querySelectorAll('.grade-level-cell').forEach(cell => {
                    cell.textContent = gradeLevel;
                });
                document.querySelectorAll('.section-name-cell').forEach(cell => {
                    cell.textContent = sectionName;
                });
            } else {
                document.querySelectorAll('.grade-level-cell').forEach(cell => {
                    cell.innerHTML = '<span class="text-muted">Select section</span>';
                });
                document.querySelectorAll('.section-name-cell').forEach(cell => {
                    cell.innerHTML = '<span class="text-muted">Select section</span>';
                });
            }
            
            updateSelectionStatus();
        }
        
        function resetDropdown(selectElement, placeholder) {
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            selectElement.disabled = true;
        }
        @endif

        // Form submission validation
        document.getElementById('studentsForm').addEventListener('submit', function(e) {
            @if($user->role === 'admin')
                const allSelected = validationState.school && validationState.semester && validationState.teacher && validationState.section;
            @else
                const allSelected = validationState.semester && validationState.section;
            @endif
            
            if (!allSelected) {
                e.preventDefault();
                alert('Please complete all required selections before submitting.');
                return false;
            }
            
            // Check if there are any students to import
            const studentRows = document.querySelectorAll('#studentsTable tbody tr:not(:last-child)');
            if (studentRows.length === 0) {
                e.preventDefault();
                alert('Please add at least one student before submitting.');
                return false;
            }
        });

         function attachRemoveEvents() {
            document.querySelectorAll('.remove-row').forEach(function(btn) {
                btn.onclick = function() {
                    const row = btn.closest('tr');
                    row.parentNode.removeChild(row);
                    updateRowNumbers();
                };
            });
        }
        attachRemoveEvents();

         document.getElementById('addRowBtn').addEventListener('click', function() {
            addNewStudentRow();
        });

        function addNewStudentRow() {
            let tbody = document.querySelector('#studentsTable tbody');
            let addRowBtnRow = document.getElementById('addRowBtn').closest('tr');
            let rowCount = tbody.querySelectorAll('tr').length - 1; 
            
            // Get current section info for new rows
            let gradeLevel = 'N/A';
            let sectionName = 'N/A';
            
            @if(auth()->user()->role === 'admin')
                const sectionSelect = document.getElementById('admin_section_id');
                if (sectionSelect && sectionSelect.value) {
                    const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
                    gradeLevel = selectedOption.getAttribute('data-gradelevel') || 'N/A';
                    sectionName = selectedOption.getAttribute('data-name') || 'N/A';
                }
            @else
                const sectionSelect = document.getElementById('teacher_section');
                if (sectionSelect && sectionSelect.value) {
                    const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
                    gradeLevel = selectedOption.getAttribute('data-gradelevel') || 'N/A';
                    sectionName = selectedOption.getAttribute('data-name') || 'N/A';
                }
            @endif
            
            let newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="row-number"></td>
                <td><input type="text" name="students[new_${rowCount}][0]" class="form-control form-control-sm w-100" required></td>
                <td><input type="text" name="students[new_${rowCount}][1]" class="form-control form-control-sm w-100" required></td>
                <td>
                    <select name="students[new_${rowCount}][2]" class="form-select form-select-sm w-100" required>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </td>
                <td><input type="number" name="students[new_${rowCount}][3]" class="form-control form-control-sm w-100" required></td>
                <td><input type="text" name="students[new_${rowCount}][4]" class="form-control form-control-sm w-100"></td>
                <td><input type="text" name="students[new_${rowCount}][5]" class="form-control form-control-sm w-100"></td>
                <td><input type="text" name="students[new_${rowCount}][6]" class="form-control form-control-sm w-100"></td>
                <td><input type="text" name="students[new_${rowCount}][7]" class="form-control form-control-sm w-100"></td>
                <td><input type="text" name="students[new_${rowCount}][8]" class="form-control form-control-sm w-100"></td>
                <td class="grade-level-cell text-center">${gradeLevel}</td>
                <td class="section-name-cell text-center">${sectionName}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5.5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6zm3 .5a.5.5 0 0 1 .5-.5.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6zm-7-1A1.5 1.5 0 0 1 5.5 4h5A1.5 1.5 0 0 1 12 5.5V6h1a.5.5 0 0 1 0 1h-1v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7H3a.5.5 0 0 1 0-1h1v-.5zM5.5 5a.5.5 0 0 0-.5.5V6h6v-.5a.5.5 0 0 0-.5-.5h-5z"/>
                        </svg>
                    </button>
                </td>
            `;
            tbody.insertBefore(newRow, addRowBtnRow);
            attachRemoveEvents();
            updateRowNumbers();
        }

        // Update row numbers and total after removing a row
        function updateRowNumbers() {
            let studentRows = document.querySelectorAll('#studentsTable tbody tr:not(:last-child)'); // Exclude "Add Row" button row
            let total = 0;
            
            studentRows.forEach(function(row, idx) {
                let num = row.querySelector('.row-number');
                if (num) {
                    num.textContent = idx + 1;
                    total++;
                }
            });
            
            document.getElementById('totalStudents').textContent = total;
            document.getElementById('totalStudentsHeader').textContent = total;
        }
    </script>
</body>
</html>