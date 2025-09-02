 
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-primary">📊 Attendance Statistics Dashboard</h4>
         </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 id="totalStudents" class="text-primary mb-1">--</h5>
                    <small class="text-muted">Total Students</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 id="attendanceRate" class="text-success mb-1">--%</h5>
                    <small class="text-muted">Avg Attendance Rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 id="presentRecords" class="text-info mb-1">--</h5>
                    <small class="text-muted">Present Records</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h5 id="totalRecords" class="text-warning mb-1">--</h5>
                    <small class="text-muted">Total Records</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <!-- Attendance Trend Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📈 Attendance Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Absenteeism Rates Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📊 High Absenteeism Rates (>70%)</h6>
                </div>
                <div class="card-body">
                    <canvas id="absenteeismChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Weekly Attendance Trend -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📅 Weekly Attendance Pattern</h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Time Patterns Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📈 Attendance Time Patterns</h6>
                </div>
                <div class="card-body">
                    <canvas id="timePatternsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">📆 Monthly Attendance Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading chart data...</p>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart instances
let attendanceTrendChart, absenteeismChart, weeklyTrendChart, timePatternsChart, monthlyTrendChart;

 document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadAllData();
});

 function initializeCharts() {
     const ctx1 = document.getElementById('attendanceTrendChart').getContext('2d');
    attendanceTrendChart = new Chart(ctx1, {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

     const ctx2 = document.getElementById('absenteeismChart').getContext('2d');
    absenteeismChart = new Chart(ctx2, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });

     const ctx3 = document.getElementById('weeklyTrendChart').getContext('2d');
    weeklyTrendChart = new Chart(ctx3, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });

     const ctx4 = document.getElementById('timePatternsChart').getContext('2d');
    timePatternsChart = new Chart(ctx4, {
        type: 'line',
        data: { 
            labels: [],
            datasets: [] 
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    display: true
                },
                title: {
                    display: true,
                    text: 'Daily Average Attendance Times (AM on top, PM below)'
                }
            },
            scales: {
                x: { 
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    min: 6,
                    max: 18,
                    title: {
                        display: true,
                        text: 'Time (Hours)'
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            if (value <= 12) {
                                return value + ' AM';
                            } else {
                                return (value - 12) + ' PM';
                            }
                        }
                    }
                }
            }
        }
    });

     const ctx5 = document.getElementById('monthlyTrendChart').getContext('2d');
    monthlyTrendChart = new Chart(ctx5, {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Load all chart data
async function loadAllData() {
    showLoading(true);
    
    try {
         loadSummaryStats();
        
         await Promise.all([
            loadAttendanceTrend(),
            loadAbsenteeismRates(),
            loadWeeklyTrend(),
            loadTimePatterns(),
            loadMonthlyTrend()
        ]);
        
    } catch (error) {
        console.error('Error loading data:', error);
        alert('Error loading chart data. Please try again.');
    } finally {
        showLoading(false);
    }
}

 async function loadSummaryStats() {
    try {
        console.log('Loading summary stats...');
        const response = await fetch('/teacher/analytics/summary-stats');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Summary stats data:', data);
        
        document.getElementById('totalStudents').textContent = data.total_students || 0;
        document.getElementById('attendanceRate').textContent = (data.average_attendance_rate || 0) + '%';
        document.getElementById('presentRecords').textContent = data.total_present_records || 0;
        document.getElementById('totalRecords').textContent = data.total_attendance_records || 0;
        
        console.log('Summary stats updated successfully');
    } catch (error) {
        console.error('Error loading summary stats:', error);
        // Set default values on error
        document.getElementById('totalStudents').textContent = '0';
        document.getElementById('attendanceRate').textContent = '0%';
        document.getElementById('presentRecords').textContent = '0';
        document.getElementById('totalRecords').textContent = '0';
    }
}

 async function loadAttendanceTrend() {
    const response = await fetch('/teacher/analytics/attendance-trend');
    const data = await response.json();
    
    attendanceTrendChart.data = data;
    attendanceTrendChart.update();
}

// Load absenteeism rates chart
async function loadAbsenteeismRates() {
    const response = await fetch('/teacher/analytics/absenteeism-rates');
    const data = await response.json();
    
    absenteeismChart.data = data;
    absenteeismChart.update();
}

// Load weekly trend chart
async function loadWeeklyTrend() {
    const response = await fetch('/teacher/analytics/weekly-trend');
    const data = await response.json();
    
    weeklyTrendChart.data = data;
    weeklyTrendChart.update();
}

 async function loadTimePatterns() {
    const response = await fetch('/teacher/analytics/time-patterns');
    const data = await response.json();
    
    timePatternsChart.data = data;
    timePatternsChart.update();
}

 async function loadMonthlyTrend() {
    const response = await fetch('/teacher/analytics/monthly-trend');
    const data = await response.json();
    
    monthlyTrendChart.data = data;
    monthlyTrendChart.update();
}

 function showLoading(show) {
    document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
}
</script>
 