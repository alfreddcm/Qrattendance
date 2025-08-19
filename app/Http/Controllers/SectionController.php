<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    /**
     * Display a listing of sections for the current school.
     */
    public function index()
    {
        try {
            $user = auth()->user();
            
            // Get sections for the current user's school with relationships (through semester)
            $sections = Section::whereHas('semester', function ($query) use ($user) {
                    $query->where('school_id', $user->school_id);
                })
                ->with(['teacher:id,name', 'semester:id,name'])
                ->withCount('students')
                ->orderBy('gradelevel')
                ->orderBy('name')
                ->get([
                    'id', 'gradelevel', 'name', 'teacher_id', 'semester_id',
                    'am_time_in_start', 'am_time_in_end', 'am_time_out_start', 'am_time_out_end',
                    'pm_time_in_start', 'pm_time_in_end', 'pm_time_out_start', 'pm_time_out_end'
                ]);

            return response()->json([
                'success' => true,
                'sections' => $sections,
                'message' => 'Sections retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created section in storage.
     */
    public function store(Request $request)
    {
        Log::info('Creating new section', ['user_id' => Auth::id()]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'gradelevel' => 'required|integer|min:1|max:12',
            'semester_id' => 'nullable|exists:semesters,id',
            'teacher_id' => 'nullable|exists:users,id',
            'am_time_in_start' => 'nullable|date_format:H:i',
            'am_time_in_end' => 'nullable|date_format:H:i|after:am_time_in_start',
            'am_time_out_start' => 'nullable|date_format:H:i',
            'am_time_out_end' => 'nullable|date_format:H:i|after:am_time_out_start',
            'pm_time_in_start' => 'nullable|date_format:H:i',
            'pm_time_in_end' => 'nullable|date_format:H:i|after:pm_time_in_start',
            'pm_time_out_start' => 'nullable|date_format:H:i',
            'pm_time_out_end' => 'nullable|date_format:H:i|after:pm_time_out_start',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if section name already exists for the same semester and grade
            $existingSection = Section::where('name', $request->name)
                ->where('gradelevel', $request->gradelevel)
                ->where('semester_id', $request->semester_id)
                ->first();

            if ($existingSection) {
                return response()->json([
                    'success' => false,
                    'message' => 'A section with this name and grade level already exists for the selected semester.'
                ], 409);
            }

            // Verify teacher exists and has teacher role (if teacher_id is provided)
            if ($request->teacher_id) {
                // If authenticated user is a teacher, they can only assign themselves
                if (auth()->user()->role === 'teacher') {
                    if ($request->teacher_id != auth()->id()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Teachers can only assign themselves to sections.'
                        ], 403);
                    }
                    $teacher_id = auth()->id();
                } else {
                    // If admin, accept the requested teacher_id
                    $teacher = User::where('id', $request->teacher_id)
                        ->where('role', 'teacher')
                        ->first();

                    if (!$teacher) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Selected teacher is not valid.'
                        ], 400);
                    }
                    $teacher_id = $request->teacher_id;
                }
            } else {
                $teacher_id = null;
            }

            $section = Section::create([
                'name' => $request->name,
                'gradelevel' => $request->gradelevel,
                'semester_id' => $request->semester_id,
                'teacher_id' => $teacher_id,
                'am_time_in_start' => $request->am_time_in_start,
                'am_time_in_end' => $request->am_time_in_end,
                'am_time_out_start' => $request->am_time_out_start,
                'am_time_out_end' => $request->am_time_out_end,
                'pm_time_in_start' => $request->pm_time_in_start,
                'pm_time_in_end' => $request->pm_time_in_end,
                'pm_time_out_start' => $request->pm_time_out_start,
                'pm_time_out_end' => $request->pm_time_out_end,
            ]);

            $section->load(['teacher', 'semester', 'students']);

            Log::info('Section created successfully', [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'grade_level' => $section->gradelevel,
                'teacher_id' => $section->teacher_id,
                'semester_id' => $section->semester_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Section created successfully!',
                'section' => $section
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating section', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating section: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified section.
     */
    public function edit(Section $section)
    {
        try {
            $section->load(['teacher', 'semester']);
            
            Log::info('Fetching section for edit', [
                'section_id' => $section->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'id' => $section->id,
                'name' => $section->name,
                'gradelevel' => $section->gradelevel,
                'teacher_id' => $section->teacher_id ,
                'semester_id' => $section->semester_id,
                'am_time_in_start' => $section->am_time_in_start,
                'am_time_in_end' => $section->am_time_in_end,
                'am_time_out_start' => $section->am_time_out_start,
                'am_time_out_end' => $section->am_time_out_end,
                'pm_time_in_start' => $section->pm_time_in_start,
                'pm_time_in_end' => $section->pm_time_in_end,
                'pm_time_out_start' => $section->pm_time_out_start,
                'pm_time_out_end' => $section->pm_time_out_end,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching section for edit', [
                'section_id' => $section->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching section data.'
            ], 500);
        }
    }

    /**
     * Update the specified section in storage.
     */
    public function update(Request $request, Section $section)
    {
        Log::info('Updating section', [
            'section_id' => $section->id,
            'user_id' => Auth::id()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'gradelevel' => 'required|integer|min:1|max:12',
            'semester_id' => 'required|exists:semesters,id',
            'teacher_id' => 'required|exists:users,id',
            'am_time_in_start' => 'required|date_format:H:i',
            'am_time_in_end' => 'required|date_format:H:i|after:am_time_in_start',
            'am_time_out_start' => 'required|date_format:H:i|after:am_time_in_end',
            'am_time_out_end' => 'required|date_format:H:i|after:am_time_out_start',
            'pm_time_in_start' => 'required|date_format:H:i|after:am_time_out_end',
            'pm_time_in_end' => 'required|date_format:H:i|after:pm_time_in_start',
            'pm_time_out_start' => 'required|date_format:H:i|after:pm_time_in_end',
            'pm_time_out_end' => 'required|date_format:H:i|after:pm_time_out_start',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
             $existingSection = Section::where('name', $request->name)
                ->where('gradelevel', $request->gradelevel)
                ->where('semester_id', $request->semester_id)
                ->where('id', '!=', $section->id)
                ->first();

            if ($existingSection) {
                return response()->json([
                    'success' => false,
                    'message' => 'A section with this name and grade level already exists for the selected semester.'
                ], 409);
            }

            // Verify teacher exists and has teacher role
            $teacher = User::where('id', $request->teacher_id)
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected teacher is not valid.'
                ], 400);
            }

            $section->update([
                'name' => $request->name,
                'gradelevel' => $request->gradelevel,
                'semester_id' => $request->semester_id,
                'teacher_id' => $request->teacher_id,
                'am_time_in_start' => $request->am_time_in_start,
                'am_time_in_end' => $request->am_time_in_end,
                'am_time_out_start' => $request->am_time_out_start,
                'am_time_out_end' => $request->am_time_out_end,
                'pm_time_in_start' => $request->pm_time_in_start,
                'pm_time_in_end' => $request->pm_time_in_end,
                'pm_time_out_start' => $request->pm_time_out_start,
                'pm_time_out_end' => $request->pm_time_out_end,
            ]);

            $section->load(['teacher', 'semester', 'students']);

            Log::info('Section updated successfully', [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'grade_level' => $section->gradelevel,
                'teacher_id' => $section->teacher_id,
                'semester_id' => $section->semester_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully!',
                'section' => $section
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating section', [
                'section_id' => $section->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating section: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified section from storage.
     */
    public function destroy(Section $section)
    {
        Log::info('Deleting section', [
            'section_id' => $section->id,
            'section_name' => $section->name,
            'user_id' => Auth::id()
        ]);

        try {
            // Check if section has students
            $studentCount = $section->students()->count();
            
            if ($studentCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete section. It has {$studentCount} student(s) assigned to it."
                ], 409);
            }

            $sectionName = $section->name;
            $section->delete();

            Log::info('Section deleted successfully', [
                'section_name' => $sectionName,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Section deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting section', [
                'section_id' => $section->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting section: ' . $e->getMessage()
            ], 500);
        }
    }
}
