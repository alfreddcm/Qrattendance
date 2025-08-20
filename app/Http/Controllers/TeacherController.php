<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\Section;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TeacherController extends Controller
{
     
    private function getCurrentSemesterId()
    {
        $today = Carbon::now()->toDateString();
        
        $activeSemester = Semester::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($activeSemester) {
            return $activeSemester->id;
        }
        
        $nearestSemester = Semester::orderByRaw('ABS(DATEDIFF(end_date, ?))', [$today])
            ->first();
            
        return $nearestSemester?->id ?? Semester::latest('start_date')->first()?->id;
    }

    
    public function dashboard(Request $request)
    {
        $semesters = Semester::orderBy('start_date')->get();
        $selectedSemester = $request->get('semester', $this->getCurrentSemesterId());

        $studentCount = Student::where('semester_id', $selectedSemester)->where('user_id', Auth::id())->count();

        $students = Student::where('semester_id', $selectedSemester)->where('user_id', Auth::id())->get();

        $attendancesToday = Attendance::where('semester_id', $selectedSemester)
            ->whereDate('date', now()->toDateString())
            ->pluck('student_id')
            ->toArray();

        $presentCount = $students->whereIn('id', $attendancesToday)->count();
        $absentCount = max($students->count() - $presentCount, 0);

        // Get teacher's sections for current semester to use their time periods
        $teacherSections = Section::where('teacher_id', Auth::id())
            ->where('semester_id', $selectedSemester)
            ->get();
        
        // Use the first section's time periods as default, or fallback to semester defaults
        $timePeriodsSource = $teacherSections->first();
        $currentSemester = $semesters->where('id', $selectedSemester)->first();
        
        $studentsWithMissingInfo = Student::where('semester_id', $selectedSemester)
            ->where('user_id', Auth::id())
            ->where(function($query) {
                $query->whereNull('picture')
                      ->orWhereNull('qr_code')
                      ->orWhereNull('cp_no')
                      ->orWhereNull('address')
                      ->orWhereNull('contact_person_name')
                      ->orWhereNull('gender')
                      ->orWhereNull('age')
                      ->orWhere('picture', '')
                      ->orWhere('qr_code', '')
                      ->orWhere('cp_no', '')
                      ->orWhere('address', '')
                      ->orWhere('contact_person_name', '')
                      ->orWhere('gender', '')
                      ->orWhere('age', '');
            })
            ->get();

        
        $mostAbsent = null;
        $totalDays = now()->diffInDays($semesters->where('id', $selectedSemester)->first()?->start_date ?? now()) + 1;
        
        // Calculate attendance counts for all students (needed for both mostAbsent/mostPunctual and section analytics)
        $studentAttendanceCounts = collect();
        if ($totalDays > 0) {
            $studentAttendanceCounts = Attendance::where('semester_id', $selectedSemester)
                ->whereIn('student_id', $students->pluck('id'))
                ->selectRaw('student_id, COUNT(*) as attendance_count')
                ->groupBy('student_id')
                ->pluck('attendance_count', 'student_id');

            $mostAbsentStudentId = null;
            $maxAbsences = 0;
            
            foreach ($students as $student) {
                $attendanceCount = $studentAttendanceCounts->get($student->id, 0);
                $absenceCount = max($totalDays - $attendanceCount, 0);
                
                if ($absenceCount > $maxAbsences) {
                    $maxAbsences = $absenceCount;
                    $mostAbsentStudentId = $student->id;
                }
            }
            
            if ($mostAbsentStudentId) {
                $mostAbsent = $students->where('id', $mostAbsentStudentId)->first();
                $mostAbsent->absence_count = $maxAbsences;
            }
        }

        
        $mostPunctual = null;
        if ($totalDays > 0) {
            $highestRate = 0;
            $mostPunctualStudentId = null;
            
            foreach ($students as $student) {
                $attendanceCount = $studentAttendanceCounts->get($student->id, 0);
                $punctualityRate = ($attendanceCount / $totalDays) * 100;
                
                if ($punctualityRate > $highestRate) {
                    $highestRate = $punctualityRate;
                    $mostPunctualStudentId = $student->id;
                }
            }
            
            if ($mostPunctualStudentId) {
                $mostPunctual = $students->where('id', $mostPunctualStudentId)->first();
                $mostPunctual->punctuality_rate = round($highestRate, 1);
            }
        }

        
        $currentSemester = $semesters->where('id', $selectedSemester)->first();
        
        // Override semester time periods with section time periods if available
        if ($timePeriodsSource && $currentSemester) {
            $currentSemester->am_time_in_start = $timePeriodsSource->am_time_in_start;
            $currentSemester->am_time_in_end = $timePeriodsSource->am_time_in_end;
            $currentSemester->am_time_out_start = $timePeriodsSource->am_time_out_start;
            $currentSemester->am_time_out_end = $timePeriodsSource->am_time_out_end;
            $currentSemester->pm_time_in_start = $timePeriodsSource->pm_time_in_start;
            $currentSemester->pm_time_in_end = $timePeriodsSource->pm_time_in_end;
            $currentSemester->pm_time_out_start = $timePeriodsSource->pm_time_out_start;
            $currentSemester->pm_time_out_end = $timePeriodsSource->pm_time_out_end;
        } elseif ($currentSemester) {
            // Provide fallback time periods if no sections are configured
            $currentSemester->am_time_in_start = $currentSemester->am_time_in_start ?? '07:00:00';
            $currentSemester->am_time_in_end = $currentSemester->am_time_in_end ?? '07:30:00';
            $currentSemester->am_time_out_start = $currentSemester->am_time_out_start ?? '11:30:00';
            $currentSemester->am_time_out_end = $currentSemester->am_time_out_end ?? '12:00:00';
            $currentSemester->pm_time_in_start = $currentSemester->pm_time_in_start ?? '13:00:00';
            $currentSemester->pm_time_in_end = $currentSemester->pm_time_in_end ?? '13:30:00';
            $currentSemester->pm_time_out_start = $currentSemester->pm_time_out_start ?? '16:30:00';
            $currentSemester->pm_time_out_end = $currentSemester->pm_time_out_end ?? '17:00:00';
        }

        
        $todaySession = null;
        if ($currentSemester) {
            $today = Carbon::today('Asia/Manila');
            $todaySession = \App\Models\AttendanceSession::where('teacher_id', Auth::id())
                ->whereDate('started_at', $today)
                ->where('status', 'active')
                ->with('semester')
                ->first();
        }

        // --- Add chart objects for statistics include ---
        $analytics = app(\App\Http\Controllers\AttendanceAnalyticsController::class);
        $chartRequest = new \Illuminate\Http\Request();
        $chartRequest->replace($request->all());
        $chartData = $analytics->getChartData($chartRequest);

        // Section-based analytics
        $sectionAnalytics = [];
        if ($currentSemester) {
            $sections = $students->groupBy(function($student) {
                return $student->grade_level . '|' . $student->section_name;
            });

            foreach ($sections as $sectionKey => $sectionStudents) {
                $parts = explode('|', $sectionKey);
                $gradeLevel = $parts[0] ?? '';
                $section = $parts[1] ?? '';
                $sectionName = "Grade {$gradeLevel} - {$section}";

                $sectionAttendanceToday = $sectionStudents->whereIn('id', $attendancesToday);
                $sectionPresentCount = $sectionAttendanceToday->count();
                $sectionAbsentCount = $sectionStudents->count() - $sectionPresentCount;

                // Calculate section percentage
                $sectionTotalStudents = $sectionStudents->count();
                $sectionAttendanceRate = $sectionTotalStudents > 0 ? round(($sectionPresentCount / $sectionTotalStudents) * 100, 1) : 0;

                // Find most punctual student in section
                $sectionMostPunctual = null;
                $sectionHighestRate = 0;
                
                foreach ($sectionStudents as $student) {
                    $attendanceCount = $studentAttendanceCounts->get($student->id, 0) ?? 0;
                    $punctualityRate = $totalDays > 0 ? ($attendanceCount / $totalDays) * 100 : 0;
                    
                    if ($punctualityRate > $sectionHighestRate) {
                        $sectionHighestRate = $punctualityRate;
                        $sectionMostPunctual = $student;
                        $sectionMostPunctual->punctuality_rate = round($punctualityRate, 1);
                    }
                }

                // Find most absent student in section
                $sectionMostAbsent = null;
                $sectionMaxAbsences = 0;
                
                foreach ($sectionStudents as $student) {
                    $attendanceCount = $studentAttendanceCounts->get($student->id, 0) ?? 0;
                    $absenceCount = max($totalDays - $attendanceCount, 0);
                    
                    if ($absenceCount > $sectionMaxAbsences) {
                        $sectionMaxAbsences = $absenceCount;
                        $sectionMostAbsent = $student;
                        $sectionMostAbsent->absence_count = $absenceCount;
                    }
                }

                $sectionAnalytics[] = [
                    'name' => $sectionName,
                    'grade_level' => $gradeLevel,
                    'section' => $section,
                    'total_students' => $sectionTotalStudents,
                    'present_count' => $sectionPresentCount,
                    'absent_count' => $sectionAbsentCount,
                    'attendance_rate' => $sectionAttendanceRate,
                    'most_punctual' => $sectionMostPunctual,
                    'most_absent' => $sectionMostAbsent,
                ];
            }
        }

        // Additional variables for dashboard view compatibility
        $myStudents = $studentCount;
        $mySections = $teacherSections->count();
        $todayPresent = $presentCount;
        $attendanceRate = $studentCount > 0 ? round(($presentCount / $studentCount) * 100, 1) . '%' : '0%';
        
        // Add student counts to teacher sections
        $teacherSections = $teacherSections->map(function($section) use ($selectedSemester) {
            $section->students_count = $section->students()->where('semester_id', $selectedSemester)->count();
            $section->present_today = $section->students()
                ->where('semester_id', $selectedSemester)
                ->whereHas('attendances', function($query) {
                    $query->whereDate('date', now()->toDateString());
                })
                ->count();
            return $section;
        });

        return view('teacher.dashboard', array_merge(compact(
            'semesters',
            'selectedSemester',
            'currentSemester',
            'studentCount',
            'presentCount',
            'absentCount',
            'studentsWithMissingInfo',
            'mostAbsent',
            'mostPunctual',
            'todaySession',
            'sectionAnalytics',
            'myStudents',
            'mySections',
            'todayPresent',
            'attendanceRate',
            'teacherSections'
        ), $chartData));
    }

    
    public function students()
    {
        $selectedSemester = $this->getCurrentSemesterId();
        
        $students = Student::where('user_id', Auth::id())
            ->where('semester_id', $selectedSemester)
            ->orderBy('id_no')
            ->get();
        
        return view('teacher.students', compact('students'));
    }

    public function message()
    {
        $selectedSemester = $this->getCurrentSemesterId();
        
        $students = Student::where('user_id', Auth::id())
            ->where('semester_id', $selectedSemester)
            ->orderBy('name')
            ->get();
        
        return view('teacher.message', compact('students'));
    }

    
    public function semesters()
    {
        $semesters = Semester::orderBy('start_date', 'desc')->get();
        
        // Get teacher's sections
        $sections = \App\Models\Section::where('teacher_id', Auth::id())
            ->with(['semester'])
            ->orderBy('gradelevel')
            ->orderBy('name')
            ->get();
            
        return view('teacher.semester', compact('semesters', 'sections'));
    }

    
    public function updateSemesterStatus(Request $request)
    {
        Log::info('Semester update request', [
            'teacher_id' => Auth::id(),
            'semester_id' => $request->semester_id,
            'new_status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        try {
            $request->validate([
                'semester_id' => 'required|exists:semesters,id',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ]);
        } catch (\Exception $e) {
            Log::warning('Semester update validation failed', [
                'teacher_id' => Auth::id(),
                'semester_id' => $request->semester_id,
                'validation_errors' => $e->getMessage(),
            ]);
            throw $e;
        }

        
        if ($request->am_time_in_start >= $request->am_time_in_end) {
            return back()->withErrors(['am_time_in_end' => 'AM time in end must be after start time.']);
        }
        
        if ($request->pm_time_out_start >= $request->pm_time_out_end) {
            return back()->withErrors(['pm_time_out_end' => 'PM time out end must be after start time.']);
        }
        
        if ($request->am_time_in_end >= $request->pm_time_out_start) {
            return back()->withErrors(['pm_time_out_start' => 'PM time out start must be after AM time in end.']);
        }

        $semester = Semester::findOrFail($request->semester_id);
        
        $updateData = $request->only([
            'status', 'start_date', 'end_date',
            'am_time_in_start', 'am_time_in_end',
            'pm_time_out_start', 'pm_time_out_end'
        ]);
        
        
        $updateData['am_time_out_start'] = null;
        $updateData['am_time_out_end'] = null;
        $updateData['pm_time_in_start'] = null;
        $updateData['pm_time_in_end'] = null;
        
        $semester->update($updateData);

        return back()->with('success', 'Semester updated successfully!');
    }

    
    public function editSemester($id)
    {
        $semester = Semester::findOrFail($id);
        
        
        $studentCount = Student::where('semester_id', $semester->id)->count();
        $attendanceCount = Attendance::where('semester_id', $semester->id)->count();
        
        $responseData = [
            'id' => $semester->id,
            'name' => $semester->name,
            'status' => $semester->status,
            'start_date' => $semester->start_date,
            'end_date' => $semester->end_date,
            'am_time_in_start_input' => $semester->am_time_in_start_input,
            'am_time_in_end_input' => $semester->am_time_in_end_input,
            'pm_time_out_start_input' => $semester->pm_time_out_start_input,
            'pm_time_out_end_input' => $semester->pm_time_out_end_input,
            'student_count' => $studentCount,
            'attendance_count' => $attendanceCount,
        ];
        
        return response()->json($responseData);
    }

    
    public function getSemesterData($id)
    {
        $semester = Semester::findOrFail($id);
        return response()->json([
            'success' => true,
            'semester' => [
                'id' => $semester->id,
                'name' => $semester->name,
                'start_date' => $semester->start_date,
                'end_date' => $semester->end_date,
            ]
        ]);
    }

    public function account()
    {
        $teacher = Auth::user();
        return view('teacher.manageaccount', compact('teacher'));
    }

    public function update(Request $request)
    {
        $teacher = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->id,
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'section_name' => 'nullable|string|max:100',
        ]);

        $teacher->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'position' => $request->position,
            'section_name' => $request->section_name,
        ]);

        return redirect()->route('teacher.account')->with('success', 'Account updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $teacher = Auth::user();
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $teacher->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        // Update password
        $teacher->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('teacher.account')->with('success', 'Password updated successfully!');
    }

    
}
