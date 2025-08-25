<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Semester;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceAnalyticsController extends Controller {

     public function dashboardWithStatistics(Request $request)
    {
        $teacherId = Auth::id();
        $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

         $trends = \App\Models\Attendance::selectRaw('
                DATE(date) as attendance_date,
                COUNT(*) as total_students,
                COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present_students,
                COUNT(CASE WHEN time_in_am IS NULL AND time_in_pm IS NULL THEN 1 END) as absent_students
            ')
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();
        $trendLabels = $trends->pluck('attendance_date')->toArray();
        $trendPresent = $trends->pluck('present_students')->toArray();
        $trendAbsent = $trends->pluck('absent_students')->toArray();
        $attendanceTrendsChart = new \App\Charts\AttendanceTrendsChart($trendLabels, $trendPresent, $trendAbsent);

         $patterns = \App\Models\Attendance::selectRaw('
                DATE(date) as attendance_date,
                COUNT(time_in_am) as am_in,
                COUNT(time_out_pm) as pm_out
            ')
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();
        $patternLabels = $patterns->pluck('attendance_date')->toArray();
        $patternIn = $patterns->pluck('am_in')->toArray();
        $patternOut = $patterns->pluck('pm_out')->toArray();
        $timePatternsChart = new \App\Charts\TimePatternsChart($patternLabels, $patternIn, $patternOut);

        // Absenteeism Rates
        $rates = $this->absenteeismRates($request)->getData();
        $absenteeismLabels = collect($rates)->pluck('student_id')->toArray();
        $absenteeismPercentages = collect($rates)->pluck('attendance_percentage')->toArray();
        $absenteeismRatesChart = new \App\Charts\AbsenteeismRatesChart($absenteeismLabels, $absenteeismPercentages);

        // Weekly/Monthly Attendance Trends
        $seasonal = $this->seasonalTrends($request)->getData();
        $weekly = isset($seasonal->weekly) ? $seasonal->weekly : [];
        $monthly = isset($seasonal->monthly) ? $seasonal->monthly : [];
        $seasonalLabels = collect($weekly)->pluck('week')->toArray();
        $weeklyCounts = collect($weekly)->pluck('attendance_count')->toArray();
        $monthlyLabels = collect($monthly)->pluck('month')->toArray();
        $monthlyCounts = collect($monthly)->pluck('attendance_count')->toArray();
        $seasonalTrendsChart = new \App\Charts\SeasonalTrendsChart($seasonalLabels, $weeklyCounts, $monthlyCounts);

        // Students Attendance Forecast (example: use first student)
        $studentId = $request->get('student_id');
        if (!$studentId) {
            $studentId = \App\Models\Student::where('user_id', $teacherId)->value('id');
        }
        $forecastData = $this->studentForecast(new Request(['student_id' => $studentId, 'start_date' => $startDate, 'end_date' => $endDate]))->getData();
        $forecastLabels = collect($forecastData)->pluck('date')->toArray();
        $forecastAttendance = collect($forecastData)->pluck('time_in_am')->toArray();
        $studentForecastChart = new \App\Charts\StudentForecastChart($forecastLabels, $forecastAttendance);

        // Subject Attendance
        $subjectData = $this->subjectAttendance($request)->getData();
        $subjectLabels = collect($subjectData)->pluck('subject_code')->toArray();
        $subjectPresent = collect($subjectData)->pluck('students_present')->toArray();
        $subjectAttendanceChart = new \App\Charts\SubjectAttendanceChart($subjectLabels, $subjectPresent);

         return view('teacher.statistics', compact(
            'attendanceTrendsChart',
            'timePatternsChart',
            'absenteeismRatesChart',
            'seasonalTrendsChart',
            'studentForecastChart',
            'subjectAttendanceChart'
        ));
    }

     public function statistics(Request $request)
    {
    $chartData = $this->getChartData($request);

    // Attendance Forecasting  
    $teacherId = \Auth::id();
    $endDate = $request->get('end_date', \Carbon\Carbon::now()->toDateString());
    $attendance = \App\Models\Attendance::selectRaw('DATE(date) as attendance_date, COUNT(DISTINCT student_id) as present_count')
        ->where('teacher_id', $teacherId)
        ->whereBetween('date', [\Carbon\Carbon::now()->subDays(14), $endDate])
        ->groupBy('attendance_date')
        ->orderBy('attendance_date')
        ->get();
    $labels = $attendance->pluck('attendance_date')->toArray();
    $presentCounts = $attendance->pluck('present_count')->toArray();
    $average = count($presentCounts) ? array_sum($presentCounts) / count($presentCounts) : 0;
    $forecastLabels = [];
    $forecastData = [];
    for ($i = 1; $i <= 7; $i++) {
        $date = \Carbon\Carbon::parse($endDate)->addDays($i)->toDateString();
        $forecastLabels[] = $date;
        $forecastData[] = round($average, 2);
    }
    $attendanceForecastChart = new \App\Charts\AttendanceForecastChart(array_merge($labels, $forecastLabels), array_merge($presentCounts, $forecastData));

    $chartData['attendanceForecastChart'] = $attendanceForecastChart;
    return view('teacher.statistics', $chartData);
}

 
    public function getChartData(Request $request)
    {
        $teacherId = Auth::id();
        $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

         \Log::info('Getting chart data', [
            'teacher_id' => $teacherId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // Daily Attendance Trends
        $trends = \App\Models\Attendance::selectRaw('
                DATE(date) as attendance_date,
                COUNT(*) as total_students,
                COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present_students,
                COUNT(CASE WHEN time_in_am IS NULL AND time_in_pm IS NULL THEN 1 END) as absent_students
            ')
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();
        
        \Log::info('Trends data', ['count' => $trends->count(), 'data' => $trends->toArray()]);
        
        $trendLabels = $trends->pluck('attendance_date')->toArray();
        $trendPresent = $trends->pluck('present_students')->toArray();
        $trendAbsent = $trends->pluck('absent_students')->toArray();
        $attendanceTrendsChart = new \App\Charts\AttendanceTrendsChart($trendLabels, $trendPresent, $trendAbsent);

        // Time-in & Time-out Patterns
        $patterns = \App\Models\Attendance::selectRaw('
                DATE(date) as attendance_date,
                COUNT(time_in_am) as am_in,
                COUNT(time_out_pm) as pm_out
            ')
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();
        
        \Log::info('Patterns data', ['count' => $patterns->count(), 'data' => $patterns->toArray()]);
        
        $patternLabels = $patterns->pluck('attendance_date')->toArray();
        $patternIn = $patterns->pluck('am_in')->toArray();
        $patternOut = $patterns->pluck('pm_out')->toArray();
        $timePatternsChart = new \App\Charts\TimePatternsChart($patternLabels, $patternIn, $patternOut);

        // Absenteeism Rates
        $rates = $this->absenteeismRates($request)->getData();
        $absenteeismLabels = collect($rates)->pluck('student_id')->toArray();
        $absenteeismPercentages = collect($rates)->pluck('attendance_percentage')->toArray();
        $absenteeismRatesChart = new \App\Charts\AbsenteeismRatesChart($absenteeismLabels, $absenteeismPercentages);

        // Weekly/Monthly Attendance Trends
        $seasonal = $this->seasonalTrends($request)->getData();
        $weekly = isset($seasonal->weekly) ? $seasonal->weekly : [];
        $monthly = isset($seasonal->monthly) ? $seasonal->monthly : [];
        $seasonalLabels = collect($weekly)->pluck('week')->toArray();
        $weeklyCounts = collect($weekly)->pluck('attendance_count')->toArray();
        $monthlyLabels = collect($monthly)->pluck('month')->toArray();
        $monthlyCounts = collect($monthly)->pluck('attendance_count')->toArray();
        $seasonalTrendsChart = new \App\Charts\SeasonalTrendsChart($seasonalLabels, $weeklyCounts, $monthlyCounts);

        // Students Attendance Forecast (example: use first student)
        $studentId = $request->get('student_id');
        if (!$studentId) {
            $studentId = \App\Models\Student::where('user_id', $teacherId)->value('id');
        }
        $forecastData = $this->studentForecast(new Request(['student_id' => $studentId, 'start_date' => $startDate, 'end_date' => $endDate]))->getData();
        $forecastLabels = collect($forecastData)->pluck('date')->toArray();
        $forecastAttendance = collect($forecastData)->pluck('time_in_am')->toArray();
        $studentForecastChart = new \App\Charts\StudentForecastChart($forecastLabels, $forecastAttendance);

        // Subject Attendance
        $subjectData = $this->subjectAttendance($request)->getData();
        $subjectLabels = collect($subjectData)->pluck('subject_code')->toArray();
        $subjectPresent = collect($subjectData)->pluck('students_present')->toArray();
        $subjectAttendanceChart = new \App\Charts\SubjectAttendanceChart($subjectLabels, $subjectPresent);

        return [
            'attendanceTrendsChart' => $attendanceTrendsChart,
            'timePatternsChart' => $timePatternsChart,
            'absenteeismRatesChart' => $absenteeismRatesChart,
            'seasonalTrendsChart' => $seasonalTrendsChart,
            'studentForecastChart' => $studentForecastChart,
            'subjectAttendanceChart' => $subjectAttendanceChart,
        ];
    }

    public function getOverallStatistics(Request $request)
    {
        $absenteeismRates = $this->absenteeismRates($request)->getData();

        $seasonal = $this->seasonalTrends($request)->getData();
        $weekly = isset($seasonal->weekly) ? $seasonal->weekly : [];
        $monthly = isset($seasonal->monthly) ? $seasonal->monthly : [];

        $subjectAttendance = $this->subjectAttendance($request)->getData();

        $responseData = [
            'absenteeismRates' => $absenteeismRates,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'subjectAttendance' => $subjectAttendance
        ];

        \Log::debug('getOverallStatistics response data', $responseData);

        return response()->json($responseData);
    }

    public function attendanceToday(Request $request)
    {
        $date = now()->toDateString(); 
        $search = $request->get('search', '');
        $sectionFilter = $request->get('section_filter', '');

        $currentSemester = Semester::where('status', 'active')->first() ?? Semester::latest()->first();

        $activeSessions = AttendanceSession::with('semester')
                                         ->where('teacher_id', Auth::id())
                                         ->where('status', 'active')
                                         ->whereDate('started_at', Carbon::today('Asia/Manila'))
                                         ->orderBy('created_at', 'desc')
                                         ->get();
                                         
        $recentSessions = AttendanceSession::with('semester')
                                         ->where('teacher_id', Auth::id())
                                         ->where('status', '!=', 'active')
                                         ->orderBy('created_at', 'desc')
                                         ->limit(5)
                                         ->get();
                                         
        $semesters = Semester::where('school_id', Auth::user()->school_id)
                           ->orderBy('name')
                           ->get();

        $students = Student::with('section')
            ->where('user_id', Auth::id())
            ->where('semester_id', $currentSemester->id ?? 0)
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            })
            ->when($sectionFilter, function ($query, $sectionFilter) {
                return $query->whereHas('section', function ($q) use ($sectionFilter) {
                    $q->where('name', $sectionFilter);
                });
            })
            ->orderBy('name')->get();

        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        $attendanceList = $students->map(function ($student) use ($attendances) {
            $attendance = $attendances->get($student->id);
            return [
                'student' => $student,
                'time_in_am' => $attendance->time_in_am ?? null,
                'time_out_am' => $attendance->time_out_am ?? null,
                'time_in_pm' => $attendance->time_in_pm ?? null,
                'time_out_pm' => $attendance->time_out_pm ?? null,
            ];
        });

        $totalPresent = $attendanceList->filter(function ($row) {
            return $row['time_in_am'] || $row['time_in_pm'];
        })->count();
        $totalAbsent = $attendanceList->count() - $totalPresent;

        return view('teacher.attendance', [
            'attendanceList' => $attendanceList,
            'date' => $date,
            'search' => $search,
            'sectionFilter' => $sectionFilter,
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'totalStudents' => $attendanceList->count(),
            'currentSemester' => $currentSemester,
            'activeSessions' => $activeSessions,
            'recentSessions' => $recentSessions,
            'semesters' => $semesters,
        ]);
    }

    public function dailyTrends(Request $request)
        {
            $teacherId = Auth::id();
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $trends = Attendance::selectRaw('
                    DATE(date) as attendance_date,
                    COUNT(*) as total_students,
                    COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present_students,
                    COUNT(CASE WHEN time_in_am IS NULL AND time_in_pm IS NULL THEN 1 END) as absent_students
                ')
                ->where('teacher_id', $teacherId)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('attendance_date')
                ->orderBy('attendance_date')
                ->get();

            Log::info('dailyTrends fetched count', ['count' => $trends->count()]);
            return response()->json($trends);
        }

        // Time-in and Time-out Patterns
        public function timePatterns(Request $request)
        {
            $teacherId = Auth::id();
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $patterns = Attendance::select('student_id', 'date', 'time_in_am', 'time_out_pm')
                ->where('teacher_id', $teacherId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            Log::info('timePatterns fetched count', ['count' => $patterns->count()]);
            return response()->json($patterns);
        }

        // Absenteeism Rates
        public function absenteeismRates(Request $request)
        {
            $teacherId = Auth::id();
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $students = Student::where('user_id', $teacherId)->get();
            $rates = [];
            foreach ($students as $student) {
                $totalClasses = Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();
                $attended = Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->where(function($q){
                        $q->whereNotNull('time_in_am')->orWhereNotNull('time_in_pm');
                    })
                    ->count();
                $rates[] = [
                    'student_id' => $student->id,
                    'total_classes' => $totalClasses,
                    'classes_attended' => $attended,
                    'attendance_percentage' => $totalClasses ? round(($attended/$totalClasses)*100,2) : 0
                ];
            }
            Log::info('absenteeismRates fetched count', ['count' => count($rates)]);
            return response()->json($rates);
        }

        // Subject/Class Attendance
        public function subjectAttendance(Request $request)
        {
            $teacherId = Auth::id();
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $attendance = Attendance::selectRaw('
                    sections.name as subject_code, DATE(attendances.date) as attendance_date, COUNT(DISTINCT attendances.student_id) as students_present
                ')
                ->join('sections', 'attendances.teacher_id', '=', 'sections.teacher_id')
                ->where('attendances.teacher_id', $teacherId)
                ->whereBetween('attendances.date', [$startDate, $endDate])
                ->where(function($q){
                    $q->whereNotNull('attendances.time_in_am')->orWhereNotNull('attendances.time_in_pm');
                })
                ->groupBy('sections.name', 'attendance_date')
                ->orderBy('attendance_date')
                ->get();

            Log::info('subjectAttendance fetched count', ['count' => $attendance->count()]);
            return response()->json($attendance);
        }

        // Weekly/Monthly Attendance Trends
        public function seasonalTrends(Request $request)
        {
            $teacherId = Auth::id();
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $weekly = Attendance::selectRaw('
                    YEARWEEK(date, 1) as week, COUNT(*) as attendance_count
                ')
                ->where('teacher_id', $teacherId)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('week')
                ->orderBy('week')
                ->get();

            $monthly = Attendance::selectRaw('
                    DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as attendance_count
                ')
                ->where('teacher_id', $teacherId)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            Log::info('seasonalTrends fetched count', ['weekly' => $weekly->count(), 'monthly' => $monthly->count()]);
            return response()->json(['weekly' => $weekly, 'monthly' => $monthly]);
        }

         public function studentForecast(Request $request)
        {
            $teacherId = Auth::id();
            $studentId = $request->get('student_id');
            $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $forecast = Attendance::select('student_id', 'date', 'time_in_am', 'time_out_pm')
                ->where('teacher_id', $teacherId)
                ->where('student_id', $studentId)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get();

            Log::info('studentForecast fetched count', ['count' => $forecast->count()]);
            return response()->json($forecast);
        }
    
}
