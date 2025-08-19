@extends('teacher/sidebar')
@section('title', 'Import Students')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">📤</span>
                    Import Students
                </h4>
                <p class="subtitle mb-0">Bulk import students from Excel/CSV files</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('teacher.students') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Students
                </a>
                <button class="btn btn-info btn-sm" onclick="downloadTemplate()">
                    <i class="fas fa-download me-1"></i>Download Template
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Import Instructions -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-info-circle me-1"></i>Import Instructions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-excel me-1"></i>File Requirements</h6>
                        <ul class="small mb-3">
                            <li>Supported formats: .xlsx, .xls, .csv</li>
                            <li>Maximum file size: 5MB</li>
                            <li>First row should contain column headers</li>
                            <li>Required fields: ID No, Name</li>
                        </ul>
                        
                        <h6><i class="fas fa-columns me-1"></i>Column Mapping</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Column</th>
                                        <th>Required</th>
                                        <th>Example</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>id_no / student_id</td>
                                        <td><span class="badge bg-danger">Required</span></td>
                                        <td>2024001</td>
                                    </tr>
                                    <tr>
                                        <td>name / full_name</td>
                                        <td><span class="badge bg-danger">Required</span></td>
                                        <td>John Doe</td>
                                    </tr>
                                    <tr>
                                        <td>section_name</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>STEM</td>
                                    </tr>
                                    <tr>
                                        <td>grade_level</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>11</td>
                                    </tr>
                                    <tr>
                                        <td>gender</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>M / F</td>
                                    </tr>
                                    <tr>
                                        <td>age</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>17</td>
                                    </tr>
                                    <tr>
                                        <td>contact_number</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>09123456789</td>
                                    </tr>
                                    <tr>
                                        <td>address</td>
                                        <td><span class="badge bg-warning">Optional</span></td>
                                        <td>123 Main St.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6><i class="fas fa-cogs me-1"></i>Auto-Section Creation</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-magic me-1"></i>
                            <strong>Smart Section Handling:</strong>
                            <ul class="mb-0">
                                <li>If section doesn't exist, it will be created automatically</li>
                                <li>New sections will be assigned to you</li>
                                <li>Students will be assigned to their respective sections</li>
                                <li>Default section created if none specified</li>
                            </ul>
                        </div>
                        
                        <h6><i class="fas fa-exclamation-triangle me-1"></i>Important Notes</h6>
                        <ul class="small text-muted">
                            <li>Duplicate student IDs will be skipped</li>
                            <li>Invalid data rows will be reported</li>
                            <li>Import progress will be shown in real-time</li>
                            <li>You can review errors after import completion</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-upload me-1"></i>Upload Student Data
            </div>
            <div class="card-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="semester_id" class="form-label">Target Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @if(isset($semesters))
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="import_file" class="form-label">Select File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="create_sections" name="create_sections" checked>
                            <label class="form-check-label" for="create_sections">
                                <i class="fas fa-plus-circle me-1"></i>
                                Automatically create sections that don't exist
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                            <label class="form-check-label" for="skip_duplicates">
                                <i class="fas fa-shield-alt me-1"></i>
                                Skip duplicate student IDs
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="validate_data" name="validate_data" checked>
                            <label class="form-check-label" for="validate_data">
                                <i class="fas fa-check-circle me-1"></i>
                                Validate data before import
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-1"></i>Start Import
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Progress -->
        <div id="progressCard" class="card" style="display: none;">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-spinner fa-spin me-1"></i>Import Progress
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="progressText">Preparing import...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div id="progressDetails" class="small text-muted"></div>
            </div>
        </div>

        <!-- Import Results -->
        <div id="resultsCard" class="card" style="display: none;">
            <div class="card-header">
                <i class="fas fa-chart-bar me-1"></i>Import Results
            </div>
            <div class="card-body">
                <div class="row" id="resultsSummary">
                    <!-- Results will be populated here -->
                </div>
                
                <!-- Errors Table -->
                <div id="errorsSection" style="display: none;">
                    <h6 class="mt-3 mb-2"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Import Errors</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped" id="errorsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Row</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody id="errorsTableBody">
                                <!-- Error rows will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Created Sections -->
                <div id="sectionsSection" style="display: none;">
                    <h6 class="mt-3 mb-2"><i class="fas fa-plus-circle text-success me-1"></i>Created Sections</h6>
                    <div id="createdSectionsList" class="d-flex flex-wrap gap-2">
                        <!-- Created sections will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const progressCard = document.getElementById('progressCard');
    const resultsCard = document.getElementById('resultsCard');
    
    // Show progress
    progressCard.style.display = 'block';
    resultsCard.style.display = 'none';
    
    // Start import
    fetch('{{ route("teacher.students.import") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        progressCard.style.display = 'none';
        
        if (data.success) {
            showImportResults(data);
        } else {
            alert('Import failed: ' + data.message);
        }
    })
    .catch(error => {
        progressCard.style.display = 'none';
        console.error('Error:', error);
        alert('Import failed: Network error');
    });
});

function showImportResults(data) {
    const resultsCard = document.getElementById('resultsCard');
    const resultsSummary = document.getElementById('resultsSummary');
    
    // Summary statistics
    resultsSummary.innerHTML = `
        <div class="col-md-3">
            <div class="text-center">
                <div class="h3 text-success mb-0">${data.imported || 0}</div>
                <small class="text-muted">Students Imported</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="h3 text-warning mb-0">${data.errors ? data.errors.length : 0}</div>
                <small class="text-muted">Errors</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="h3 text-info mb-0">${data.sections_created || 0}</div>
                <small class="text-muted">Sections Created</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <div class="h3 text-primary mb-0">${data.total_processed || 0}</div>
                <small class="text-muted">Total Processed</small>
            </div>
        </div>
    `;
    
    // Show errors if any
    if (data.errors && data.errors.length > 0) {
        const errorsSection = document.getElementById('errorsSection');
        const errorsTableBody = document.getElementById('errorsTableBody');
        
        errorsTableBody.innerHTML = data.errors.map((error, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${error.row.id_no || error.row.student_id || 'N/A'}</td>
                <td>${error.row.name || error.row.full_name || 'N/A'}</td>
                <td class="text-danger">${error.error}</td>
            </tr>
        `).join('');
        
        errorsSection.style.display = 'block';
    }
    
    // Show created sections if any
    if (data.created_sections && Object.keys(data.created_sections).length > 0) {
        const sectionsSection = document.getElementById('sectionsSection');
        const createdSectionsList = document.getElementById('createdSectionsList');
        
        createdSectionsList.innerHTML = Object.keys(data.created_sections).map(sectionKey => {
            const [name, grade] = sectionKey.split('_');
            return `<span class="badge bg-success">${name} - Grade ${grade}</span>`;
        }).join('');
        
        sectionsSection.style.display = 'block';
    }
    
    resultsCard.style.display = 'block';
}

function downloadTemplate() {
    // Create a simple template file
    const csvContent = "id_no,name,section_name,grade_level,gender,age,contact_number,address,emergency_contact_name,emergency_contact_relationship,emergency_contact_number\n" +
                       "2024001,John Doe,STEM,11,M,17,09123456789,123 Main St,Jane Doe,Mother,09987654321\n" +
                       "2024002,Jane Smith,HUMSS,11,F,16,09111222333,456 Oak Ave,John Smith,Father,09888777666";
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'student_import_template.csv';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

function resetForm() {
    document.getElementById('importForm').reset();
    document.getElementById('progressCard').style.display = 'none';
    document.getElementById('resultsCard').style.display = 'none';
}
</script>

@endsection
