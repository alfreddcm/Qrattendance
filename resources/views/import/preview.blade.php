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
                    $teachers = \App\Models\User::where('role', 'teacher')->get();
                @endphp
                @if($user->role === 'admin')
                    <label for="user_id" class="mb-0 fw-bold" style="font-size: 1rem;">Select Teacher:</label>
                    <select id="user_id" name="user_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;">
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" data-school="{{ $teacher->school_id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="school_id" id="school_id" value="">
                @elseif($user->role === 'teacher')
                    <input type="hidden" name="user_id" id="user_id" value="{{ $user->id }}">
                 @endif

                <label for="semester" class="mb-0 fw-bold" style="font-size: 1rem;">Select Semester:</label>
                <select id="semester" name="semester_id" class="form-select form-select-sm" style="width: auto; min-width: 170px;">
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <form id="studentsForm" action="{{ route('import.import') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" id="selecteduser_id" value="{{ $user->id }}">
            <input type="hidden" name="semester_id" id="selectedSemester" value="{{ $semesters->first()->id ?? '' }}">
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
                            <td colspan="11" class="text-center">
                                <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                    Add Row
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary" style="position: sticky; bottom: 0; z-index: 2;">
                            <td colspan="11" class="text-end fw-bold">
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
         document.getElementById('semester').addEventListener('change', function() {
            document.getElementById('selectedSemester').value = this.value;
        });
         document.getElementById('user_id').addEventListener('change', function() {
            document.getElementById('selecteduser_id').value = this.value;
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
                if (num) num.textContent = idx + 1;
                total++;
            });
            document.getElementById('totalStudents').textContent = total - 1;
        }

        var userSelect = document.getElementById('user_id');
        if (userSelect) {
            userSelect.addEventListener('change', function() {
                var selected = this.options[this.selectedIndex];
                var schoolId = selected.getAttribute('data-school');
                document.getElementById('school_id').value = schoolId;
            });
             setTimeout(function() {
                var event = new Event('change');
                userSelect.dispatchEvent(event);
            }, 100);
        }
    </script>
</body>
</html>