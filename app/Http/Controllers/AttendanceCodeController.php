<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCode;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceCodeController extends Controller
{
    /**
     * Generate a new attendance code for the teacher
     */
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'section_id' => 'nullable|exists:sections,id',
                'duration' => 'nullable|integer|min:5|max:120',  
            ]);

            $teacher = Auth::user();
            $sectionId = $validated['section_id'] ?? null;
            $duration = $validated['duration'] ?? 15;  

             if ($sectionId) {
                $section = Section::where('id', $sectionId)
                    ->where('teacher_id', $teacher->id)
                    ->first();

                if (!$section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this section.'
                    ], 403);
                }
            }

             $attendanceCode = AttendanceCode::createForTeacher(
                $teacher->id,
                $sectionId,
                $duration
            );

            Log::info('Attendance code generated', [
                'teacher_id' => $teacher->id,
                'code' => $attendanceCode->code,
                'section_id' => $sectionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance code generated successfully!',
                'data' => [
                    'code' => $attendanceCode->code,
                    'qr_code_url' => $attendanceCode->qr_code_url,
                    'access_url' => route('public.attendance.show', ['code' => $attendanceCode->code]),
                    'id' => $attendanceCode->uuid
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating attendance code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate code: ' . $e->getMessage()
            ], 500);
        }
    }

 
    public function getActive(Request $request)
    {
        try {
            $teacher = Auth::user();
            $sectionId = $request->input('section_id');

            $activeCode = AttendanceCode::getActiveCodeForTeacher($teacher->id, $sectionId);

            if (!$activeCode) {
                return response()->json([
                    'success' => true,
                    'has_active_code' => false,
                    'message' => 'No active code found.'
                ]);
            }

            return response()->json([
                'success' => true,
                'has_active_code' => true,
                'data' => [
                    'id' => $activeCode->uuid,
                    'code' => $activeCode->code,
                    'qr_code_url' => $activeCode->qr_code_url,
                    'access_url' => route('public.attendance.show', ['code' => $activeCode->code]),
                    'section_id' => $activeCode->section_id,
                    'section_name' => $activeCode->section ? $activeCode->section->name : 'All Sections'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching active code', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active code.'
            ], 500);
        }
    }

 
    public function deactivate(Request $request, AttendanceCode $attendanceCode)
    {
        try {
            $teacher = Auth::user();
            $this->authorize('view', $attendanceCode);
            
            if ($attendanceCode->teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code not found or unauthorized.'
                ], 404);
            }

            $attendanceCode->deactivate();

            Log::info('Attendance code deactivated', [
                'teacher_id' => $teacher->id,
                'code' => $attendanceCode->code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance code deactivated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deactivating attendance code', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate code.'
            ], 500);
        }
    }

  
    public function validate(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a code.'
            ], 400);
        }

        $attendanceCode = AttendanceCode::validateCode($code);

        if (!$attendanceCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code is valid!',
            'data' => [
                'teacher_name' => $attendanceCode->teacher->name ?? 'Teacher',
                'school_name' => $attendanceCode->teacher->school->name ?? 'School',
                'section_name' => $attendanceCode->section->name ?? 'All Sections',
                'is_active' => $attendanceCode->is_active
            ]
        ]);
    }

 
    public function printCode(AttendanceCode $attendanceCode)
    {
        try {
            $teacher = Auth::user();

            $this->authorize('view', $attendanceCode);

            if ($attendanceCode->teacher_id !== $teacher->id) {
                abort(404, 'Attendance code not found or you do not have permission to view it.');
            }

            $attendanceCode->load(['teacher.school', 'section']);

            return view('attendance-code.print-single', compact('attendanceCode'));

        } catch (\Exception $e) {
            Log::error('Error printing attendance code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Failed to load attendance code for printing.');
        }
    }
}