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

class AttendanceAnalyticsController extends Controller
{
    /**
     * Display today's attendance for teacher's students with session management
     */
    public function attendanceToday(Request $request)
    {
        $date = now()->toDateString(); // Always use today's date
        $search = $request->get('search', '');

        $currentSemester = Semester::where('status', 'active')->first() ?? Semester::latest()->first();

        // Get session data
        $activeSessions = AttendanceSession::with('semester')
                                         ->where('teacher_id', Auth::id())
                                         ->active()
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

        $students = Student::where('user_id', Auth::id())
            ->where('semester_id', $currentSemester->id ?? 0)
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            })->orderBy('name')->get();

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
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'totalStudents' => $attendanceList->count(),
            'currentSemester' => $currentSemester,
            'activeSessions' => $activeSessions,
            'recentSessions' => $recentSessions,
            'semesters' => $semesters,
        ]);
    }

    public function getOverallStatistics(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $dateRange = $request->get('date_range', '30'); 
        
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($dateRange);
        
        $query = Attendance::with('student')
            ->whereHas('student', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->whereBetween('date', [$startDate, $endDate]);
            
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        $attendances = $query->get();
        $totalStudents = Student::where('user_id', Auth::id())->when($semesterId, function($q) use ($semesterId) {
            return $q->where('semester_id', $semesterId);
        })->count();
        
        $statistics = [
            'total_students' => $totalStudents,
            'total_attendance_records' => $attendances->count(),
            'attendance_rate' => $this->calculateAttendanceRate($attendances, $totalStudents, $dateRange),
            'punctuality_rate' => $this->calculatePunctualityRate($attendances),
            'daily_average' => $this->calculateDailyAverage($attendances),
            'gender_distribution' => $this->getGenderDistribution($attendances),
            'late_arrivals' => $this->getLateArrivals($attendances),
            'early_departures' => $this->getEarlyDepartures($attendances),
        ];
        
        return response()->json($statistics);
    }

    public function getDailyTrends(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $dateRange = $request->get('date_range', '30');
        
        // Calculate start and end dates based on date range
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::now()->subDays((int)$dateRange)->toDateString();
        
        $query = Attendance::selectRaw('
            DATE(date) as attendance_date,
            COUNT(*) as total_attendances,
            COUNT(CASE WHEN time_in_am IS NOT NULL THEN 1 END) as morning_attendances,
            COUNT(CASE WHEN time_in_pm IS NOT NULL THEN 1 END) as afternoon_attendances,
            COUNT(CASE WHEN time_in_am IS NOT NULL AND time_out_am IS NOT NULL 
                      AND time_in_pm IS NOT NULL AND time_out_pm IS NOT NULL THEN 1 END) as full_day_attendances
        ')
        ->whereHas('student', function($q) {
            $q->where('user_id', Auth::id());
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->groupBy('attendance_date')
        ->orderBy('attendance_date');
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        $trends = $query->get();
        
        return response()->json($trends);
    }

    public function getStudentPerformance(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $limit = $request->get('limit', 10);
        $dateRange = $request->get('date_range', '30');
        
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($dateRange);
        
        $query = Student::with(['semester'])
            ->where('students.user_id', Auth::id())
            ->leftJoin('attendances', 'students.id', '=', 'attendances.student_id')
            ->selectRaw('
                students.id,
                students.id_no,
                students.name,
                students.gender,
                COUNT(attendances.id) as total_attendances,
                COUNT(CASE WHEN attendances.time_in_am IS NOT NULL THEN 1 END) as morning_attendances,
                COUNT(CASE WHEN attendances.time_in_pm IS NOT NULL THEN 1 END) as afternoon_attendances,
                COUNT(CASE WHEN attendances.time_in_am IS NOT NULL AND attendances.time_out_am IS NOT NULL 
                          AND attendances.time_in_pm IS NOT NULL AND attendances.time_out_pm IS NOT NULL THEN 1 END) as full_day_attendances,
                AVG(CASE WHEN attendances.time_in_am IS NOT NULL THEN 1 ELSE 0 END) * 100 as attendance_rate
            ')
            ->whereBetween('attendances.date', [$startDate, $endDate])
            ->groupBy('students.id', 'students.id_no', 'students.name', 'students.gender')
            ->orderBy('attendance_rate', 'desc')
            ->limit($limit);
        
        if ($semesterId) {
            $query->where('students.semester_id', $semesterId);
        }
        
        $performance = $query->get();
        
        return response()->json($performance);
    }

    public function getAttendancePatterns(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $dateRange = $request->get('date_range', '30');
        
        // Calculate start and end dates based on date range
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::now()->subDays((int)$dateRange)->toDateString();
        
        $query = Attendance::selectRaw('
            DAYNAME(date) as day_name,
            DAYOFWEEK(date) as day_number,
            COUNT(*) as total_attendances,
            COUNT(CASE WHEN time_in_am IS NOT NULL THEN 1 END) as morning_count,
            COUNT(CASE WHEN time_in_pm IS NOT NULL THEN 1 END) as afternoon_count,
            AVG(CASE WHEN time_in_am IS NOT NULL THEN 1 ELSE 0 END) * 100 as morning_rate,
            AVG(CASE WHEN time_in_pm IS NOT NULL THEN 1 ELSE 0 END) * 100 as afternoon_rate
        ')
        ->whereHas('student', function($q) {
            $q->where('user_id', Auth::id());
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->groupBy('day_name', 'day_number')
        ->orderBy('day_number');
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        $patterns = $query->get();
        
        return response()->json($patterns);
    }

    public function getTimeDistribution(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $dateRange = $request->get('date_range', '30');
        
        // Calculate start and end dates based on date range
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::now()->subDays((int)$dateRange)->toDateString();
        
        $query = Attendance::selectRaw('
            HOUR(time_in_am) as hour,
            COUNT(*) as count,
            "morning" as session
        ')
        ->whereHas('student', function($q) {
            $q->where('user_id', Auth::id());
        })
        ->whereBetween('date', [$startDate, $endDate])
        ->whereNotNull('time_in_am')
        ->groupBy('hour')
        ->unionAll(
            Attendance::selectRaw('
                HOUR(time_in_pm) as hour,
                COUNT(*) as count,
                "afternoon" as session
            ')
            ->whereHas('student', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('time_in_pm')
            ->groupBy('hour')
        );
        
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        
        $distribution = $query->get();
        
        return response()->json($distribution);
    }

    private function calculateAttendanceRate($attendances, $totalStudents, $dateRange)
    {
        if ($totalStudents == 0) return 0;
        
        $expectedAttendances = $totalStudents * $dateRange;
        $actualAttendances = $attendances->count();
        
        return round(($actualAttendances / $expectedAttendances) * 100, 2);
    }

    private function calculatePunctualityRate($attendances)
    {
        $totalMorningAttendances = $attendances->whereNotNull('time_in_am')->count();
        if ($totalMorningAttendances == 0) return 0;
        
        $punctualAttendances = $attendances->filter(function($attendance) {
            return $attendance->time_in_am && Carbon::parse($attendance->time_in_am)->format('H:i') <= '08:00';
        })->count();
        
        return round(($punctualAttendances / $totalMorningAttendances) * 100, 2);
    }

    private function calculateDailyAverage($attendances)
    {
        $dailyAttendances = $attendances->groupBy('date');
        if ($dailyAttendances->count() == 0) return 0;
        
        $totalDays = $dailyAttendances->count();
        $totalAttendances = $attendances->count();
        
        return round($totalAttendances / $totalDays, 2);
    }

    private function getGenderDistribution($attendances)
    {
        return $attendances->groupBy('student.gender')->map(function($group) {
            return $group->count();
        });
    }

    private function getLateArrivals($attendances)
    {
        return $attendances->filter(function($attendance) {
            return $attendance->time_in_am && Carbon::parse($attendance->time_in_am)->format('H:i') > '08:00';
        })->count();
    }

    private function getEarlyDepartures($attendances)
    {
        return $attendances->filter(function($attendance) {
            return $attendance->time_out_pm && Carbon::parse($attendance->time_out_pm)->format('H:i') < '16:00';
        })->count();
    }
}
