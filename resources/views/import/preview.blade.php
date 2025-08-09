<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Imported Students</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
</head>
<body>
    <div class="container py-4">
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

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Preview & Edit Students</h2>
            <form class="d-flex align-items-center">
                <label for="semester" class="me-2 mb-0 fw-bold">Select Semester:</label>
                <select id="semester" name="semester_id" class="form-select" style="width:auto;">
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <form id="studentsForm" action="{{ route('import.import') }}" method="POST">
            @csrf
            <input type="hidden" name="semester_id" id="selectedSemester" value="{{ $semesters->first()->id ?? '' }}">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-striped align-middle" id="studentsTable">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="width: 40px; background: #212529; color: #fff;">#</th>
                            <th style="width: 120px; background: #212529; color: #fff;">ID No</th>
                            <th style="width: 180px; background: #212529; color: #fff;">Name</th>
                            <th style="width: 80px; background: #212529; color: #fff;">Gender</th>
                            <th style="width: 60px; background: #212529; color: #fff;">Age</th>
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
            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <a href="{{ route('teacher.students') }}" class="btn btn-info">
                    &larr; Return
                </a>
                <button type="submit" class="btn btn-success">
                    Add to List
                </button>
            </div>
            </form>
    </div>
    <script>
        // Sync semester selection with hidden input for form submission
        document.getElementById('semester').addEventListener('change', function() {
            document.getElementById('selectedSemester').value = this.value;
        });

        // Remove row logic
        document.querySelectorAll('.remove-row').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const row = btn.closest('tr');
                row.parentNode.removeChild(row);
                updateRowNumbers();
            });
        });

        // Add Row Functionality
        document.getElementById('addRowBtn').addEventListener('click', function() {
            addNewStudentRow();
        });

        function addNewStudentRow() {
            let tbody = document.querySelector('#studentsTable tbody');
            let addRowBtnRow = document.getElementById('addRowBtn').closest('tr');
            let rowCount = tbody.querySelectorAll('tr').length - 1; // exclude the add row button row
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
            // Attach remove event to the new row
            newRow.querySelector('.remove-row').addEventListener('click', function() {
                newRow.parentNode.removeChild(newRow);
                updateRowNumbers();
            });
            updateRowNumbers();
        }

        // Update row numbers and total after removing a row
        function updateRowNumbers() {
            let rows = document.querySelectorAll('#studentsTable tbody tr');
            let total = 0;
            rows.forEach(function(row, idx) {
                row.querySelector('.row-number').textContent = idx + 1;
                total++;
            });
            document.getElementById('totalStudents').textContent = total;
        }
    </script>
</body>
</html>