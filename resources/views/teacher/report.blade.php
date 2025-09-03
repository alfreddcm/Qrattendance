@extends('teacher/sidebar')
@section('title', 'Attendance Reports')
@section('content')

<div class="container-fluid">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fs-5 mb-1">
                    <span class="me-2">📊</span>
                    Attendance Reports
                </h4>
                <p class="subtitle fs-6 mb-0">Generate and export attendance reports</p>
            </div>
           
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0 fs-6">
                        <span class="me-1">🔍</span>
                        Report Filters
                    </h6>
                </div>
                <div class="card-body p-2">
                    <form id="filterForm" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label fw-bold fs-6">
                                <i class="fas fa-filter me-1"></i>Report Type
                            </label>
                            <select name="type" id="type" class="form-select form-select-sm">
                                <option value="daily" {{ request('type', 'daily') == 'daily' ? 'selected' : '' }}>
                                    📅 Daily Report
                                </option>
                                <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>
                                    📊 Monthly Summary
                                </option>
                                <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>
                                    📈 Quarterly Tracking
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="grade_section" class="form-label fw-bold fs-6">
                                <i class="fas fa-users me-1"></i>Grade & Section
                            </label>
                            <select name="grade_section" id="grade_section" class="form-select form-select-sm">
                                <option value="">All Students</option>
                                @if(isset($gradeSectionOptions))
                                    @foreach($gradeSectionOptions as $option)
                                        @php
                                            $parts = explode('|', $option);
                                            $displayText = count($parts) == 2 ? "Grade {$parts[0]} - {$parts[1]}" : $option;
                                        @endphp
                                        <option value="{{ $option }}" {{ request('grade_section') == $option ? 'selected' : '' }}>
                                            {{ $displayText }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <div id="dateField" style="display:none;">
                                <label for="date" class="form-label fw-bold fs-6">
                                    <i class="fas fa-calendar me-1"></i>Select Date
                                </label>
                                <input type="date" name="date" id="date" value="{{ request('date', now()->toDateString()) }}" class="form-control">
                            </div>
                            <div id="monthField" style="display:none;">
                                <label for="month" class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-1"></i>Select Month
                                </label>
                                <input type="month" name="month" id="month" value="{{ request('month', now()->format('Y-m')) }}" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="semester_id" class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-1"></i>Semester
                            </label>
                            <select name="semester_id" id="semester_id" class="form-select">
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                        {{ $semester->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

        @if(isset($records) && count($records))
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>Export Options
                        </h5>
                        <form method="POST" action="{{ route('teacher.attendance.export.csv') }}">
                            @csrf
                            <input type="hidden" name="type" id="export_type" value="{{ request('type', 'daily') }}">
                            <input type="hidden" name="semester_id" id="export_semester_id" value="{{ request('semester_id') }}">
                            <input type="hidden" name="grade_section" id="export_grade_section" value="{{ request('grade_section') }}">
                            <input type="hidden" name="date" id="export_date" value="{{ request('date') }}">
                            <input type="hidden" name="month" id="export_month" value="{{ request('month') }}">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-csv me-2"></i>Export to CSV
                            </button>
                            <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#sf2Modal">
                                <i class="fas fa-file-excel me-2"></i>Generate SF2
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row mt-4">
        <div class="col-12">
            <div id="previewArea">
                @include('teacher.report_preview', ['records' => $records])
            </div>
        </div>
    </div>
    

</div>

<!-- SF2 Generation Modal -->
<div class="modal fade" id="sf2Modal" tabindex="-1" aria-labelledby="sf2ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sf2ModalLabel">
                    <i class="fas fa-file-excel me-2"></i>Generate SF2 Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sf2Form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="sf2_semester" class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-1"></i>Semester
                            </label>
                            <select name="semester_id" id="sf2_semester" class="form-select" required>
                                <option value="">Select Semester</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="sf2_grade_section" class="form-label fw-bold">
                                <i class="fas fa-layer-group me-1"></i>Grade & Section
                            </label>
                            <select name="grade_section" id="sf2_grade_section" class="form-select">
                                <option value="">All Students</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="sf2_month_year" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Month & Year
                            </label>
                            <select name="month_year" id="sf2_month_year" class="form-select" required>
                                <option value="">Select Semester First</option>
                            </select>
                            <input type="hidden" name="month" id="sf2_month_hidden">
                            <input type="hidden" name="year" id="sf2_year_hidden">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="generateSF2Btn">
                    <i class="fas fa-file-excel me-1"></i>Generate SF2
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SF2 Result Modal -->
<div class="modal fade" id="sf2ResultModal" tabindex="-1" aria-labelledby="sf2ResultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sf2ResultModalLabel">
                    <i class="fas fa-check-circle me-2 text-success"></i>SF2 Generated Successfully
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="sf2ResultMessage">SF2 file has been generated successfully!</span>
                </div>
                
                <!-- Warning display area -->
                <div id="sf2WarningsContainer" style="display: none;">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Data Availability Notice:</strong>
                        <ul id="sf2WarningsList" class="mb-0 mt-2">
                        </ul>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="#" id="downloadExcelBtn" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Download Excel File
                    </a>
                   
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateFilterFields() {
    var type = $('#type').val();
    $('#dateField').hide();
    $('#monthField').hide();
    if(type === 'daily') {
        $('#dateField').show();
    } else if(type === 'monthly') {
        $('#monthField').show();
    }
}

// SF2 Functions
let sf2Data = {};

// Enhanced error handling function
function showError(message) {
    // Create a more user-friendly error display
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove any existing error alerts
    $('.modal-body .alert-danger').remove();
    
    // Add the error alert to the modal body
    $('#sf2Modal .modal-body').prepend(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        $('.alert-danger').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
}

function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('.modal-body .alert-success').remove();
    $('#sf2Modal .modal-body').prepend(alertHtml);
    
    setTimeout(() => {
        $('.alert-success').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
} // Store all SF2 data

function loadSF2Options() {
    $.get('{{ route("teacher.sf2.options") }}')
        .done(function(response) {
            // Store the full response for later use
            sf2Data = response;
            
            // Populate Semesters
            const $semester = $('#sf2_semester');
            $semester.empty().append('<option value="">Select Semester</option>');
            response.semesters.forEach(function(semester) {
                $semester.append(`<option value="${semester.id}" 
                    data-start-month="${semester.start_month}"
                    data-start-year="${semester.start_year}"
                    data-end-month="${semester.end_month}"
                    data-end-year="${semester.end_year}">${semester.name}</option>`);
            });

            // Populate Grade & Section Options
            const $gradeSection = $('#sf2_grade_section');
            $gradeSection.empty().append('<option value="">All Students</option>');
            response.grade_section_options.forEach(function(option) {
                $gradeSection.append(`<option value="${option.value}">${option.label}</option>`);
            });

            // Initialize month-year as empty (will be populated when semester is selected)
            const $monthYear = $('#sf2_month_year');
            $monthYear.empty().append('<option value="">Select Semester First</option>');
        })
        .fail(function(xhr) {
            console.error('SF2 Options Error:', xhr);
            const errorMessage = xhr.responseJSON?.message || 'Error loading SF2 options. Please try again.';
            showError(errorMessage);
        });
}

function updateMonthOptions() {
    const $semester = $('#sf2_semester');
    const $monthYear = $('#sf2_month_year');
    const selectedSemester = $semester.find('option:selected');
    
    if (!$semester.val()) {
        $monthYear.empty().append('<option value="">Select Semester First</option>');
        return;
    }
    
    const startMonth = parseInt(selectedSemester.data('start-month'));
    const startYear = parseInt(selectedSemester.data('start-year'));
    const endMonth = parseInt(selectedSemester.data('end-month'));
    const endYear = parseInt(selectedSemester.data('end-year'));
    
    // Validate semester data
    if (!startMonth || !startYear || !endMonth || !endYear) {
        showError('Invalid semester data. Please contact administrator.');
        $monthYear.empty().append('<option value="">Semester Data Error</option>');
        return;
    }
    
    $monthYear.empty().append('<option value="">Select Month & Year</option>');
    
    // Generate months within semester range
    const months = sf2Data.months;
    if (!months) {
        showError('Month data not loaded. Please try again.');
        return;
    }
    
    let currentYear = startYear;
    let currentMonth = startMonth;
    let optionCount = 0;
    
    while (currentYear < endYear || (currentYear === endYear && currentMonth <= endMonth)) {
        const monthName = months[currentMonth];
        if (monthName) {
            const value = `${currentMonth}-${currentYear}`;
            $monthYear.append(`<option value="${value}">${monthName} ${currentYear}</option>`);
            optionCount++;
        }
        
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        
        // Safety break to prevent infinite loop
        if (optionCount > 24) {
            console.warn('Too many months generated, breaking loop');
            break;
        }
    }
    
    if (optionCount === 0) {
        showError('No valid months found for this semester.');
    }
}

function generateSF2() {
    const monthYear = $('#sf2_month_year').val();
    const semesterId = $('#sf2_semester').val();
    
    // Enhanced validation with specific error messages
    if (!semesterId) {
        showError('Please select a semester first.');
        return;
    }
    
    if (!monthYear) {
        showError('Please select Month & Year.');
        return;
    }
    
    // Validate that the selected option exists in the dropdown
    const selectedOption = $(`#sf2_month_year option[value="${monthYear}"]`);
    if (selectedOption.length === 0) {
        showError('Invalid month-year selection. Please choose from available options.');
        return;
    }
    
    // Split month-year value
    const [month, year] = monthYear.split('-');
    
    // Additional validation for split values
    if (!month || !year || isNaN(month) || isNaN(year)) {
        showError('Invalid month-year format. Please try again.');
        return;
    }
    
    // Validate month range (1-12)
    const monthNum = parseInt(month);
    const yearNum = parseInt(year);
    if (monthNum < 1 || monthNum > 12) {
        showError('Invalid month value. Month must be between 1 and 12.');
        return;
    }
    
    // Validate year range (reasonable bounds)
    if (yearNum < 2020 || yearNum > 2030) {
        showError('Invalid year value. Please select a valid academic year.');
        return;
    }
    
    // Update hidden fields for backend compatibility
    $('#sf2_month_hidden').val(month);
    $('#sf2_year_hidden').val(year);
    
    const formData = {
        semester_id: semesterId,
        grade_section: $('#sf2_grade_section').val(),
        month: month,
        year: year,
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Additional validation: Check if month is within semester range
    const selectedSemester = $('#sf2_semester').find('option:selected');
    const startMonth = parseInt(selectedSemester.data('start-month'));
    const startYear = parseInt(selectedSemester.data('start-year'));
    const endMonth = parseInt(selectedSemester.data('end-month'));
    const endYear = parseInt(selectedSemester.data('end-year'));
    const selectedMonth = parseInt(formData.month);
    const selectedYear = parseInt(formData.year);

    // Check if selected month/year is within semester range
    const isValidDate = (selectedYear > startYear || (selectedYear === startYear && selectedMonth >= startMonth)) &&
                       (selectedYear < endYear || (selectedYear === endYear && selectedMonth <= endMonth));

    if (!isValidDate) {
        showError('Selected month and year must be within the semester period.');
        return;
    }

    // Show loading state
    const $btn = $('#generateSF2Btn');
    const originalText = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...').prop('disabled', true);

    $.post('{{ route("teacher.sf2.generate") }}', formData)
        .done(function(response) {
            if (response.success) {
                // Hide SF2 modal
                $('#sf2Modal').modal('hide');
                
                // Show result modal
                $('#sf2ResultMessage').text(`SF2 generated successfully! ${response.student_count} students included.`);
                $('#downloadExcelBtn').attr('href', response.download_url);
                $('#downloadExcelBtn').data('filename', response.filename);
                
                // Handle warnings if present
                if (response.warnings && response.warnings.length > 0) {
                    $('#sf2WarningsContainer').show();
                    $('#sf2WarningsList').empty();
                    response.warnings.forEach(function(warning) {
                        $('#sf2WarningsList').append('<li>' + warning + '</li>');
                    });
                } else {
                    $('#sf2WarningsContainer').hide();
                }
                
                $('#sf2ResultModal').modal('show');
                
                // Clear any error messages
                $('.modal-body .alert-danger').remove();
            } else {
                showError(response.message || 'Error generating SF2 file');
            }
        })
        .fail(function(xhr) {
            console.error('SF2 Generation Error:', xhr);
            const response = xhr.responseJSON;
            const errorMessage = response?.message || 'Unknown error occurred while generating SF2';
            showError(errorMessage);
        })
        .always(function() {
             $btn.html(originalText).prop('disabled', false);
        });
}
 

$(function() {
    updateFilterFields();

    $('#type').on('change', function() {
        updateFilterFields();
    });

    $('#filterForm').on('change', 'select, input[type=date], input[type=month]', function() {
        // Update export form values
        $('#export_type').val($('#type').val());
        $('#export_semester_id').val($('#semester_id').val());
        $('#export_grade_section').val($('#grade_section').val());
        $('#export_date').val($('#date').val());
        $('#export_month').val($('#month').val());
        
        $('#filterForm').submit();
    });

    // Initial update of export form values
    $('#export_type').val($('#type').val());
    $('#export_semester_id').val($('#semester_id').val());
    $('#export_grade_section').val($('#grade_section').val());
    $('#export_date').val($('#date').val());
    $('#export_month').val($('#month').val());

    // SF2 Event Handlers
    $('#sf2Modal').on('show.bs.modal', function() {
        loadSF2Options();
    });

    // Semester change handler to update months
    $('#sf2_semester').on('change', function() {
        updateMonthOptions();
    });

    // Month-year change handler to update hidden fields
    $('#sf2_month_year').on('change', function() {
        const monthYear = $(this).val();
        if (monthYear) {
            const [month, year] = monthYear.split('-');
            $('#sf2_month_hidden').val(month);
            $('#sf2_year_hidden').val(year);
        } else {
            $('#sf2_month_hidden').val('');
            $('#sf2_year_hidden').val('');
        }
    });

    // Prevent form submission
    $('#sf2Form').on('submit', function(e) {
        e.preventDefault();
        generateSF2();
    });

    $('#generateSF2Btn').on('click', function(e) {
        e.preventDefault();
        generateSF2();
    });

    $('#generatePdfBtn').on('click', function() {
        generatePDF();
    });
});
</script>
@endsection