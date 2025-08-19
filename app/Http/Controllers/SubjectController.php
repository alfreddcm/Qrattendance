<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index()
    {
        try {
            $subjects = Subject::with(['teacher', 'semester', 'sections'])
                ->where('teacher_id', Auth::id())
                ->get();

            return response()->json([
                'success' => true,
                'subjects' => $subjects
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load subjects: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string|max:500',
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            $subject = Subject::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'teacher_id' => Auth::id(),
                'semester_id' => $request->semester_id
            ]);

            $subject->load(['teacher', 'semester', 'sections']);

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully',
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subject: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $subject = Subject::with(['teacher', 'semester', 'sections'])
                ->where('teacher_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found or access denied'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $id,
            'description' => 'nullable|string|max:500',
            'semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            $subject = Subject::where('teacher_id', Auth::id())->findOrFail($id);

            $subject->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'semester_id' => $request->semester_id
            ]);

            $subject->load(['teacher', 'semester', 'sections']);

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully',
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $subject = Subject::where('teacher_id', Auth::id())->findOrFail($id);

            // Check if subject has attendance records
            $attendanceCount = $subject->attendances()->count();
            if ($attendanceCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete subject. It has {$attendanceCount} attendance record(s)."
                ], 422);
            }

            // Detach all sections
            $subject->sections()->detach();
            
            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignSection(Request $request, $subjectId)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'schedule_day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100'
        ]);

        try {
            $subject = Subject::where('teacher_id', Auth::id())->findOrFail($subjectId);
            
            // Check if section is already assigned to this subject
            if ($subject->sections()->where('section_id', $request->section_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section is already assigned to this subject'
                ], 422);
            }

            $subject->sections()->attach($request->section_id, [
                'schedule_day' => $request->schedule_day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'room' => $request->room
            ]);

            $subject->load(['teacher', 'semester', 'sections']);

            return response()->json([
                'success' => true,
                'message' => 'Section assigned successfully',
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign section: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSectionSchedule(Request $request, $subjectId, $sectionId)
    {
        $request->validate([
            'schedule_day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100'
        ]);

        try {
            $subject = Subject::where('teacher_id', Auth::id())->findOrFail($subjectId);
            
            $subject->sections()->updateExistingPivot($sectionId, [
                'schedule_day' => $request->schedule_day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'room' => $request->room
            ]);

            $subject->load(['teacher', 'semester', 'sections']);

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'subject' => $subject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detachSection($subjectId, $sectionId)
    {
        try {
            $subject = Subject::where('teacher_id', Auth::id())->findOrFail($subjectId);
            
            $subject->sections()->detach($sectionId);

            return response()->json([
                'success' => true,
                'message' => 'Section removed from subject successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove section: ' . $e->getMessage()
            ], 500);
        }
    }
}
