<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SectionController extends Controller
{
    /**
     * Store a newly created section in storage.
     */
    public function store(Request $request)
    {
        Log::info('Creating new section', ['user_id' => Auth::id()]);

         $data = $request->all();
        $timeFields = ['am_time_in_start', 'am_time_in_end', 'am_time_out_start', 'am_time_out_end',
                      'pm_time_in_start', 'pm_time_in_end', 'pm_time_out_start', 'pm_time_out_end'];
        
        foreach ($timeFields as $field) {
            if (!empty($data[$field])) {
                 if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $data[$field])) {
                    $data[$field] = substr($data[$field], 0, 5); // Keep only H:i part
                }
            }
        }

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'gradelevel' => 'required|integer|min:1|max:12',
            'semester_id' => 'required|exists:semesters,id',
            'teacher_id' => 'nullable|exists:users,id',
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
            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
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

             $teacher = User::where('id', $request->teacher_id)
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected teacher is not valid.'
                ], 400);
            }

             $timeValidation = $this->validateSectionTimeSequence($request);
            if (!$timeValidation['valid']) {
                if ($request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $timeValidation['message']
                    ], 400);
                }
                return redirect()->back()->with('error', $timeValidation['message']);
            }

            $validatedData = $validator->validated();
            
            $section = Section::create([
                'name' => $validatedData['name'],
                'gradelevel' => $validatedData['gradelevel'],
                'semester_id' => $validatedData['semester_id'],
                'teacher_id' => $validatedData['teacher_id'],
                'am_time_in_start' => $validatedData['am_time_in_start'],
                'am_time_in_end' => $validatedData['am_time_in_end'],
                'am_time_out_start' => $validatedData['am_time_out_start'],
                'am_time_out_end' => $validatedData['am_time_out_end'],
                'pm_time_in_start' => $validatedData['pm_time_in_start'],
                'pm_time_in_end' => $validatedData['pm_time_in_end'],
                'pm_time_out_start' => $validatedData['pm_time_out_start'],
                'pm_time_out_end' => $validatedData['pm_time_out_end'],
            ]);

             try {
                if (method_exists($section, 'teachers')) {
                    if (!$section->teachers()->where('teacher_id', $validatedData['teacher_id'])->exists()) {
                        $section->teachers()->attach($validatedData['teacher_id']);
                    }
                }
                 $newTeacher = User::find($validatedData['teacher_id']);
                if ($newTeacher && $newTeacher->role === 'teacher') {
                    $newTeacher->update(['section_id' => $section->id]);
                }
            } catch (\Exception $pivotEx) {
                Log::warning('Non-fatal: could not sync pivot/teacher reference on section store', [
                    'error' => $pivotEx->getMessage()
                ]);
            }

            $section->load(['teacher', 'semester', 'students']);

            Log::info('Section created successfully', [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'grade_level' => $section->gradelevel,
                'teacher_id' => $section->teacher_id,
                'semester_id' => $section->semester_id
            ]);

            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Section created successfully!',
                    'section' => $section
                ]);
            }
            return redirect()->back()->with('success', 'Section created successfully!');

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
                'teacher_id' => $section->teacher_id,
                'semester_id' => $section->semester_id,
                'am_time_in_start' => $section->am_time_in_start,
                'am_time_in_end' => $section->am_time_in_end,
                'am_time_out_start' => $section->am_time_out_start,
                'am_time_out_end' => $section->am_time_out_end,
                'pm_time_in_start' => $section->pm_time_in_start,
                'pm_time_in_end' => $section->pm_time_in_end,
                'pm_time_out_start' => $section->pm_time_out_start,
                'pm_time_out_end' => $section->pm_time_out_end,
                 'semester_defaults' => [
                    'morning_start' => $section->semester->morning_period_start,
                    'morning_end' => $section->semester->morning_period_end,
                    'afternoon_start' => $section->semester->afternoon_period_start,
                    'afternoon_end' => $section->semester->afternoon_period_end,
                ]
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

         $data = $request->all();
        $timeFields = ['am_time_in_start', 'am_time_in_end', 'am_time_out_start', 'am_time_out_end',
                      'pm_time_in_start', 'pm_time_in_end', 'pm_time_out_start', 'pm_time_out_end'];
        
        foreach ($timeFields as $field) {
            if (!empty($data[$field])) {
                 if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $data[$field])) {
                    $data[$field] = substr($data[$field], 0, 5);  
                }
            }
        }

        $validator = Validator::make($data, [
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
            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
             $validatedData = $validator->validated();
            
            $existingSection = Section::where('name', $validatedData['name'])
                ->where('gradelevel', $validatedData['gradelevel'])
                ->where('semester_id', $validatedData['semester_id'])
                ->where('id', '!=', $section->id)
                ->first();

            if ($existingSection) {
                if ($request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A section with this name and grade level already exists for the selected semester.'
                    ], 409);
                }
                return redirect()->back()->with('error', 'A section with this name and grade level already exists for the selected semester.');
            }

             $teacher = User::where('id', $validatedData['teacher_id'])
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                if ($request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected teacher is not valid.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Selected teacher is not valid.');
            }

             $timeValidation = $this->validateSectionTimeSequence((object) $data);
            if (!$timeValidation['valid']) {
                if ($request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $timeValidation['message']
                    ], 400);
                }
                return redirect()->back()->with('error', $timeValidation['message']);
            }

             $oldTeacherId = $section->teacher_id;
            $newTeacherId = $validatedData['teacher_id'];

            $section->update([
                'name' => $validatedData['name'],
                'gradelevel' => $validatedData['gradelevel'],
                'semester_id' => $validatedData['semester_id'],
                'teacher_id' => $validatedData['teacher_id'],
                'am_time_in_start' => $validatedData['am_time_in_start'],
                'am_time_in_end' => $validatedData['am_time_in_end'],
                'am_time_out_start' => $validatedData['am_time_out_start'],
                'am_time_out_end' => $validatedData['am_time_out_end'],
                'pm_time_in_start' => $validatedData['pm_time_in_start'],
                'pm_time_in_end' => $validatedData['pm_time_in_end'],
                'pm_time_out_start' => $validatedData['pm_time_out_start'],
                'pm_time_out_end' => $validatedData['pm_time_out_end'],
            ]);

             if ($oldTeacherId !== $newTeacherId) {
                $this->updateTeacherAssignments($section, $oldTeacherId, $newTeacherId);
            }

            $section->load(['teacher', 'semester', 'students']);

            Log::info('Section updated successfully', [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'grade_level' => $section->gradelevel,
                'old_teacher_id' => $oldTeacherId,
                'new_teacher_id' => $newTeacherId,
                'semester_id' => $section->semester_id
            ]);

            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Section updated successfully!'
                ]);
            }
            return redirect()->back()->with('success', 'Section updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating section', [
                'section_id' => $section->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating section: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating section: ' . $e->getMessage());
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
             $studentCount = $section->students()->count();
            
            if ($studentCount > 0) {
                if ($request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete section. It has {$studentCount} student(s) assigned to it."
                    ], 409);
                }
                return redirect()->back()->with('error', "Cannot delete section. It has {$studentCount} student(s) assigned to it.");
            }

            $sectionName = $section->name;
            $section->delete();

            Log::info('Section deleted successfully', [
                'section_name' => $sectionName,
                'user_id' => Auth::id()
            ]);

            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Section deleted successfully!'
                ]);
            }
            return redirect()->back()->with('success', 'Section deleted successfully!');

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

    /**
     * Validate individual section time sequence (no cross-section conflicts)
     */
    private function validateSectionTimeSequence($request)
    {
        $times = [
            'am_time_in_start' => $request->am_time_in_start,
            'am_time_in_end' => $request->am_time_in_end,
            'am_time_out_start' => $request->am_time_out_start,
            'am_time_out_end' => $request->am_time_out_end,
            'pm_time_in_start' => $request->pm_time_in_start,
            'pm_time_in_end' => $request->pm_time_in_end,
            'pm_time_out_start' => $request->pm_time_out_start,
            'pm_time_out_end' => $request->pm_time_out_end,
        ];

         $timeToMinutes = function($time) {
            if (!$time) return 0;
            list($hours, $minutes) = explode(':', $time);
            return ($hours * 60) + $minutes;
        };

         if ($times['am_time_in_start'] && $times['am_time_in_end']) {
            if ($timeToMinutes($times['am_time_in_start']) >= $timeToMinutes($times['am_time_in_end'])) {
                return ['valid' => false, 'message' => 'AM Time-In Start must be before AM Time-In End'];
            }
        }

        if ($times['am_time_out_start'] && $times['am_time_out_end']) {
            if ($timeToMinutes($times['am_time_out_start']) >= $timeToMinutes($times['am_time_out_end'])) {
                return ['valid' => false, 'message' => 'AM Time-Out Start must be before AM Time-Out End'];
            }
        }

         if ($times['am_time_in_end'] && $times['am_time_out_start']) {
            if ($timeToMinutes($times['am_time_in_end']) >= $timeToMinutes($times['am_time_out_start'])) {
                return ['valid' => false, 'message' => 'AM Time-In End must be before AM Time-Out Start'];
            }
        }

         if ($times['pm_time_in_start'] && $times['pm_time_in_end']) {
            if ($timeToMinutes($times['pm_time_in_start']) >= $timeToMinutes($times['pm_time_in_end'])) {
                return ['valid' => false, 'message' => 'PM Time-In Start must be before PM Time-In End'];
            }
        }

        if ($times['pm_time_out_start'] && $times['pm_time_out_end']) {
            if ($timeToMinutes($times['pm_time_out_start']) >= $timeToMinutes($times['pm_time_out_end'])) {
                return ['valid' => false, 'message' => 'PM Time-Out Start must be before PM Time-Out End'];
            }
        }

         if ($times['pm_time_in_end'] && $times['pm_time_out_start']) {
            if ($timeToMinutes($times['pm_time_in_end']) >= $timeToMinutes($times['pm_time_out_start'])) {
                return ['valid' => false, 'message' => 'PM Time-In End must be before PM Time-Out Start'];
            }
        }

         if ($times['am_time_out_end'] && $times['pm_time_in_start']) {
            if ($timeToMinutes($times['am_time_out_end']) >= $timeToMinutes($times['pm_time_in_start'])) {
                return ['valid' => false, 'message' => 'AM Time-Out End must be before PM Time-In Start'];
            }
        }

        return ['valid' => true, 'message' => 'Time sequence is valid'];
    }

    /**
     * Update teacher assignments and student relationships
     */
    private function updateTeacherAssignments(Section $section, $oldTeacherId, $newTeacherId)
    {
        try {
            DB::transaction(function () use ($section, $oldTeacherId, $newTeacherId, &$studentsUpdated, &$usersUpdated) {
                 if (method_exists($section, 'teachers')) {
                    if ($oldTeacherId) {
                        $section->teachers()->detach($oldTeacherId);
                    }
                    if ($newTeacherId && !$section->teachers()->where('teacher_id', $newTeacherId)->exists()) {
                        $section->teachers()->attach($newTeacherId);
                    }
                }

                 if ($oldTeacherId) {
                    $oldTeacher = User::find($oldTeacherId);
                    if ($oldTeacher && $oldTeacher->section_id == $section->id) {
                        $oldTeacher->update(['section_id' => null]);
                    }
                }
                if ($newTeacherId) {
                    $newTeacher = User::find($newTeacherId);
                    if ($newTeacher) {
                        $newTeacher->update(['section_id' => $section->id]);
                    }
                }

                 $studentsUpdated = Student::where('section_id', $section->id)
                    ->update(['user_id' => $newTeacherId]);

                 $usersUpdated = User::where('section_id', $section->id)
                    ->where('role', 'student')
                    ->update(['section_id' => $section->id]);
            });

            Log::info('Teacher assignments updated', [
                'section_id' => $section->id,
                'old_teacher_id' => $oldTeacherId,
                'new_teacher_id' => $newTeacherId,
                'students_updated' => $studentsUpdated ?? 0,
                'users_updated' => $usersUpdated ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating teacher assignments', [
                'section_id' => $section->id,
                'error' => $e->getMessage()
            ]);
         }
    }

    /**
     * Get available teachers and semesters for dropdowns
     */
    public function getFormData()
    {
        try {
            $teachers = User::where('role', 'teacher')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            $semesters = Semester::select('id', 'name', 'status', 'morning_period_start', 'morning_period_end', 'afternoon_period_start', 'afternoon_period_end')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

             $formattedSemesters = $semesters->map(function ($semester) {
                return [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'status' => $semester->status,
                    'time_defaults' => [
                        'morning_start' => $semester->morning_period_start ? Carbon::parse($semester->morning_period_start)->format('H:i') : '07:00',
                        'morning_end' => $semester->morning_period_end ? Carbon::parse($semester->morning_period_end)->format('H:i') : '12:00',
                        'afternoon_start' => $semester->afternoon_period_start ? Carbon::parse($semester->afternoon_period_start)->format('H:i') : '13:00',
                        'afternoon_end' => $semester->afternoon_period_end ? Carbon::parse($semester->afternoon_period_end)->format('H:i') : '17:00',
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'teachers' => $teachers,
                'semesters' => $formattedSemesters,
                'grade_levels' => range(11, 12)  
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching form data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading form data'
            ], 500);
        }
    }
    
    /**
     * Get teacher's assigned sections for dropdown
     */
    public function getTeacherSections()
    {
        try {
            $teacherId = Auth::id();
            $teacher = User::with(['sections', 'section'])->find($teacherId);
            
            // Get sections from many-to-many relationship (primary)
            $sections = $teacher->sections;
            
            // Add legacy single section if exists
            if ($teacher->section && !$sections->contains('id', $teacher->section->id)) {
                $sections->push($teacher->section);
            }
            
            $formattedSections = $sections->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'gradelevel' => $section->gradelevel,
                    'student_count' => $section->students()->count()
                ];
            });
            
            return response()->json($formattedSections);
            
        } catch (\Exception $e) {
            Log::error('Error fetching teacher sections', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Could not load sections'
            ], 500);
        }
    }
}
