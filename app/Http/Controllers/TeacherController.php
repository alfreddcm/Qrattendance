<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ValidatesForResponse;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\Attendance;
use App\Models\Section;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TeacherController extends Controller
{
    use ValidatesForResponse;
      
    private function getCurrentSchoolYearId()
    {
        $user = Auth::user();
        $currentSchoolYear = SchoolYear::getCurrentSchoolYear($user->school_id);
        return $currentSchoolYear ? $currentSchoolYear->id : null;
    }

    
    public function dashboard(Request $request)
    {
        $schoolYears = SchoolYear::orderBy('start_date')->get();
        $selectedSchoolYear = $request->get('semester', $this->getCurrentSchoolYearId());
        $studentCount = Student::where('school_year_id', $selectedSchoolYear)->where('user_id', Auth::id())->count();
        $students = Student::where('school_year_id', $selectedSchoolYear)->where('user_id', Auth::id())->get();
        $attendancesToday = Attendance::where('school_year_id', $selectedSchoolYear)
            ->whereDate('date', now()->toDateString())
            ->pluck('student_id')
            ->toArray();

        $presentCount = $students->whereIn('id', $attendancesToday)->count();
        $absentCount = max($students->count() - $presentCount, 0);

         $teacherSections = Section::where('teacher_id', Auth::id())
            ->where('school_year_id', $selectedSchoolYear)
            ->get();
        
         $timePeriodsSource = $teacherSections->first();
        $currentSchoolYear = $schoolYears->where('id', $selectedSchoolYear)->first();
        
        $studentsWithMissingInfo = Student::where('school_year_id', $selectedSchoolYear)
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
        $totalDays = now()->diffInDays($schoolYears->where('id', $selectedSchoolYear)->first()?->start_date ?? now()) + 1;
        
         $studentAttendanceCounts = collect();
        if ($totalDays > 0) {
            $studentAttendanceCounts = Attendance::where('school_year_id', $selectedSchoolYear)
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

        $user = Auth::user();
        $currentSchoolYear = SchoolYear::getCurrentSchoolYear($user->school_id);
        $selectedSchoolYearObj = $schoolYears->where('id', $selectedSchoolYear)->first();
        
         if ($timePeriodsSource && $currentSchoolYear) {
            $currentSchoolYear->am_time_in_start = $timePeriodsSource->am_time_in_start;
            $currentSchoolYear->am_time_in_end = $timePeriodsSource->am_time_in_end;
            $currentSchoolYear->am_time_out_start = $timePeriodsSource->am_time_out_start;
            $currentSchoolYear->am_time_out_end = $timePeriodsSource->am_time_out_end;
            $currentSchoolYear->pm_time_in_start = $timePeriodsSource->pm_time_in_start;
            $currentSchoolYear->pm_time_in_end = $timePeriodsSource->pm_time_in_end;
            $currentSchoolYear->pm_time_out_start = $timePeriodsSource->pm_time_out_start;
            $currentSchoolYear->pm_time_out_end = $timePeriodsSource->pm_time_out_end;
        } elseif ($currentSchoolYear) {
             $currentSchoolYear->am_time_in_start = $currentSchoolYear->am_time_in_start ?? '07:00:00';
            $currentSchoolYear->am_time_in_end = $currentSchoolYear->am_time_in_end ?? '07:30:00';
            $currentSchoolYear->am_time_out_start = $currentSchoolYear->am_time_out_start ?? '11:30:00';
            $currentSchoolYear->am_time_out_end = $currentSchoolYear->am_time_out_end ?? '12:00:00';
            $currentSchoolYear->pm_time_in_start = $currentSchoolYear->pm_time_in_start ?? '13:00:00';
            $currentSchoolYear->pm_time_in_end = $currentSchoolYear->pm_time_in_end ?? '13:30:00';
            $currentSchoolYear->pm_time_out_start = $currentSchoolYear->pm_time_out_start ?? '16:30:00';
            $currentSchoolYear->pm_time_out_end = $currentSchoolYear->pm_time_out_end ?? '17:00:00';
        }

        
        $todaySession = null;
        if ($currentSchoolYear) {
            $today = Carbon::today('Asia/Manila');
            $todaySession = \App\Models\AttendanceSession::where('teacher_id', Auth::id())
                ->whereDate('started_at', $today)
                ->where('status', 'active')
                ->with('schoolYear')
                ->first();
        }

        // --- Add chart objects for statistics include ---
        $analytics = app(\App\Http\Controllers\AttendanceAnalyticsController::class);
        $chartRequest = new \Illuminate\Http\Request();
        $chartRequest->replace($request->all());
        $chartData = $analytics->getChartData($chartRequest);

        // Section-based analytics
        $sectionAnalytics = [];
        if ($currentSchoolYear) {
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
        // Today's attendance rate: present today / total students * 100
        $todayAttendanceRate = $studentCount > 0 ? round(($presentCount / $studentCount) * 100, 1) . '%' : '0%';
        $attendanceRate = $todayAttendanceRate; // Renamed for clarity
        
        // Add student counts to teacher sections
        $teacherSections = $teacherSections->map(function($section) use ($selectedSchoolYear) {
            $section->students_count = $section->students()->where('school_year_id', $selectedSchoolYear)->count();
            $section->present_today = $section->students()
                ->where('school_year_id', $selectedSchoolYear)
                ->whereHas('attendances', function($query) {
                    $query->whereDate('date', now()->toDateString());
                })
                ->count();
            return $section;
        });

        return view('teacher.dashboard', array_merge(compact(
            'schoolYears',
            'selectedSchoolYear',
            'currentSchoolYear',
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
        $selectedSchoolYear = $this->getCurrentSchoolYearId();
        
        $students = Student::where('user_id', Auth::id())
            ->where('school_year_id', $selectedSchoolYear)
            ->orderBy('id_no')
            ->get();
        
        return view('teacher.students', compact('students'));
    }

    public function message()
    {
        $selectedSchoolYear = $this->getCurrentSchoolYearId();
        
        $students = Student::where('user_id', Auth::id())
            ->where('school_year_id', $selectedSchoolYear)
            ->orderBy('name')
            ->get();
        
        return view('teacher.message', compact('students'));
    }

    
    public function semesters()
    {
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        
        $sections = \App\Models\Section::where('teacher_id', Auth::id())
            ->with(['schoolYear'])
            ->orderBy('gradelevel')
            ->orderBy('name')
            ->get();
            
        return view('teacher.school-year', compact('schoolYears', 'sections'));
    }

    
    public function updateSemesterStatus(Request $request)
    {
        Log::info('Semester update request', [
            'teacher_id' => Auth::id(),
            'school_year_id' => $request->school_year_id,
            'new_status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        try {
            $request->validate([
                'school_year_id' => 'required|exists:semesters,id',
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
                'school_year_id' => $request->school_year_id,
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

        $schoolYear = SchoolYear::findOrFail($request->school_year_id);
        
        $updateData = $request->only([
            'status', 'start_date', 'end_date',
            'am_time_in_start', 'am_time_in_end',
            'pm_time_out_start', 'pm_time_out_end'
        ]);
        
        
        $updateData['am_time_out_start'] = null;
        $updateData['am_time_out_end'] = null;
        $updateData['pm_time_in_start'] = null;
        $updateData['pm_time_in_end'] = null;
        
        $schoolYear->update($updateData);

        return back()->with('success', 'Semester updated successfully!');
    }

    
    public function editSemester($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        
        
        $studentCount = Student::where('school_year_id', $schoolYear->id)->count();
        $attendanceCount = Attendance::where('school_year_id', $schoolYear->id)->count();
        
        $responseData = [
            'id' => $schoolYear->id,
            'name' => $schoolYear->name,
            'status' => $schoolYear->status,
            'start_date' => $schoolYear->start_date,
            'end_date' => $schoolYear->end_date,
            'am_time_in_start_input' => $schoolYear->am_time_in_start_input,
            'am_time_in_end_input' => $schoolYear->am_time_in_end_input,
            'pm_time_out_start_input' => $schoolYear->pm_time_out_start_input,
            'pm_time_out_end_input' => $schoolYear->pm_time_out_end_input,
            'student_count' => $studentCount,
            'attendance_count' => $attendanceCount,
        ];
        
        return response()->json($responseData);
    }

    
    public function getSemesterData($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        return response()->json([
            'success' => true,
            'semester' => [
                'id' => $schoolYear->id,
                'name' => $schoolYear->name,
                'start_date' => $schoolYear->start_date,
                'end_date' => $schoolYear->end_date,
            ]
        ]);
    }

    public function account()
    {
        $teacher = Auth::user()->load(['section', 'sections']);
        return view('teacher.manageaccount', compact('teacher'));
    }

    public function update(Request $request)
    {
        $teacher = Auth::user();
        
        $validated = $this->validateForResponse($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->id,
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $teacher->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'position' => $request->position,
        ]);

        return redirect()->route('teacher.account')->with('success', 'Account updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $teacher = Auth::user();
        
        $validated = $this->validateForResponse($request, [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

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
