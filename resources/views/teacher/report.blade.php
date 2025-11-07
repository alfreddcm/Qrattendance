@extends('teacher/sidebar')
@section('title', 'Attendance Reports')
@section('content')

<div class="container-fluid">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fs-5 mb-1">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reports
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
                        <i class="fas fa-search me-1"></i>
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
                                    <i class="fas fa-calendar-day me-1"></i>Daily Report
                                </option>
                                <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>
                                    <i class="fas fa-chart-line me-1"></i>Monthly Summary
                                </option>
                                <option value="quarterly" {{ request('type') == 'quarterly' ? 'selected' : '' }}>
                                    📈 Quarterly 
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="grade_section" class="form-label fw-bold fs-6">
                                <i class="fas fa-users me-1"></i>Grade & Section
                            </label>
                            <select name="grade_section" id="grade_section" class="form-select form-select-sm">
                                @if(isset($gradeSectionOptions))
                                    @foreach($gradeSectionOptions as $option)
                                        @php
                                            $parts = explode('|', $option);
                                            $displayText = count($parts) == 2 ? "Grade {$parts[0]} - {$parts[1]}" : $option;
                                            $isSelected = (isset($gradeSection) && $gradeSection == $option) || (request('grade_section') == $option);
                                        @endphp
                                        <option value="{{ $option }}" {{ $isSelected ? 'selected' : '' }}>
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
                            <label for="school_year_id" class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-1"></i>School Year
                            </label>
                            <select name="school_year_id" id="school_year_id" class="form-select">
                                @foreach($schoolYears as $schoolYear)
                                    <option value="{{ $schoolYear->id }}" {{ request('school_year_id') == $schoolYear->id ? 'selected' : '' }}>
                                        {{ $schoolYear->name }}
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
                            <input type="hidden" name="school_year_id" id="export_school_year_id" value="{{ request('school_year_id') }}">
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
                            <label for="sf2_school_year" class="form-label fw-bold">
                                <i class="fas fa-graduation-cap me-1"></i>School Year
                            </label>
                            <select name="school_year_id" id="sf2_school_year" class="form-select" required>
                                <option value="">Select School Year</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="sf2_grade_section" class="form-label fw-bold">
                                <i class="fas fa-layer-group me-1"></i>Grade & Section
                            </label>
                            <select name="grade_section" id="sf2_grade_section" class="form-select">
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="sf2_month_year" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Month & Year
                            </label>
                            <select name="month_year" id="sf2_month_year" class="form-select" required>
                                <option value="">Select School Year First</option>
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

let sf2Data = {};

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

     $('.modal-body .alert-danger').remove();

     $('#sf2Modal .modal-body').prepend(alertHtml);

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
}  

function loadSF2Options() {
    $.get('{{ route("teacher.sf2.options") }}')
        .done(function(response) {
             sf2Data = response;

             const $schoolYear = $('#sf2_school_year');
            $schoolYear.empty().append('<option value="">Select School Year</option>');
            response.schoolYears.forEach(function(schoolYear) {
                $schoolYear.append(`<option value="${schoolYear.id}"
                    data-start-month="${schoolYear.start_month}"
                    data-start-year="${schoolYear.start_year}"
                    data-end-month="${schoolYear.end_month}"
                    data-end-year="${schoolYear.end_year}">${schoolYear.name}</option>`);
            });

             const $gradeSection = $('#sf2_grade_section');
            $gradeSection.empty().append('<option value="">All Students</option>');
            response.grade_section_options.forEach(function(option) {
                $gradeSection.append(`<option value="${option.value}">${option.label}</option>`);
            });

             const $monthYear = $('#sf2_month_year');
            $monthYear.empty().append('<option value="">Select School Year First</option>');
        })
        .fail(function(xhr) {
            console.error('SF2 Options Error:', xhr);
            const errorMessage = xhr.responseJSON?.message || 'Error loading SF2 options. Please try again.';
            showError(errorMessage);
        });
}

function updateMonthOptions() {
    const $schoolYear = $('#sf2_school_year');
    const $monthYear = $('#sf2_month_year');
    const selectedSchoolYear = $schoolYear.find('option:selected');

    if (!$schoolYear.val()) {
        $monthYear.empty().append('<option value="">Select School Year First</option>');
        return;
    }

    const startMonth = parseInt(selectedSchoolYear.data('start-month'));
    const startYear = parseInt(selectedSchoolYear.data('start-year'));
    const endMonth = parseInt(selectedSchoolYear.data('end-month'));
    const endYear = parseInt(selectedSchoolYear.data('end-year'));

      if (!startMonth || !startYear || !endMonth || !endYear) {
        showError('Invalid School Year data. Please contact administrator.');
        $monthYear.empty().append('<option value="">School Year Data Error</option>');
        return;
    }

    $monthYear.empty().append('<option value="">Select Month & Year</option>');

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

         if (optionCount > 24) {
            console.warn('Too many months generated, breaking loop');
            break;
        }
    }

    if (optionCount === 0) {
        showError('No valid months found for this School Year.');
    }
}

function generateSF2() {
    const monthYear = $('#sf2_month_year').val();
    const schoolYearId = $('#sf2_school_year').val();

     if (!schoolYearId) {
        showError('Please select a School Year first.');
        return;
    }

    if (!monthYear) {
        showError('Please select Month & Year.');
        return;
    }

     const selectedOption = $(`#sf2_month_year option[value="${monthYear}"]`);
    if (selectedOption.length === 0) {
        showError('Invalid month-year selection. Please choose from available options.');
        return;
    }

     const [month, year] = monthYear.split('-');

     if (!month || !year || isNaN(month) || isNaN(year)) {
        showError('Invalid month-year format. Please try again.');
        return;
    }

     const monthNum = parseInt(month);
    const yearNum = parseInt(year);
    if (monthNum < 1 || monthNum > 12) {
        showError('Invalid month value. Month must be between 1 and 12.');
        return;
    }

     if (yearNum < 2020 || yearNum > 2030) {
        showError('Invalid year value. Please select a valid academic year.');
        return;
    }

     $('#sf2_month_hidden').val(month);
    $('#sf2_year_hidden').val(year);

    const formData = {
        school_year_id: schoolYearId,
        grade_section: $('#sf2_grade_section').val(),
        month: month,
        year: year,
        _token: $('meta[name="csrf-token"]').attr('content')
    };

     const selectedSchoolYear = $('#sf2_school_year').find('option:selected');
    const startMonth = parseInt(selectedSchoolYear.data('start-month'));
    const startYear = parseInt(selectedSchoolYear.data('start-year'));
    const endMonth = parseInt(selectedSchoolYear.data('end-month'));
    const endYear = parseInt(selectedSchoolYear.data('end-year'));
    const selectedMonth = parseInt(formData.month);
    const selectedYear = parseInt(formData.year);

     const isValidDate = (selectedYear > startYear || (selectedYear === startYear && selectedMonth >= startMonth)) &&
                       (selectedYear < endYear || (selectedYear === endYear && selectedMonth <= endMonth));

    if (!isValidDate) {
        showError('Selected month and year must be within the School Year period.');
        return;
    }

     const $btn = $('#generateSF2Btn');
    const originalText = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...').prop('disabled', true);

    $.post('{{ route("teacher.sf2.generate") }}', formData)
        .done(function(response) {
            if (response.success) {
                 $('#sf2Modal').modal('hide');

                 $('#sf2ResultMessage').text(`SF2 generated successfully! ${response.student_count} students included.`);
                $('#downloadExcelBtn').attr('href', response.download_url);
                $('#downloadExcelBtn').data('filename', response.filename);

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
         $('#export_type').val($('#type').val());
        $('#export_school_year_id').val($('#school_year_id').val());
        $('#export_grade_section').val($('#grade_section').val());
        $('#export_date').val($('#date').val());
        $('#export_month').val($('#month').val());

        $('#filterForm').submit();
    });

     $('#export_type').val($('#type').val());
    $('#export_school_year_id').val($('#school_year_id').val());
    $('#export_grade_section').val($('#grade_section').val());
    $('#export_date').val($('#date').val());
    $('#export_month').val($('#month').val());

     $('#sf2Modal').on('show.bs.modal', function() {
        loadSF2Options();
    });

     $('#sf2_school_year').on('change', function() {
        updateMonthOptions();
    });

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
