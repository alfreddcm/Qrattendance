<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Imported Students</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <style>
        /* Materialize-inspired compact design */
        body {
            background: #f5f6fa;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .container {
            max-width: 90%;
            margin: 24px auto 0 auto;
            padding: 12px 18px 18px 18px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
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

        <div class="d-flex justify-content-between align-items-center mb-3" style="gap: 0;">
            <h2>Preview & Edit Students</h2>
            <form class="d-flex align-items-center" style="gap: 6px;">
                @php
                    $user = Auth::user();
                    $userSections = $user->role === 'teacher' ? \App\Models\Section::where('teacher_id', $user->id)->get() : collect();
                @endphp
                
                @if($user->role === 'admin')
                    <!-- Admin Cascading Dropdowns: School -> Semester -> Teacher -> Section -->
                    <label for="admin_school_id" class="mb-0 fw-bold" style="font-size: 1rem;">School:</label>
                    <select id="admin_school_id" name="school_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;" onchange="loadSchoolData()">
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>

                    <label for="admin_semester_id" class="mb-0 fw-bold" style="font-size: 1rem;">Semester:</label>
                    <select id="admin_semester_id" name="semester_id" class="form-select form-select-sm" style="width: auto; min-width: 150px;" onchange="loadTeachersBySchool()">
                        <option value="">Select Semester</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                        @endforeach
                    </select>

                    <label for="admin_teacher_id" class="mb-0 fw-bold" style="font-size: 1rem;">Teacher:</label>
                    <select id="admin_teacher_id" name="user_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;" onchange="loadSectionsByTeacher()" disabled>
                        <option value="">Select Teacher</option>
                    </select>

                    <label for="admin_section_id" class="mb-0 fw-bold" style="font-size: 1rem;">Section:</label>
                    <select id="admin_section_id" name="section_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;" onchange="updateSectionInfo()" disabled>
                        <option value="">Select Section</option>
                    </select>
                    
                @elseif($user->role === 'teacher')
                    <!-- Teacher: Auto-populated School and Teacher, Selectable Semester and Section -->
                    <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
                    
                    <label for="teacher_school" class="mb-0 fw-bold" style="font-size: 1rem;">School:</label>
                    <input type="text" id="teacher_school" class="form-control form-control-sm" style="width: auto; min-width: 170px; background-color: #f8f9fa;" value="{{ $user->school->name ?? 'No School Assigned' }}" readonly>
                    
                    <label for="teacher_name" class="mb-0 fw-bold" style="font-size: 1rem;">Teacher:</label>
                    <input type="text" id="teacher_name" class="form-control form-control-sm" style="width: auto; min-width: 170px; background-color: #f8f9fa;" value="{{ $user->name }}" readonly>
                    
                    <label for="teacher_semester" class="mb-0 fw-bold" style="font-size: 1rem;">Semester:</label>
                    <select id="teacher_semester" name="semester_id" class="form-select form-select-sm" style="width: auto; min-width: 150px;" onchange="updateTeacherSections()">
                        <option value="">Select Semester</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                        @endforeach
                    </select>

                    <label for="teacher_section" class="mb-0 fw-bold" style="font-size: 1rem;">Section:</label>
                    <select id="teacher_section" name="section_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;" onchange="updateTeacherSectionInfo()">
                        <option value="">Select Section</option>
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
                @endif
            </form>
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
                        @php $total = 0; @endphp
                        @foreach(array_slice($data, 1) as $i => $row)
                            @if(count($row) >= 3)
                            @php $total++; @endphp
                            <tr>
                                <td class="row-number">{{ $total }}</td>
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
                                Total Students: <span id="totalStudents">{{ $total }}</span>
                            </td>
                            
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4 mb-2" style="gap: 0;">
                <a href="{{ route('teacher.students') }}" class="btn btn-info" style="min-width: 90px;">&larr; Return</a>
                <button type="submit" class="btn btn-success" style="min-width: 110px;">Add to List</button>
            </div>
            </form>
    </div>
    <script>
        // Teacher-specific functions
        @if($user->role === 'teacher')
        document.getElementById('teacher_semester').addEventListener('change', function() {
            document.getElementById('selectedSemester').value = this.value;
            updateTeacherSections();
        });
        
        document.getElementById('teacher_section').addEventListener('change', function() {
            document.getElementById('selectedSection').value = this.value;
            updateTeacherSectionInfo();
        });

        function updateTeacherSections() {
            const semesterSelect = document.getElementById('teacher_semester');
            const sectionSelect = document.getElementById('teacher_section');
            const selectedSemesterId = semesterSelect.value;
            
            if (!selectedSemesterId) {
                sectionSelect.innerHTML = '<option value="">Select Semester first</option>';
                return;
            }
            
            // Filter sections by selected semester
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
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
            
            if (!schoolSelect.value) {
                resetDropdown(teacherSelect, 'Select School first');
                resetDropdown(sectionSelect, 'Select Teacher first');
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
            
            if (!schoolSelect.value || !semesterSelect.value) {
                resetDropdown(teacherSelect, 'Select School and Semester first');
                resetDropdown(sectionSelect, 'Select Teacher first');
                return;
            }
            
            // Show loading state
            teacherSelect.innerHTML = '<option value="">Loading teachers...</option>';
            teacherSelect.disabled = true;
            resetDropdown(sectionSelect, 'Select Teacher first');
            
            // Fetch teachers for the selected school
            fetch(`/admin/schools/${schoolSelect.value}/teachers`)
                .then(response => response.json())
                .then(data => {
                    teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                    data.forEach(teacher => {
                        teacherSelect.innerHTML += `<option value="${teacher.id}">${teacher.name}</option>`;
                    });
                    teacherSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                    teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
                    teacherSelect.disabled = false;
                });
        }
        
        function loadSectionsByTeacher() {
            const teacherSelect = document.getElementById('admin_teacher_id');
            const semesterSelect = document.getElementById('admin_semester_id');
            const sectionSelect = document.getElementById('admin_section_id');
            
            document.getElementById('selectedUserId').value = teacherSelect.value;
            
            if (!teacherSelect.value) {
                resetDropdown(sectionSelect, 'Select Teacher first');
                return;
            }
            
            // Show loading state
            sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
            sectionSelect.disabled = true;
            
            // Fetch sections for the selected teacher and semester
            fetch(`/admin/teachers/${teacherSelect.value}/sections?semester_id=${semesterSelect.value}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section.id}" data-gradelevel="${section.gradelevel}" data-name="${section.name}">Grade ${section.gradelevel} - ${section.name}</option>`;
                    });
                    sectionSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    sectionSelect.disabled = false;
                });
        }
        
        function updateSectionInfo() {
            const sectionSelect = document.getElementById('admin_section_id');
            const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
            
            document.getElementById('selectedSectionId').value = sectionSelect.value;
            
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
        
        function resetDropdown(selectElement, placeholder) {
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            selectElement.disabled = true;
        }
        @endif

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
            let rows = document.querySelectorAll('#studentsTable tbody tr');
            let total = 0;
            rows.forEach(function(row, idx) {
                let num = row.querySelector('.row-number');
                if (num) {
                    num.textContent = idx + 1;
                    total++;
                }
            });
            document.getElementById('totalStudents').textContent = total - 1; // Subtract 1 for the "Add Row" button row
        }
    </script>
</body>
</html>