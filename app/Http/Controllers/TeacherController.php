<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Attendance;
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
            $studentAttendanceCounts = Attendance::where('semester_id', $selectedSemester)
                ->whereIn('student_id', $students->pluck('id'))
                ->selectRaw('student_id, COUNT(*) as attendance_count')
                ->groupBy('student_id')
                ->pluck('attendance_count', 'student_id');

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
            'todaySession'
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
        return view('teacher.semester', compact('semesters'));
    }

    /*
    public function addSemester(Request $request)
    {
        Log::info('Semester creation request', [
            'teacher_id' => Auth::id(),
            'semester_name' => $request->name,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ]);
        } catch (\Exception $e) {
            Log::warning('Semester validation failed', [
                'teacher_id' => Auth::id(),
                'semester_name' => $request->name,
                'validation_errors' => $e->getMessage(),
            ]);
            throw $e;
        }

        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $weekdays = 0;
        
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) { 
                $weekdays++;
            }
            $current->addDay();
        }

        
        if ($weekdays < 5) {
            return back()->withErrors([
                'end_date' => "Semester must have at least 1 week (5 weekdays). Currently has {$weekdays} weekdays."
            ])->withInput();
        }

        
        $warningMessage = '';
        if ($weekdays < 400) {
            $weeks = round($weekdays / 5, 1);
            $warningMessage = " Note: Current semester is {$weeks} weeks. Recommended duration is at least 80 weeks for a full academic semester.";
        }

        
        if ($request->status === 'active') {
            
            $activeSemester = Semester::where('school_id', auth()->user()->school_id)
                ->where('status', 'active')
                ->first();

            if ($activeSemester) {
                return back()->withErrors([
                    'status' => "Cannot create active semester. '{$activeSemester->name}' is already active. Please deactivate it first or set this semester as inactive."
                ])->withInput();
            }
        }
        
        
        $overlapping = Semester::where('school_id', auth()->user()->school_id)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })
            ->first();

        if ($overlapping) {
            return back()->withErrors([
                'start_date' => "Date range conflicts with existing semester '{$overlapping->name}' ({$overlapping->start_date} to {$overlapping->end_date})."
            ])->withInput();
        }
        
        
        if ($request->am_time_in_start >= $request->am_time_in_end) {
            return back()->withErrors(['am_time_in_end' => 'AM time in end must be after start time.'])->withInput();
        }
        
        if ($request->pm_time_out_start >= $request->pm_time_out_end) {
            return back()->withErrors(['pm_time_out_end' => 'Afternoon time out end must be after start time.'])->withInput();
        }
        
        $semester = new Semester([
            'name' => $request->name,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'school_id' => auth()->user()->school_id,
            'am_time_in_start' => Carbon::createFromFormat('H:i', $request->am_time_in_start)->format('H:i:s'),
            'am_time_in_end' => Carbon::createFromFormat('H:i', $request->am_time_in_end)->format('H:i:s'),
            'pm_time_out_start' => Carbon::createFromFormat('H:i', $request->pm_time_out_start)->format('H:i:s'),
            'pm_time_out_end' => Carbon::createFromFormat('H:i', $request->pm_time_out_end)->format('H:i:s'),
        ]);
        
        $semester->save();
        
        $successMessage = "Semester created successfully with {$weekdays} weekdays!";
        if (!empty($warningMessage)) {
            $successMessage .= $warningMessage;
        }
        
        return redirect()->route('teacher.semesters')->with('success', $successMessage);
    } 
    */

    
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
