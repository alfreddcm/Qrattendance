<div class="container">
    <h2>Attendance Statistics</h2>
    <div class="row" style="margin-top:0;">
        <div class="col s12 m6 l4">
            <div class="card z-depth-2" style="padding:20px 16px 16px 16px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.08); background:#fff; min-height:340px; max-width:100%; display:flex; flex-direction:column; align-items:center; margin-bottom:16px;">
                <span style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:block; color:#333;">Daily Attendance Trends</span>
                @if(isset($attendanceTrendsChart))
                    <div style="width:100%; height:260px;">
                        {!! $attendanceTrendsChart->container() !!}
                    </div>
                @else
                    <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center; color:#999;">
                        <div style="text-align:center;">
                            <i class="material-icons" style="font-size:48px;">bar_chart</i>
                            <p>No attendance data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col s12 m6 l4">
            <div class="card z-depth-2" style="padding:20px 16px 16px 16px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.08); background:#fff; min-height:340px; max-width:100%; display:flex; flex-direction:column; align-items:center; margin-bottom:16px;">
                <span style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:block; color:#333;">Time-in & Time-out Patterns</span>
                @if(isset($timePatternsChart))
                    <div style="width:100%; height:260px;">
                        {!! $timePatternsChart->container() !!}
                    </div>
                @else
                    <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center; color:#999;">
                        <div style="text-align:center;">
                            <i class="material-icons" style="font-size:48px;">schedule</i>
                            <p>No time pattern data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col s12 m6 l4">
            <div class="card z-depth-2" style="padding:20px 16px 16px 16px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.08); background:#fff; min-height:340px; max-width:100%; display:flex; flex-direction:column; align-items:center; margin-bottom:16px;">
                <span style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:block; color:#333;">Absenteeism Rates</span>
                @if(isset($absenteeismRatesChart))
                    <div style="width:100%; height:260px;">
                        {!! $absenteeismRatesChart->container() !!}
                    </div>
                @else
                    <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center; color:#999;">
                        <div style="text-align:center;">
                            <i class="material-icons" style="font-size:48px;">trending_down</i>
                            <p>No absenteeism data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col s12 m6 l6">
            <div class="card z-depth-2" style="padding:20px 16px 16px 16px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.08); background:#fff; min-height:340px; max-width:100%; display:flex; flex-direction:column; align-items:center; margin-bottom:16px;">
                <span style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:block; color:#333;">Weekly/Monthly Attendance Trends</span>
                @if(isset($seasonalTrendsChart))
                    <div style="width:100%; height:260px;">
                        {!! $seasonalTrendsChart->container() !!}
                    </div>
                @else
                    <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center; color:#999;">
                        <div style="text-align:center;">
                            <i class="material-icons" style="font-size:48px;">timeline</i>
                            <p>No seasonal trends data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- <div class="col s12 m6 l6">
            <div class="card z-depth-2" style="padding:20px 16px 16px 16px; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,0.08); background:#fff; min-height:340px; max-width:100%; display:flex; flex-direction:column; align-items:center; margin-bottom:16px;">
                <span style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:block; color:#333;">Attendance Forecasting</span>
                @if(isset($attendanceForecastChart))
                    <div style="width:100%; height:260px;">
                        {!! $attendanceForecastChart->container() !!}
                    </div>
                @else
                    <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center; color:#999;">
                        <div style="text-align:center;">
                            <i class="material-icons" style="font-size:48px;">trending_up</i>
                            <p>No forecast data available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div> -->
       
    </div>
</div>

@if(isset($attendanceTrendsChart))
    {!! $attendanceTrendsChart->script() !!}
@endif
@if(isset($timePatternsChart))
    {!! $timePatternsChart->script() !!}
@endif
@if(isset($absenteeismRatesChart))
    {!! $absenteeismRatesChart->script() !!}
@endif
@if(isset($seasonalTrendsChart))
    {!! $seasonalTrendsChart->script() !!}
@endif
@if(isset($attendanceForecastChart))
    {!! $attendanceForecastChart->script() !!}
@endif
@if(isset($studentForecastChart))
    {!! $studentForecastChart->script() !!}
@endif
@if(isset($subjectAttendanceChart))
    {!! $subjectAttendanceChart->script() !!}
@endif

<script>
// Add error handling for charts
document.addEventListener('DOMContentLoaded', function() {
    // Check if any chart containers are empty and show fallback message
    const chartContainers = document.querySelectorAll('[id^="chart-"]');
    chartContainers.forEach(function(container) {
        if (!container.querySelector('canvas')) {
            const fallback = container.nextElementSibling;
            if (fallback && fallback.classList.contains('chart-fallback')) {
                fallback.style.display = 'flex';
            }
        }
    });
});
</script>

