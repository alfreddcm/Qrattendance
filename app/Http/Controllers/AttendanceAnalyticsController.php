<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Section;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceAnalyticsController extends Controller 
{
    
    public function statistics(Request $request)
    {
        $teacherId = Auth::id();
        $currentSemester = $this->getCurrentSemester();
        $sections = $this->getTeacherSections($teacherId);
        
        return view('teacher.statistics', compact('currentSemester', 'sections'));
    }

    /**
     * Display today's attendance overview page
     */
    public function attendanceToday(Request $request)
    {
        $teacherId = Auth::id();
        $teacher = Auth::user();
        
         $currentSemester = Semester::where('status', 'active')->first();
        if (!$currentSemester) {
            $currentSemester = Semester::latest('created_at')->first();
        }
        
         if ($teacher->school_id) {
            $semesters = Semester::where('school_id', $teacher->school_id)->orderBy('created_at', 'desc')->get();
        } else {
            $semesters = Semester::orderBy('created_at', 'desc')->get();
        }
        
         $sections = $this->getTeacherSections($teacherId);
        
         $activeSessions = AttendanceSession::where('teacher_id', $teacherId)
            ->whereDate('created_at', today())
            ->where('status', 'active')
            ->with('semester')
            ->get();
            
         $recentSessions = AttendanceSession::where('teacher_id', $teacherId)
            ->where('status', 'closed')
            ->with('semester')
            ->latest()
            ->take(10)
            ->get();
            
         $today = today();
        
         $search = $request->get('search');
        $sectionFilter = $request->get('section_filter');
        
         $studentsQuery = Student::where('user_id', $teacherId);
        if ($currentSemester) {
            $studentsQuery->where('semester_id', $currentSemester->id);
        }
        
         if ($search) {
            $studentsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('id_no', 'like', '%' . $search . '%');
            });
        }
        
         if ($sectionFilter) {
            $studentsQuery->where('section_id', $sectionFilter);
        }
        
        $students = $studentsQuery->with(['section', 'attendances' => function($q) use ($today) {
            $q->whereDate('date', $today);
        }])->get();
        
         $totalStudents = $students->count();
        $totalPresent = $students->filter(function($student) {
            return $student->attendances->isNotEmpty() && 
                   ($student->attendances->first()->time_in_am || $student->attendances->first()->time_in_pm);
        })->count();
        $totalAbsent = $totalStudents - $totalPresent;
        
         $attendanceList = $students->map(function($student, $index) {
            $attendance = $student->attendances->first();
            
             $status = 'Absent';
            $statusClass = 'bg-danger';
            
            if ($attendance) {
                if ($attendance->time_in_am && $attendance->time_in_pm) {
                    $status = 'Present';
                    $statusClass = 'bg-success';
                } elseif ($attendance->time_in_am || $attendance->time_in_pm) {
                    $status = 'Partial';
                    $statusClass = 'bg-warning';
                } elseif ($attendance->time_out_am || $attendance->time_out_pm) {
                    $status = 'Time Out Only';
                    $statusClass = 'bg-info';
                }
            }
            
            return [
                'index' => $index + 1,
                'student' => $student,
                'status' => $status,
                'status_class' => $statusClass,
                'attendance' => $attendance,
                'time_in_am' => $attendance ? $attendance->time_in_am : null,
                'time_out_am' => $attendance ? $attendance->time_out_am : null,
                'time_in_pm' => $attendance ? $attendance->time_in_pm : null,
                'time_out_pm' => $attendance ? $attendance->time_out_pm : null,
            ];
        });
        
        return view('teacher.attendance', compact(
            'activeSessions',
            'recentSessions', 
            'semesters',
            'sections',
            'totalPresent',
            'totalAbsent', 
            'totalStudents',
            'attendanceList',
            'search'
        ));
    }

    /**
     * Get attendance trend data for Chart.js
     */
    public function getAttendanceTrend(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

        $trends = Attendance::selectRaw('
                DATE(date) as attendance_date,
                COUNT(*) as total_records,
                COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present_count
            ')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        $chartData = [
            'labels' => $trends->pluck('attendance_date')->map(function($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'datasets' => [
                [
                    'label' => 'Present Students',
                    'data' => $trends->pluck('present_count')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                ]
            ]
        ];

        return response()->json($chartData);
    }

    /**
     * Get absenteeism rate data for Chart.js
     */
    public function getAbsenteeismRates(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

        Log::info('Absenteeism Debug:', [
            'teacher_id' => $teacherId,
            'student_count' => $studentIds->count(),
            'student_ids' => $studentIds->toArray(),
            'filters' => $filters
        ]);

        if ($studentIds->isEmpty()) {
            return response()->json([
                'labels' => ['No Data'],
                'datasets' => [
                    [
                        'label' => 'Absenteeism Rate (%)',
                        'data' => [0],
                        'backgroundColor' => 'rgba(200, 200, 200, 0.2)',
                        'borderColor' => 'rgba(200, 200, 200, 1)',
                        'borderWidth' => 2
                    ]
                ]
            ]);
        }

        $absenteeismData = $studentIds->map(function($studentId) use ($filters) {
            $totalDays = Attendance::where('student_id', $studentId)
                ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
                ->count();

            $absentDays = Attendance::where('student_id', $studentId)
                ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
                ->whereNull('time_in_am')
                ->whereNull('time_in_pm')
                ->count();

            $absenteeismRate = $totalDays > 0 ? round(($absentDays / $totalDays) * 100, 1) : 0;

            $student = Student::find($studentId);
            $studentName = $student ? $this->formatStudentName($student->name) : 'Unknown Student';

            return [
                'student_id' => $studentId,
                'student_name' => $studentName,
                'absenteeism_rate' => $absenteeismRate,
                'total_days' => $totalDays,
                'absent_days' => $absentDays
            ];
        })->filter(function($item) {
            return $item['total_days'] > 0;  
        })->sortByDesc('absenteeism_rate')
          ->take(15)  
          ->values();

        // Color code based on absenteeism rate
        $backgroundColors = $absenteeismData->map(function($item) {
            $rate = $item['absenteeism_rate'];
            if ($rate >= 50) return 'rgba(255, 99, 132, 0.8)';  
            if ($rate >= 30) return 'rgba(255, 159, 64, 0.8)';  
            if ($rate >= 15) return 'rgba(255, 206, 86, 0.8)';  
            return 'rgba(75, 192, 192, 0.8)'; 
        })->toArray();

        $borderColors = $absenteeismData->map(function($item) {
            $rate = $item['absenteeism_rate'];
            if ($rate >= 50) return 'rgba(255, 99, 132, 1)';
            if ($rate >= 30) return 'rgba(255, 159, 64, 1)';
            if ($rate >= 15) return 'rgba(255, 206, 86, 1)';
            return 'rgba(75, 192, 192, 1)';
        })->toArray();

        $chartData = [
            'labels' => $absenteeismData->pluck('student_name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Absenteeism Rate (%)',
                    'data' => $absenteeismData->pluck('absenteeism_rate')->toArray(),
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2
                ]
            ]
        ];

        return response()->json($chartData);
    }

    /**
     * Get weekly attendance trend for Chart.js
     */
    public function getWeeklyTrend(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

        $weeklyData = [];
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($daysOfWeek as $index => $day) {
            $dayNumber = $index + 1; 
            
            $dayAttendance = Attendance::selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present
                ')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
                ->whereRaw('DAYOFWEEK(date) = ?', [$dayNumber + 1])
                ->first();

            $attendanceRate = $dayAttendance && $dayAttendance->total > 0 ? 
                round(($dayAttendance->present / $dayAttendance->total) * 100, 1) : 0;

            $weeklyData[] = $attendanceRate;
        }

        $chartData = [
            'labels' => $daysOfWeek,
            'datasets' => [
                [
                    'label' => 'Attendance Rate (%)',
                    'data' => $weeklyData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2
                ]
            ]
        ];

        return response()->json($chartData);
    }

    /**
     * Get monthly attendance trend for Chart.js
     */
    public function getMonthlyTrend(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

        $monthlyData = Attendance::selectRaw('
                DATE_FORMAT(date, "%Y-%m") as month,
                COUNT(*) as total_records,
                COUNT(CASE WHEN time_in_am IS NOT NULL OR time_in_pm IS NOT NULL THEN 1 END) as present_count
            ')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $chartData = [
            'labels' => $monthlyData->pluck('month')->map(function($month) {
                return Carbon::parse($month . '-01')->format('M Y');
            })->toArray(),
            'datasets' => [
                [
                    'label' => 'Present Students',
                    'data' => $monthlyData->pluck('present_count')->toArray(),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 2
                ]
            ]
        ];

        return response()->json($chartData);
    }

    /**
     * Get daily attendance time patterns with AM/PM in/out times
     */
    public function getTimePatterns(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

         $attendanceByDate = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->where(function($query) {
                $query->whereNotNull('time_in_am')
                      ->orWhereNotNull('time_out_am')
                      ->orWhereNotNull('time_in_pm')
                      ->orWhereNotNull('time_out_pm');
            })
            ->select('date', 'time_in_am', 'time_out_am', 'time_in_pm', 'time_out_pm')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        $labels = [];
        $avgAmTimeIn = [];
        $avgAmTimeOut = [];
        $avgPmTimeIn = [];
        $avgPmTimeOut = [];

        foreach ($attendanceByDate as $date => $records) {
            $labels[] = Carbon::parse($date)->format('M d');
            
             $amInTimes = $records->whereNotNull('time_in_am')->pluck('time_in_am');
            $avgAmTimeIn[] = $this->calculateAverageTime($amInTimes);
            
             $amOutTimes = $records->whereNotNull('time_out_am')->pluck('time_out_am');
            $avgAmTimeOut[] = $this->calculateAverageTime($amOutTimes);
            
             $pmInTimes = $records->whereNotNull('time_in_pm')->pluck('time_in_pm');
            $avgPmTimeIn[] = $this->calculateAverageTime($pmInTimes);
            
             $pmOutTimes = $records->whereNotNull('time_out_pm')->pluck('time_out_pm');
            $avgPmTimeOut[] = $this->calculateAverageTime($pmOutTimes);
        }

        $chartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'AM Time In',
                    'data' => $avgAmTimeIn,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.1
                ],
                [
                    'label' => 'AM Time Out',
                    'data' => $avgAmTimeOut,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.1
                ],
                [
                    'label' => 'PM Time In',
                    'data' => $avgPmTimeIn,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                    'borderColor' => 'rgba(249, 115, 22, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.1
                ],
                [
                    'label' => 'PM Time Out',
                    'data' => $avgPmTimeOut,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.6)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.1
                ]
            ]
        ];

        return response()->json($chartData);
    }

    /**
     * Calculate average time from a collection of time strings
     */
    private function calculateAverageTime($times)
    {
        if ($times->isEmpty()) return null;
        
        $totalMinutes = 0;
        $count = 0;
        
        foreach ($times as $time) {
            if ($time) {
                $timeObj = Carbon::parse($time);
                $totalMinutes += ($timeObj->hour * 60) + $timeObj->minute;
                $count++;
            }
        }
        
        if ($count === 0) return null;
        
        $avgMinutes = $totalMinutes / $count;
        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;
        
        return $hours + ($minutes / 60); // Return as decimal hours for chart
    }

    /**
     * Convert time string to decimal hours
     */
    private function timeToHours($timeString)
    {
        if (!$timeString) return 0;
        
        $time = Carbon::parse($timeString);
        return $time->hour + ($time->minute / 60);
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getSummaryStats(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);

        $totalStudents = $studentIds->count();
        
        $totalAttendanceRecords = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->count();

        $totalPresentRecords = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->where(function($query) {
                $query->whereNotNull('time_in_am')->orWhereNotNull('time_in_pm');
            })
            ->count();

        $averageAttendanceRate = $totalAttendanceRecords > 0 ? 
            round(($totalPresentRecords / $totalAttendanceRecords) * 100, 1) : 0;

        return response()->json([
            'total_students' => $totalStudents,
            'total_attendance_records' => $totalAttendanceRecords,
            'total_present_records' => $totalPresentRecords,
            'average_attendance_rate' => $averageAttendanceRate
        ]);
    }

    /**
     * Get current semester with date validation
     */
    private function getCurrentSemester()
    {
        return Semester::where('school_id', Auth::user()->school_id)
            ->whereDate('start_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now())
            ->first();
    }

    /**
     * Get sections assigned to the authenticated teacher
     */
    private function getTeacherSections($teacherId)
    {
        try {
            // First try direct teacher assignment
            $sections = Section::where('teacher_id', $teacherId)
                ->withCount('students')
                ->with(['semester'])
                ->get();

            Log::info('Teacher Sections Debug:', [
                'teacher_id' => $teacherId,
                'direct_sections_found' => $sections->count(),
                'direct_section_details' => $sections->toArray()
            ]);

            // If no direct sections found, try many-to-many relationship
            if ($sections->isEmpty()) {
                $sections = Section::whereHas('teachers', function($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->withCount('students')
                ->with(['semester'])
                ->get();

                Log::info('Teacher Sections Many-to-Many Debug:', [
                    'teacher_id' => $teacherId,
                    'pivot_sections_found' => $sections->count(),
                    'pivot_section_details' => $sections->toArray()
                ]);
            }

            return $sections;
        } catch (\Exception $e) {
            Log::error('Error getting teacher sections:', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    /**
     * Validate and get filter parameters
     */
    private function getValidatedFilters(Request $request)
    {
        $currentSemester = $this->getCurrentSemester();
        
        $defaultStartDate = $currentSemester ? 
            $currentSemester->start_date->toDateString() : 
            Carbon::now()->subMonth()->toDateString();
            
        $defaultEndDate = $currentSemester ? 
            $currentSemester->end_date->toDateString() : 
            Carbon::now()->toDateString();

        return [
            'semester_id' => $request->get('semester_id', $currentSemester ? $currentSemester->id : null),
            'section_id' => $request->get('section_id', 'all'),
            'start_date' => $request->get('start_date', $defaultStartDate),
            'end_date' => $request->get('end_date', $defaultEndDate)
        ];
    }

    /**
     * Get filtered student IDs based on teacher and section
     */
    private function getFilteredStudentIds($teacherId, $filters)
    {
        try {
            // Get sections that belong to this teacher - try direct assignment first
            $teacherSectionIds = Section::where('teacher_id', $teacherId)->pluck('id');

            // If no direct sections, try many-to-many relationship
            if ($teacherSectionIds->isEmpty()) {
                $teacherSectionIds = Section::whereHas('teachers', function($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })->pluck('id');
            }

            Log::info('Student Filter Debug:', [
                'teacher_id' => $teacherId,
                'teacher_section_ids' => $teacherSectionIds->toArray(),
                'filters' => $filters
            ]);

            if ($teacherSectionIds->isEmpty()) {
                Log::warning('No sections found for teacher', ['teacher_id' => $teacherId]);
                return collect([]);
            }

            // Start with students in teacher's sections
            $studentsQuery = Student::whereIn('section_id', $teacherSectionIds);

            if ($filters['semester_id']) {
                $studentsQuery->where('semester_id', $filters['semester_id']);
            }

            if ($filters['section_id'] && $filters['section_id'] !== 'all') {
                $studentsQuery->where('section_id', $filters['section_id']);
            }

            $studentIds = $studentsQuery->pluck('id');
            
            Log::info('Final student IDs:', [
                'count' => $studentIds->count(),
                'student_ids' => $studentIds->take(20)->toArray() // Only log first 20 for brevity
            ]);

            return $studentIds;
        } catch (\Exception $e) {
            Log::error('Error getting filtered student IDs:', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    /**
     * Format student name for display
     */
    private function formatStudentName($name)
    {
        if (empty($name)) {
            return 'Unknown Student';
        }

        $name = trim($name);
        
        if (strpos($name, ',') !== false) {
            return $name; 
        }

        $nameParts = explode(' ', $name);
        if (count($nameParts) >= 2) {
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
            return $lastName . ', ' . $firstName;
        }

        return $name;
    }

    /**
     * Get chart data for compatibility with TeacherController
     */
    public function getChartData(Request $request)
    {
        $teacherId = Auth::id();
        $filters = $this->getValidatedFilters($request);
        $studentIds = $this->getFilteredStudentIds($teacherId, $filters);
        
        $totalRecords = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->count();
            
        $presentRecords = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$filters['start_date'], $filters['end_date']])
            ->where(function($query) {
                $query->whereNotNull('time_in_am')
                      ->orWhereNotNull('time_in_pm');
            })
            ->count();
            
        $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;
        
        return [
            'total_students' => $studentIds->count(),
            'total_records' => $totalRecords,
            'present_records' => $presentRecords,
            'attendance_rate' => $attendanceRate,
            'period' => [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date']
            ]
        ];
    }
}
