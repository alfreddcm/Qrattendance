@if(isset($records) && count($records))
@if(request('type', 'daily') == 'daily')
<!-- Daily Report -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-calendar-day me-2"></i>
            Daily Attendance Report - {{ \Carbon\Carbon::parse(request('date', now()->toDateString()))->format('F j, Y') }}
        </h5>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x:auto; max-height:600px">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th scope="col" class="text-center" style="min-width: 50px;">#</th>
                        <th scope="col" style="min-width: 200px;">Student Name</th>
                        <th scope="col" class="text-center" style="min-width: 100px;">Status</th>
                        <th scope="col" class="text-center" style="min-width: 120px;">AM In</th>
                        <th scope="col" class="text-center" style="min-width: 120px;">AM Out</th>
                        <th scope="col" class="text-center" style="min-width: 120px;">PM In</th>
                        <th scope="col" class="text-center" style="min-width: 120px;">PM Out</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $i => $row)
                    <tr>
                        <td class="text-center fw-bold">{{ $i+1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    <small class="text-muted">{{ $row->id_no }}</small>
                                    @if(isset($row->grade_level) && isset($row->section))
                                        <br><small class="badge bg-info text-white">Grade {{ $row->grade_level }} - {{ $row->section }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($row->status == 'Present')
                                <span class="badge bg-success">Present</span>
                            @elseif($row->status == 'Partial')
                                <span class="badge bg-warning">Partial</span>
                            @else
                                <span class="badge bg-danger">Absent</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row->am_in)
                                <span class="badge bg-success">{{ $row->am_in }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row->am_out)
                                <span class="badge bg-info">{{ $row->am_out }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row->pm_in)
                                <span class="badge bg-success">{{ $row->pm_in }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($row->pm_out)
                                <span class="badge bg-info">{{ $row->pm_out }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

 
<div class="card shadow-sm mt-4">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Daily Summary</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row g-2">
                    @php
                        $present = $records->where('status', 'Present')->count();
                        $partial = $records->where('status', 'Partial')->count();  
                        $absent = $records->where('status', 'Absent')->count();
                        $total = $records->count();
                    @endphp
                    <div class="col-6">
                        <div class="card bg-success text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $present }}</h4>
                                <small>Present</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-warning text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $partial }}</h4>
                                <small>Partial</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-danger text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $absent }}</h4>
                                <small>Absent</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-primary text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $total }}</h4>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@elseif(request('type') == 'monthly')
 
<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt me-2"></i>
            Monthly Attendance Summary - {{ \Carbon\Carbon::parse(request('month', now()->format('Y-m')))->format('F Y') }}
        </h5>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x:auto; max-height:600px">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th scope="col" class="text-center" style="min-width: 50px;">#</th>
                        <th scope="col" style="min-width: 200px;">Student Name</th>
                        <th scope="col" class="text-center" style="min-width: 100px;">Total Days</th>
                        <th scope="col" class="text-center" style="min-width: 100px;">Present</th>
                        <th scope="col" class="text-center" style="min-width: 100px;">Absent</th>
                        <th scope="col" class="text-center" style="min-width: 100px;">Partial</th>
                        <th scope="col" class="text-center" style="min-width: 120px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $i => $row)
                    <tr>
                        <td class="text-center fw-bold">{{ $i+1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    <small class="text-muted">{{ $row->id_no }}</small>
                                    @if(isset($row->grade_level) && isset($row->section))
                                        <br><small class="badge bg-info text-white">Grade {{ $row->grade_level }} - {{ $row->section }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $row->total_day }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success">{{ $row->present }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger">{{ $row->absent }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning">{{ $row->partial }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $row->remarks }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monthly Summary Chart -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-success text-white">
        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Summary</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row g-2">
                    @php
                        $totalPresent = $records->sum('present');
                        $totalAbsent = $records->sum('absent');
                        $totalPartial = $records->sum('late');
                        $totalPossible = $records->sum('total_day');
                    @endphp
                    <div class="col-6">
                        <div class="card bg-success text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalPresent }}</h4>
                                <small>Total Present</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-warning text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalPartial }}</h4>
                                <small>Total Partial</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-danger text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalAbsent }}</h4>
                                <small>Total Absent</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-primary text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalPossible }}</h4>
                                <small>Total Possible</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@elseif(request('type') == 'quarterly')
<!-- Quarterly Report -->
<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-calendar-check me-2"></i>
            Quarterly Attendance Tracking
        </h5>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x:auto; max-height:500px; max-width: 100%">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th scope="col" class="sticky-col text-center" style="min-width: 50px;">#</th>
                        <th scope="col" class="sticky-col" style="min-width: 200px;">Student Name</th>
                        @if(count($records) > 0 && isset($records->first()->checks))
                            @foreach ($records->first()->checks as $date => $val)
                            <th scope="col" class="text-center" style="min-width: 70px;">
                                {{ \Carbon\Carbon::parse($date)->format('m/d') }}
                            </th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $i => $record)
                    <tr>
                        <td class="sticky-col text-center fw-bold">{{ $i+1 }}</td>
                        <td class="sticky-col">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $record->name }}</div>
                                    <small class="text-muted">{{ $record->id_no }}</small>
                                    @if(isset($record->grade_level) && isset($record->section))
                                        <br><small class="badge bg-info text-white">Grade {{ $record->grade_level }} - {{ $record->section }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(isset($record->checks))
                            @foreach ($record->checks as $check)
                            <td class="text-center">
                                @if($check == '✓')
                                    <span class="badge bg-success">Present</span>
                                @elseif($check == '◐')
                                    <span class="badge bg-warning">Partial</span>
                                @else
                                    <span class="badge bg-danger">Absent</span>
                                @endif
                            </td>
                            @endforeach
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quarterly Summary Chart -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Quarterly Summary</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <canvas id="quarterlyChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                @php
                    $totalDays = 0;
                    $totalPresent = 0;
                    $totalPartial = 0;
                    $totalAbsent = 0;
                    
                    if(count($records) > 0) {
                        foreach($records as $record) {
                            if(isset($record->checks)) {
                                foreach($record->checks as $check) {
                                    $totalDays++;
                                    if($check == '✓') $totalPresent++;
                                    elseif($check == '◐') $totalPartial++;
                                    else $totalAbsent++;
                                }
                            }
                        }
                    }
                @endphp
                <div class="row g-2">
                    <div class="col-6">
                        <div class="card bg-success text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalPresent }}</h4>
                                <small>Full Present</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-warning text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalPartial }}</h4>
                                <small>Half Day</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-danger text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalAbsent }}</h4>
                                <small>Absent</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-primary text-white summary-card">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">{{ $totalDays }}</h4>
                                <small>Total Days</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endif

@else
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No Data Available</h4>
        <p class="text-muted">Please select filters to generate the report.</p>
    </div>
</div>
@endif

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<style>
/* Fix sticky header overlap issue */
.sticky-top {
    top: 0px !important; /* Offset for main sticky header */
    z-index: 1020 !important; /* Below hamburger button */
}

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 10;
    background-color: #fff;
    border-right: 2px solid #dee2e6;
}

.table-dark .sticky-col {
    background-color: #212529;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
}

/* Consistent table styling */
.table th {
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    font-size: 14px;
}

.card-header h5 {
    font-weight: 600;
    font-size: 16px;
}

.badge {
    font-size: 12px;
    font-weight: 500;
    padding: 6px 12px;
}


@media (max-width: 768px) {
    .table th, .table td {
        font-size: 12px;
        padding: 8px;
    }
    
    .avatar-sm {
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
    
    .badge {
        font-size: 10px;
        padding: 4px 8px;
    }
}

/* Enhance chart container */
.chart-container {
    position: relative;
    height: 300px;
    margin: 20px 0;
}

/* Summary cards styling */
.summary-card {
    transition: transform 0.2s ease-in-out;
}

.summary-card:hover {
    transform: translateY(-2px);
}
</style>

<script>
function isWeekend(dateString) {
    const date = new Date(dateString);
    const day = date.getDay();
    return day === 0 || day === 6;
}

// Chart initialization
document.addEventListener('DOMContentLoaded', function() {
    const type = '{{ request("type", "daily") }}';
    
    if (type === 'daily') {
        initializeDailyChart();
    } else if (type === 'monthly') {
        initializeMonthlyChart();
    } else if (type === 'quarterly') {
        initializeQuarterlyChart();
    }
});

function initializeDailyChart() {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    
    @php
        $present = $records->where('status', 'Present')->count();
        $partial = $records->where('status', 'Partial')->count();  
        $absent = $records->where('status', 'Absent')->count();
    @endphp   
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Partial', 'Absent'],
            datasets: [{
                data: [{{ $present }}, {{ $partial }}, {{ $absent }}],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold'
                    },
                    formatter: (value, context) => {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + '%';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function initializeMonthlyChart() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    
    const students = @json($records->pluck('name'));
    const present = @json($records->pluck('present'));
    const absent = @json($records->pluck('absent'));
    const partial = @json($records->pluck('late'));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: students,
            datasets: [{
                label: 'Present',
                data: present,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                borderWidth: 1
            }, {
                label: 'Partial',
                data: partial,
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                borderWidth: 1
            }, {
                label: 'Absent',
                data: absent,
                backgroundColor: '#dc3545',
                borderColor: '#dc3545',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            }
        }
    });
}

function initializeQuarterlyChart() {
    const ctx = document.getElementById('quarterlyChart');
    if (!ctx) return;
    
    @php
        $totalDays = 0;
        $totalPresent = 0;
        $totalPartial = 0;
        $totalAbsent = 0;
        
        if(count($records) > 0) {
            foreach($records as $record) {
                if(isset($record->checks)) {
                    foreach($record->checks as $check) {
                        $totalDays++;
                        if($check == '✓') $totalPresent++;
                        elseif($check == '◐') $totalPartial++;
                        else $totalAbsent++;
                    }
                }
            }
        }
    @endphp
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Full Present', 'Partial', 'Absent'],
            datasets: [{
                data: [{{ $totalPresent }}, {{ $totalPartial }}, {{ $totalAbsent }}],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold'
                    },
                    formatter: (value, context) => {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + '%';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

$(function() {
    updateFilterFields();

    // Disable weekends in date picker
    $('#date').on('change', function() {
        if (isWeekend(this.value)) {
            alert('Weekends are not allowed. Please select a weekday.');
            this.value = '';
        }
    });

    $('#type').on('change', function() {
        updateFilterFields();
    });

    $('#filterForm').on('change', 'select, input[type=date], input[type=month]', function() {
        // For daily, prevent submit if weekend
        if ($('#type').val() === 'daily' && isWeekend($('#date').val())) {
            alert('Weekends are not allowed. Please select a weekday.');
            $('#date').val('');
            return false;
        }
        
        // Show loading spinner
        const submitBtn = document.querySelector('#filterForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            submitBtn.disabled = true;
        }
        
        $('#filterForm').submit();
    });
});
</script>