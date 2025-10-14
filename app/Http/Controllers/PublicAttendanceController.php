<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCode;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicAttendanceController extends Controller
{
    /**
     * Show the public attendance access page
     */
    public function index(Request $request)
    {
        $code = $request->input('code');

        // If no code provided, show code entry form
        if (!$code) {
            return view('public.attendance-login');
        }

        // Validate code
        $attendanceCode = AttendanceCode::validateCode($code);

        if (!$attendanceCode) {
            return view('public.attendance-login', [
                'error' => 'Invalid or expired code. Please check the code and try again.'
            ]);
        }

        // Get students for this section/teacher
        $studentsQuery = Student::with(['section', 'semester']);

        if ($attendanceCode->section_id) {
            // Specific section
            $studentsQuery->where('section_id', $attendanceCode->section_id);
        } else {
            // All sections for this teacher
            $studentsQuery->whereHas('section', function($query) use ($attendanceCode) {
                $query->where('teacher_id', $attendanceCode->teacher_id);
            });
        }

        $students = $studentsQuery->orderBy('name')->get();

        Log::info('Public attendance accessed', [
            'code' => $code,
            'teacher_id' => $attendanceCode->teacher_id,
            'section_id' => $attendanceCode->section_id,
            'students_count' => $students->count()
        ]);

        return view('public.attendance-view', [
            'attendanceCode' => $attendanceCode,
            'students' => $students,
            'code' => $code
        ]);
    }

    /**
     * Record attendance via public page
     */
    public function record(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|size:6',
                'student_id' => 'required|exists:students,id',
                'status' => 'required|in:present,absent,late'
            ]);

            // Validate code
            $attendanceCode = AttendanceCode::validateCode($validated['code']);

            if (!$attendanceCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired code.'
                ], 403);
            }

            // Verify student belongs to the correct section
            $student = Student::find($validated['student_id']);
            
            if ($attendanceCode->section_id && $student->section_id != $attendanceCode->section_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student does not belong to this section.'
                ], 403);
            }

            // Record attendance (you'll need to implement this based on your existing attendance system)
            // For now, this is a placeholder
            
            Log::info('Attendance recorded via public page', [
                'code' => $validated['code'],
                'student_id' => $validated['student_id'],
                'status' => $validated['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error recording public attendance', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance.'
            ], 500);
        }
    }
}
