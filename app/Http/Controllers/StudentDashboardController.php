<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        \Log::info('=== StudentDashboardController::dashboard() CALLED ===', [
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'session_data' => $request->session()->all()
        ]);

        $student = Auth::user();

        \Log::info('Student loaded', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_class' => class_basename(get_class($student))
        ]);

         $school = $student->school;

         $section = $student->section;

           $today = Carbon::today();
        $todayAttendance = $student->attendances()
            ->where('date', $today)
            ->first();

        \Log::info('Dashboard data loaded successfully', [
            'has_school' => !is_null($school),
            'has_section' => !is_null($section),
            'has_today_attendance' => !is_null($todayAttendance)
        ]);

        return view('student.dashboard', [
            'student' => $student,
            'school' => $school,
            'section' => $section,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    public function attendance(Request $request)
    {
        $student = Auth::user();

        $query = $student->attendances()
            ->orderBy('date', 'desc');

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $attendances = $query->paginate(15);

        return view('student.attendance', [
            'student' => $student,
            'attendances' => $attendances,
        ]);
    }

    public function account()
    {
        $student = Auth::user();
        return view('student.account', ['student' => $student]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $student = Auth::user();

        if (!\Hash::check($request->current_password, $student->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $student->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}