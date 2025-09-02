<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ValidatesForResponse;
use Carbon\Carbon;

class AdminController extends Controller
{
    use ValidatesForResponse;
    
    public function dashboard()
    {
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalStudents = Student::count();
        $totalSchools = School::count();
        
        $totalSections = Section::count();
        
        // Count active sessions created by teachers today
        $todaySessionCount = AttendanceSession::whereDate('created_at', today())
            ->where('status', 'active')
            ->whereHas('teacher', function($query) {
                $query->where('role', 'teacher');
            })
            ->count();
        
        $totalAttendanceRecords = Attendance::count();
        
        $recentAttendance = Attendance::with(['student.user.school', 'student.section'])
            ->whereDate('date', today())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        $schools = School::orderBy('name')->get();
        
        return view('admin.dashboard', compact(
            'totalTeachers',
            'totalStudents', 
            'totalSchools',
            'totalSections',
            'todaySessionCount',
            'totalAttendanceRecords',
            'recentAttendance',
            'schools'
        ));
    }

    private function validateOrRespond(Request $request, array $rules)
    {
        return $this->validateForResponse($request, $rules);
    }

 
    public function getDashboardStats()
    {
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalStudents = Student::count();
        $totalSections = Section::count();
        $attendanceToday = Attendance::whereDate('date', today())->distinct('student_id')->count();

        return response()->json([
            'success' => true,
            'totalTeachers' => $totalTeachers,
            'totalStudents' => $totalStudents,
            'totalSections' => $totalSections,
            'attendanceToday' => $attendanceToday
        ]);
    }

  
    public function checkDatabaseStatus()
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'online', 'message' => 'Database connection successful']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'offline', 'message' => 'Database connection failed'], 500);
        }
    }

    public function checkSmsStatus()
    {
        try {
            $isConfigured = config('sms.api_key') && config('sms.sender_id');
            if ($isConfigured) {
                return response()->json(['status' => 'online', 'message' => 'SMS gateway configured']);
            } else {
                return response()->json(['status' => 'offline', 'message' => 'SMS gateway not configured'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'offline', 'message' => 'SMS gateway error'], 500);
        }
    }

    public function checkStorageStatus()
    {
        try {
            $storagePath = storage_path('app');
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            
            if ($freeBytes !== false && $totalBytes !== false) {
                $usagePercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
                return response()->json([
                    'status' => 'online',
                    'message' => 'Storage accessible',
                    'usage' => $usagePercent . '% used'
                ]);
            } else {
                return response()->json(['status' => 'offline', 'message' => 'Storage access failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'offline', 'message' => 'Storage error'], 500);
        }
    }

    /**
     * Get recent attendance for dashboard
     */
    public function getRecentAttendance()
    {
        try {
            $recentAttendance = Attendance::with(['student.user.school', 'student.section'])
                ->whereDate('date', today())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'attendance' => $recentAttendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent attendance'
            ], 500);
        }
    }

    /**
     * Show manage schools page
     */
    public function manageSchools()
    {
        $schools = School::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.manage-schools', compact('schools'));
    }

    public function addSchoolForm()
    {
        return view('admin.add-school');
    }

    public function storeSchool(Request $request)
    {
        $validated = $this->validateForResponse($request, [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'required|string|unique:schools,school_id|max:20'
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('school_logos', 'public');
        }

        School::create([
            'school_id' => $request->school_id,
            'name' => $request->name,
            'address' => $request->address,
            'logo' => $logoPath
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'School added successfully!');
    }

    /**
     * Show edit school form
     */
    public function editSchoolForm($id)
    {
        $school = School::findOrFail($id);
        return view('admin.edit-school', compact('school'));
    }

    /**
     * Update school
     */
    public function updateSchool(Request $request, $id)
    {
        $school = School::findOrFail($id);
        
        $validated = $this->validateForResponse($request, [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'required|string|max:20|unique:schools,school_id,' . $school->id
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $logoPath = $school->logo;
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $logoPath = $request->file('logo')->store('school_logos', 'public');
        }

        $school->update([
            'school_id' => $request->school_id,
            'name' => $request->name,
            'address' => $request->address,
            'logo' => $logoPath
        ]);

        return redirect()->route('admin.edit-school', $school->id)->with('success', 'School updated successfully!');
    }

    /**
     * Delete school and cascade delete all related data
     */
    public function deleteSchool($id)
    {
        $school = School::findOrFail($id);
        
        // Get all teachers in this school
        $teachers = User::where('role', 'teacher')
                        ->where('school_id', $school->school_id)
                        ->get();
        
        foreach ($teachers as $teacher) {
            // Get students associated with this teacher
            $students = Student::where('user_id', $teacher->id)->get();
            
            // Delete students and their files
            foreach ($students as $student) {
                // Clear QR code files
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    Storage::disk('public')->delete($student->qr_code);
                }
                
                // Clear picture
                if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                    Storage::disk('public')->delete('student_pictures/' . $student->picture);
                }
                
                // Delete student record
                $student->delete();
            }
            
            // Delete teacher
            $teacher->delete();
        }
        
        // Delete all semesters for this school
        $semesters = Semester::where('school_id', $school->school_id)->get();
        foreach ($semesters as $semester) {
            $semester->delete();
        }
        
        // Delete school logo if exists
        if ($school->logo) {
            Storage::disk('public')->delete($school->logo);
        }

        // Delete school
        $school->delete();

        return redirect()->route('admin.dashboard')->with('success', 'School and all related data deleted successfully!');
    }

    /**
     * Show manage teachers page
     */
    public function manageTeachers(Request $request)
    {
        $query = User::where('role', 'teacher')
                    ->with(['school', 'sections.students', 'section.students']);
        
        // Handle sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validate sort parameters
        $allowedSortColumns = ['name', 'phone_number', 'email', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'name';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';
        
        $query->orderBy($sortBy, $sortOrder);
        
        $teachers = $query->paginate(10);
        
        $schools = School::all();
        $sections = \App\Models\Section::with(['teacher', 'semester', 'students'])->get();
        $semesters = \App\Models\Semester::all();
        
        // Calculate statistics using both legacy and new relationships
        $teachersWithSections = User::where('role', 'teacher')
                                   ->where(function($query) {
                                       $query->whereNotNull('section_id')
                                             ->orWhereHas('sections');
                                   })->count();
        
        $teachersWithoutSections = User::where('role', 'teacher')
                                      ->whereNull('section_id')
                                      ->whereDoesntHave('sections')
                                      ->count();
        
        $sectionsWithTeachers = \App\Models\Section::where(function($query) {
                                    $query->whereNotNull('teacher_id')
                                          ->orWhereHas('teachers');
                                })->count();
        
        $sectionsWithoutTeachers = \App\Models\Section::whereNull('teacher_id')
                                                      ->whereDoesntHave('teachers')
                                                      ->count();
        
        // Calculate statistics
        $stats = [
            'total_teachers' => User::where('role', 'teacher')->count(),
            'teachers_with_sections' => $teachersWithSections,
            'teachers_without_sections' => $teachersWithoutSections,
            'total_sections' => \App\Models\Section::count(),
            'sections_with_teachers' => $sectionsWithTeachers,
            'sections_without_teachers' => $sectionsWithoutTeachers,
            'total_students_in_sections' => \App\Models\Student::whereNotNull('section_id')->count(),
        ];

        return view('admin.manage-teachers', compact('teachers', 'schools', 'sections', 'semesters', 'stats'));
    }

    /**
     * Store new teacher
     */
    public function storeTeacher(Request $request)
    {
        try {
            // Direct validation without the trait to ensure proper web form handling
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|unique:users,username|max:255',
                'password' => 'required|string|min:6',
                'phone_number' => 'nullable|string|max:20',
                'position' => 'nullable|string|max:100',
                'school_id' => 'required|exists:schools,id',
                'section_id' => 'nullable|exists:sections,id', // Single section for Add modal
                'section_ids' => 'nullable|array', // Multiple sections for future use
                'section_ids.*' => 'exists:sections,id'
            ]);

            // Handle both single and multiple section assignments
            $sectionIds = [];
            if ($request->section_id) {
                $sectionIds = [$request->section_id];
            } elseif ($request->section_ids) {
                $sectionIds = $request->section_ids;
            }

            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'teacher',
                'phone_number' => $validated['phone_number'],
                'position' => $validated['position'],
                'school_id' => $validated['school_id'],
                'section_id' => $sectionIds ? $sectionIds[0] : null // Primary section
            ]);

            // Handle section assignments
            if (!empty($sectionIds)) {
                // Check for already assigned sections
                $assignedSections = Section::whereIn('id', $sectionIds)
                    ->whereNotNull('teacher_id')
                    ->with('teacher')
                    ->get();
                
                if ($assignedSections->count() > 0) {
                    $assignedSectionNames = $assignedSections->map(function($section) {
                        return "'{$section->name}' (assigned to {$section->teacher->name})";
                    })->join(', ');
                    
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "The following sections are already assigned to other teachers: {$assignedSectionNames}");
                }
                
                foreach ($sectionIds as $sectionId) {
                    // Update section with teacher_id
                    Section::where('id', $sectionId)->update(['teacher_id' => $user->id]);
                    
                    // Update students in this section to belong to this teacher
                    Student::where('section_id', $sectionId)
                           ->update(['user_id' => $user->id]);
                    
                    // Add to pivot table for many-to-many relationship
                    $user->sections()->attach($sectionId);
                }
            }

            return redirect()->route('admin.manage-teachers')
                           ->with('success', 'Teacher "' . $user->name . '" has been added successfully with ' . count($sectionIds) . ' section(s) assigned!');
                           
        } catch (\Exception $e) {
            \Log::error('Error creating teacher: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'An error occurred while creating the teacher. Please try again.');
        }
    }

    /**
     * Update teacher with proper dual-reference handling
     */
    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::findOrFail($id);
        
        // Debug: Force to not be treated as AJAX/JSON request
        $request->headers->remove('X-Requested-With');
        $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        
        // Use standard Laravel validation instead of the trait
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'username' => 'required|string|unique:users,username,' . $teacher->id . '|max:255',
            'password' => 'nullable|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'school_id' => 'required|exists:schools,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id'
        ]);

        try {
            \DB::beginTransaction();

             $currentSectionIds = Section::where('teacher_id', $teacher->id)->pluck('id')->toArray();
            $newSectionIds = $request->section_ids ?: [];

             $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'phone_number' => $request->phone_number,
                'position' => $request->position,
                'school_id' => $request->school_id,
                'section_id' => !empty($newSectionIds) ? $newSectionIds[0] : null // Primary section
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $teacher->update($updateData);

            // Handle section reassignments
            $sectionsToRemove = array_diff($currentSectionIds, $newSectionIds);
            $sectionsToAdd = array_diff($newSectionIds, $currentSectionIds);

            // Remove teacher from unchecked sections
            foreach ($sectionsToRemove as $sectionId) {
                $section = Section::find($sectionId);
                if ($section) {
                    // Get student count for logging
                    $studentCount = Student::where('section_id', $sectionId)->count();
                    
                    // Unassign teacher from section
                    Section::where('id', $sectionId)->update(['teacher_id' => null]);
                    
                    // Set students' user_id to null (unassign them from teacher)
                    Student::where('section_id', $sectionId)->update(['user_id' => null]);
                    
                    // Remove from pivot table for many-to-many relationship
                    $teacher->sections()->detach($sectionId);
                    
                    \Log::info("Section unassigned from teacher", [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->name,
                        'section_id' => $sectionId,
                        'section_name' => $section->name,
                        'students_affected' => $studentCount
                    ]);
                }
            }

            // Add teacher to newly checked sections
            foreach ($sectionsToAdd as $sectionId) {
                $section = Section::find($sectionId);
                if ($section && $section->teacher_id && $section->teacher_id != $teacher->id) {
                    \DB::rollBack();
                    return redirect()->back()
                        ->with('error', "Section '{$section->name}' is already assigned to another teacher.")
                        ->withInput();
                }

                if ($section) {
                     $studentCount = Student::where('section_id', $sectionId)->count();
                    
                     Section::where('id', $sectionId)->update(['teacher_id' => $teacher->id]);
                    
                     Student::where('section_id', $sectionId)->update(['user_id' => $teacher->id]);
                    
                     $teacher->sections()->attach($sectionId);
                    
                    \Log::info("Section assigned to teacher", [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->name,
                        'section_id' => $sectionId,
                        'section_name' => $section->name,
                        'students_assigned' => $studentCount
                    ]);
                }
            }

            \DB::commit();

            $assignedCount = count($newSectionIds);
            $removedCount = count($sectionsToRemove);
            $addedCount = count($sectionsToAdd);
            
            $message = "Teacher '{$teacher->name}' updated successfully";
            
            if ($addedCount > 0 || $removedCount > 0) {
                $changes = [];
                if ($addedCount > 0) {
                    $changes[] = "{$addedCount} section(s) assigned";
                }
                if ($removedCount > 0) {
                    $changes[] = "{$removedCount} section(s) unassigned";
                }
                $message .= " - " . implode(', ', $changes);
            }
            
            if ($assignedCount > 0) {
                $message .= ". Total sections: {$assignedCount}";
            } else {
                $message .= ". No sections currently assigned";
            }

            // Force redirect to manage-teachers page instead of back
            return redirect()->route('admin.manage-teachers')->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error updating teacher: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred while updating the teacher. Please try again.')
                ->withInput();
        }
    }

    /**
     * Manage section assignment with dual-reference integrity
     * Handles both users.section_id and sections.teacher_id
     */
    private function manageSectionAssignment($teacher, $oldSectionId, $newSectionId)
    {
        \DB::beginTransaction();
        
        try {
            // Step 1: Handle removal from old section
            if ($oldSectionId) {
                $oldSection = \App\Models\Section::find($oldSectionId);
                if ($oldSection && $oldSection->teacher_id == $teacher->id) {
                    // Check if section can exist without teacher (it can't due to constraint)
                    // We need to either delete section or assign temporary teacher
                    $studentCount = $oldSection->students()->count();
                    
                    if ($studentCount > 0) {
                        session()->flash('warning', "Cannot remove teacher from section '{$oldSection->name}' as it has {$studentCount} students. Please transfer students first.");
                        \DB::rollBack();
                        return ['success' => false, 'error' => 'Section has students - cannot remove teacher'];
                    }
                    
                    // Remove students' teacher reference and delete section
                    \App\Models\Student::where('section_id', $oldSectionId)->update(['user_id' => null]);
                    $oldSection->delete(); // This will cascade delete due to foreign key constraint
                    
                    session()->flash('info', "Section '{$oldSection->name}' was deleted as it cannot exist without a teacher.");
                }
            }

            // Step 2: Handle assignment to new section
            if ($newSectionId) {
                $newSection = \App\Models\Section::find($newSectionId);
                if (!$newSection) {
                    \DB::rollBack();
                    return ['success' => false, 'error' => 'Section not found'];
                }

                // Check if section already has a different teacher
                if ($newSection->teacher_id != $teacher->id) {
                    $currentTeacher = User::find($newSection->teacher_id);
                    if ($currentTeacher) {
                        // Remove current teacher's section reference
                        $currentTeacher->update(['section_id' => null]);
                        session()->flash('warning', "Teacher '{$currentTeacher->name}' was removed from section '{$newSection->name}'.");
                    }
                    
                    // Update section's teacher
                    $newSection->update(['teacher_id' => $teacher->id]);
                }

                // Update all students in this section to belong to this teacher
                \App\Models\Student::where('section_id', $newSectionId)
                    ->update(['user_id' => $teacher->id]);
            }

            \DB::commit();
            return ['success' => true];
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Section assignment error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Reassign section to another teacher (with students transfer)
     * Handles proper dual-reference integrity
     */
    public function reassignSection(Request $request)
    {
        if ($resp = $this->validateOrRespond($request, [
            'section_id' => 'required|exists:sections,id',
            'new_teacher_id' => 'required|exists:users,id',
            'transfer_students' => 'boolean'
        ])) return $resp;

        \DB::beginTransaction();
        
        try {
            $section = \App\Models\Section::findOrFail($request->section_id);
            $newTeacher = User::findOrFail($request->new_teacher_id);
            $transferStudents = $request->boolean('transfer_students', true);

            // Validate new teacher role
            if ($newTeacher->role !== 'teacher') {
                \DB::rollBack();
                return back()->with('error', 'Selected user is not a teacher.');
            }

            $oldTeacher = $section->teacher;
            $studentCount = $section->students()->count();

            // Step 1: Handle new teacher's current section (if any)
            if ($newTeacher->section_id) {
                $currentSection = \App\Models\Section::find($newTeacher->section_id);
                if ($currentSection && $currentSection->id != $section->id) {
                    $currentStudentCount = $currentSection->students()->count();
                    if ($currentStudentCount > 0) {
                        \DB::rollBack();
                        return back()->with('error', "Teacher '{$newTeacher->name}' is already assigned to section '{$currentSection->name}' with {$currentStudentCount} students. Please reassign those students first.");
                    }
                    // Delete the empty section
                    $currentSection->delete();
                }
            }

            // Step 2: Remove old teacher from section
            if ($oldTeacher) {
                $oldTeacher->update(['section_id' => null]);
            }

            // Step 3: Assign new teacher to section
            $section->update(['teacher_id' => $newTeacher->id]);
            $newTeacher->update(['section_id' => $section->id]);

            // Step 4: Transfer students if requested
            if ($transferStudents && $studentCount > 0) {
                \App\Models\Student::where('section_id', $section->id)
                    ->update(['user_id' => $newTeacher->id]);

                $message = "Section '{$section->name}' successfully reassigned to {$newTeacher->name}. {$studentCount} students transferred.";
            } else {
                $message = "Section '{$section->name}' successfully reassigned to {$newTeacher->name}.";
                
                if ($studentCount > 0 && !$transferStudents) {
                    // Update students to have no teacher
                    \App\Models\Student::where('section_id', $section->id)
                        ->update(['user_id' => null]);
                    $message .= " Students remain in section but without teacher assignment.";
                }
            }

            if ($oldTeacher) {
                $message .= " Previous teacher ({$oldTeacher->name}) is now unassigned.";
            }

            \DB::commit();
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Section reassignment error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during reassignment: ' . $e->getMessage());
        }
    }

    /**
     * Create new section and assign to teacher
     */
    public function createSectionForTeacher(Request $request)
    {
        if ($resp = $this->validateOrRespond($request, [
            'teacher_id' => 'required|exists:users,id',
            'section_name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1|max:12',
            'semester_id' => 'required|exists:semesters,id'
        ])) return $resp;

        \DB::beginTransaction();
        
        try {
            $teacher = User::findOrFail($request->teacher_id);
            
            // Check if teacher already has a section
            if ($teacher->section_id) {
                \DB::rollBack();
                return back()->with('error', 'Teacher already has an assigned section.');
            }

            // Create new section
            $section = \App\Models\Section::create([
                'name' => $request->section_name,
                'gradelevel' => $request->grade_level,
                'teacher_id' => $teacher->id,
                'semester_id' => $request->semester_id
            ]);

            // Update teacher's section reference
            $teacher->update(['section_id' => $section->id]);

            \DB::commit();
            return back()->with('success', "Section '{$section->name}' created and assigned to {$teacher->name}.");
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Section creation error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while creating section: ' . $e->getMessage());
        }
    }

    /**
     * Delete teacher and cascade delete related students
     */
    public function deleteTeacher($id)
    {
        try {
            $teacher = User::findOrFail($id);
            
            \DB::beginTransaction();
            
            // Get students associated with this teacher
            $students = Student::where('user_id', $teacher->id)->get();
            $teacherName = $teacher->name;
            
            // Clear section assignments
            Section::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
            
            // Remove from pivot table
            $teacher->sections()->detach();
            
            // Delete students and their files
            foreach ($students as $student) {
                // Clear QR code files
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    Storage::disk('public')->delete($student->qr_code);
                }
                
                // Clear picture
                if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                    Storage::disk('public')->delete('student_pictures/' . $student->picture);
                }
                
                // Delete student record
                $student->delete();
            }
            
            // Delete teacher
            $teacher->delete();
            
            \DB::commit();

            return redirect()->route('admin.manage-teachers')
                ->with('success', "Teacher '{$teacherName}' and {$students->count()} related students deleted successfully!");
                
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error deleting teacher: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the teacher. Please try again.');
        }
    }

    /**
     * Show manage semesters page
     */
    public function manageSemesters()
    {
        $semesters = Semester::with('school')->orderBy('start_date', 'desc')->paginate(10);
        $schools = School::all();

        return view('admin.manage-semesters', compact('semesters', 'schools'));
    }

    /**
     * Store new semester
     */
    public function storeSemester(Request $request)
    {
        try {
            if ($resp = $this->validateOrRespond($request, [
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_id' => 'required|exists:schools,id',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ])) return $resp;

            // Build create data array - only include existing columns
            $createData = [
                'name' => $request->name,
                'status' => 'active', // Default status
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'school_id' => $request->school_id,
            ];

           $tableColumns = \Schema::getColumnListing('semesters');
            
            if (in_array('am_time_in_start', $tableColumns)) {
                $createData['am_time_in_start'] = $request->am_time_in_start;
            }
            if (in_array('am_time_in_end', $tableColumns)) {
                $createData['am_time_in_end'] = $request->am_time_in_end;
            }
            if (in_array('pm_time_out_start', $tableColumns)) {
                $createData['pm_time_out_start'] = $request->pm_time_out_start;
            }
            if (in_array('pm_time_out_end', $tableColumns)) {
                $createData['pm_time_out_end'] = $request->pm_time_out_end;
            }

            Semester::create($createData);

            return redirect()->back()->with('success', 'Semester created successfully!');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create semester. Please check your data and try again.');
        }
    }

    /**
     * Update semester
     */
    public function updateSemester(Request $request, $id)
    {
        try {
            $semester = Semester::findOrFail($id);
            if ($resp = $this->validateOrRespond($request, [
                'name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_id' => 'required|exists:schools,id',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ])) return $resp;

            // Build update data array - only include existing columns
            $updateData = [
                'name' => $request->name,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'school_id' => $request->school_id,
            ];

            // Check if time columns exist in the database before adding them
            $tableColumns = \Schema::getColumnListing('semesters');
            
            if (in_array('am_time_in_start', $tableColumns)) {
                $updateData['am_time_in_start'] = $request->am_time_in_start;
            }
            if (in_array('am_time_in_end', $tableColumns)) {
                $updateData['am_time_in_end'] = $request->am_time_in_end;
            }
            if (in_array('pm_time_out_start', $tableColumns)) {
                $updateData['pm_time_out_start'] = $request->pm_time_out_start;
            }
            if (in_array('pm_time_out_end', $tableColumns)) {
                $updateData['pm_time_out_end'] = $request->pm_time_out_end;
            }

            $semester->update($updateData);
            return redirect()->back()->with('success', 'Semester updated successfully!');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update semester. Please check your data and try again.');
        }
    }

    /**
     * Delete semester and cascade delete related teachers and students
     */
    public function deleteSemester($id)
    {
        $semester = Semester::findOrFail($id);
        
         $teachers = User::where('role', 'teacher')
                        ->where('school_id', $semester->school_id)
                        ->get();
        
        foreach ($teachers as $teacher) {
             $students = Student::where('user_id', $teacher->id)->get();
            
             foreach ($students as $student) {
                 if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    Storage::disk('public')->delete($student->qr_code);
                }
                
                 if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                    Storage::disk('public')->delete('student_pictures/' . $student->picture);
                }
                
                 $student->delete();
            }
            
            // Delete teacher
            $teacher->delete();
        }
        
         $semester->delete();

        return redirect()->route('admin.manage-semesters')->with('success', 'Semester and related teachers/students deleted successfully!');
    }

    /**
     * Show manage students page
     */
    public function manageStudents(Request $request)
    {
        $query = Student::with(['user:id,name,email', 'school:id,name,address', 'section.teacher:id,name', 'section.teachers:id,name', 'semester:id,name']);
        
         if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_no', 'like', "%{$search}%")
                  ->orWhereHas('section', function($sectionQuery) use ($search) {
                      $sectionQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('gradelevel', 'like', "%{$search}%");
                  });
            });
        }
        
         if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        
         if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }
        
        // Handle section_id filter (direct section ID)
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        // Handle grade_section filter (format: "Grade 11|STEM")
        if ($request->filled('grade_section')) {
            $parts = explode('|', $request->grade_section);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
                $query->whereHas('section', function($sectionQuery) use ($gradeLevel, $sectionName) {
                    $sectionQuery->where('gradelevel', $gradeLevel)
                                ->where('name', $sectionName);
                });
            }
        }

         if ($request->filled('qr_status')) {
            if ($request->qr_status == 'with_qr') {
                $query->whereNotNull('qr_code');
            } elseif ($request->qr_status == 'without_qr') {
                $query->whereNull('qr_code');
            }
        }
        
        // Handle sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'name') {
            $query->orderBy('name', $sortOrder);
        } elseif ($sortBy === 'gender') {
            $query->orderBy('gender', $sortOrder)->orderBy('name', 'asc');
        } elseif ($sortBy === 'age') {
            $query->orderBy('age', $sortOrder)->orderBy('name', 'asc');
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $students = $query->get();
        
    $schools = School::select('id', 'name')->orderBy('name')->get();
    // Load teachers with their section(s) relationship so views can show assigned section labels
    $teachers = User::where('role', 'teacher')->with(['section', 'sections'])->orderBy('name')->get();
    // Provide semesters for the top filter (manageStudents view expects $semesters)
    $semesters = Semester::orderBy('created_at', 'desc')->get();
        
        // Get grade-section options for the filter dropdown
        $gradeSectionOptions = Student::with('section')
            ->whereHas('section')
            ->get()
            ->map(function($student) {
                return [
                    'value' => $student->section->gradelevel . '|' . $student->section->name,
                    'label' => 'Grade ' . $student->section->gradelevel . ' - ' . $student->section->name
                ];
            })
            ->unique('value')
            ->values();

    return view('admin.manage-students', compact('students', 'schools', 'teachers', 'gradeSectionOptions', 'semesters'));
    }

    /**
     * Show attendance reports page
     */
    public function attendanceReports(Request $request)
    {
        $schools = School::all();
        $teachers = User::where('role', 'teacher')->get();
        
        $attendanceData = collect();
        
        if ($request->filled(['start_date', 'end_date'])) {
            $query = Attendance::with(['student', 'student.user', 'student.school'])
                              ->whereBetween('date', [$request->start_date, $request->end_date]);
            
            if ($request->filled('school_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('school_id', $request->school_id);
                });
            }
            
            if ($request->filled('teacher_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('user_id', $request->teacher_id);
                });
            }
            
            $attendanceData = $query->orderBy('date', 'desc')->paginate(20);
        }

        return view('admin.attendance-reports', compact('schools', 'teachers', 'attendanceData'));
    }

    /**
     * Show teacher attendance reports page for admin
     */
    public function teacherAttendanceReports(Request $request)
    {
        $schools = School::all();
        $semesters = Semester::all();
        $teachers = User::where('role', 'teacher')->with(['school', 'section'])->get();
        $sections = Section::with(['semester', 'teacher'])->get();
        
        $type = $request->input('type', 'daily');
        $semesterId = $request->input('semester_id');
        $gradeSection = $request->input('grade_section');
        $teacherId = $request->input('teacher_id');
        $schoolId = $request->input('school_id');
        $sectionId = $request->input('section_id');
        $timeRange = $request->input('time_range');
        $reportMonthYear = $request->input('report_month_year');
        $reportDate = $request->input('report_date');
        
        // Parse month and year from the combined field for backward compatibility
        $reportMonth = null;
        $reportYear = date('Y');
        if ($reportMonthYear) {
            $parts = explode('-', $reportMonthYear);
            if (count($parts) == 2) {
                $reportYear = $parts[0];
                $reportMonth = (int)$parts[1];
            }
        }
        
        $attendanceData = collect();
        $reportData = null;
        $records = [];
        
        // Handle different date parameters based on report type
        switch ($type) {
            case 'daily':
                // For daily reports, use single date or convert to start/end date
                if ($reportDate) {
                    $request->merge([
                        'start_date' => $reportDate,
                        'end_date' => $reportDate
                    ]);
                }
                break;
            case 'monthly':
                // For monthly reports, use month and year to determine date range
                if ($reportMonth && $reportYear) {
                    $startDate = date('Y-m-01', mktime(0, 0, 0, $reportMonth, 1, $reportYear));
                    $endDate = date('Y-m-t', mktime(0, 0, 0, $reportMonth, 1, $reportYear));
                    $request->merge([
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]);
                }
                break;
            case 'quarterly':
                // For quarterly reports, use semester dates
                if ($semesterId) {
                    $semester = Semester::find($semesterId);
                    if ($semester) {
                        $request->merge([
                            'start_date' => $semester->start_date,
                            'end_date' => $semester->end_date
                        ]);
                    }
                }
                break;
        }
        
        // Handle predefined time ranges (fallback)
        if ($timeRange && $timeRange !== 'custom') {
            $dates = $this->getDateRangeFromTimeRange($timeRange);
            $request->merge(['start_date' => $dates['start'], 'end_date' => $dates['end']]);
        }
        
        // Handle SF2 generation request
        if ($request->has('generate_sf2') && $request->filled(['semester_id', 'report_month', 'report_year'])) {
            return $this->generateAdminSF2($request);
        }
        
        // Handle CSV export - check for any valid date range
        $hasValidDateRange = $request->filled(['start_date', 'end_date']) || 
                           ($type === 'daily' && $reportDate) ||
                           ($type === 'monthly' && $reportMonthYear) ||
                           ($type === 'quarterly'); // Quarterly doesn't require specific semester
                           
        if ($request->has('export') && $hasValidDateRange) {
            return $this->exportTeacherAttendanceCsv($request);
        }
        
        // Get students based on filters
        $students = $this->getFilteredStudents($request);
        $gradeSectionOptions = $this->getGradeSectionOptions($request);
        
        // Generate report based on type
        if ($hasValidDateRange || $timeRange) {
            switch ($type) {
                case 'daily':
                    $records = $this->generateDailyReport($students, $request);
                    break;
                case 'monthly':
                    $records = $this->generateMonthlyReport($students, $request);
                    break;
                case 'quarterly':
                    $records = $this->generateQuarterlyReport($students, $request);
                    break;
            }
            
            // Generate attendance data for detailed view
            $attendanceData = $this->getDetailedAttendanceData($request);
            
            // Generate report summary data
            $reportData = $this->generateTeacherAttendanceReportData($request);
        }
        
        $semester = $semesterId ? Semester::find($semesterId) : null;
        $semester_start = $semester ? $semester->start_date : null;
        $semester_end = $semester ? $semester->end_date : null;

        return view('admin.teacher-attendance-reports', compact(
            'schools', 'semesters', 'teachers', 'sections', 'attendanceData', 'reportData',
            'records', 'semester_start', 'semester_end', 'gradeSectionOptions', 'type'
        ));
    }

    /**
     * Generate SF2 for admin - reuse teacher functionality
     */
    public function generateAdminSF2(Request $request)
    {
        // Instantiate the ReportController and call its SF2 method
        $reportController = new \App\Http\Controllers\ReportController();
        return $reportController->generateSF2($request);
    }

    /**
     * Get SF2 options for admin - reuse teacher functionality
     */
    public function getAdminSF2Options(Request $request)
    {
        // Get available semesters - all semesters for admin
        $semesters = Semester::select('id', 'name', 'start_date', 'end_date')
                           ->orderBy('created_at', 'desc')
                           ->get();

        // Add semester date information for month filtering
        $semestersWithDates = $semesters->map(function($semester) {
            return [
                'id' => $semester->id,
                'name' => $semester->name,
                'start_date' => $semester->start_date,
                'end_date' => $semester->end_date,
                'start_month' => \Carbon\Carbon::parse($semester->start_date)->month,
                'start_year' => \Carbon\Carbon::parse($semester->start_date)->year,
                'end_month' => \Carbon\Carbon::parse($semester->end_date)->month,
                'end_year' => \Carbon\Carbon::parse($semester->end_date)->year
            ];
        });

        // Get combined grade level and section options - all sections for admin
        $gradeSection = Student::with('section')
            ->whereHas('section')
            ->get()
            ->filter(function($student) {
                return $student->section; // Only include students with sections
            })
            ->map(function($student) {
                return [
                    'value' => $student->section->gradelevel . '|' . $student->section->name,
                    'label' => $student->section->gradelevel . ' - ' . $student->section->name
                ];
            })
            ->unique('value')
            ->sortBy('label')
            ->values();

        // All months (will be filtered on frontend based on semester selection)
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return response()->json([
            'semesters' => $semestersWithDates,
            'grade_sections' => $gradeSection,
            'months' => $months
        ]);
    }

    /**
     * Export teacher attendance as CSV
     */
    public function exportTeacherAttendanceCsv(Request $request)
    {
        // Get filtered students
        $students = $this->getFilteredStudents($request);
        $type = $request->input('type', 'daily');
        
        $filename = 'teacher_attendance_' . $type . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($students, $request, $type) {
            $handle = fopen('php://output', 'w');
            
            if ($type === 'daily') {
                $date = $request->input('start_date', now()->toDateString());
                fputcsv($handle, ['Date', 'ID No', 'Name', 'School', 'Teacher', 'Grade', 'Section', 'AM In', 'AM Out', 'PM In', 'PM Out', 'Status']);
                
                $records = $this->generateDailyReport($students, $request);
                foreach ($records as $record) {
                    fputcsv($handle, [
                        $record->date,
                        $record->id_no,
                        $record->name,
                        $record->school,
                        $record->teacher,
                        $record->grade_level,
                        $record->section,
                        $record->am_in ?: '--',
                        $record->am_out ?: '--',
                        $record->pm_in ?: '--',
                        $record->pm_out ?: '--',
                        $record->status,
                    ]);
                }
            } elseif ($type === 'monthly') {
                fputcsv($handle, ['ID No', 'Name', 'School', 'Teacher', 'Grade', 'Section', 'Total Days', 'Present', 'Absent', 'Partial', 'Remarks']);
                
                $records = $this->generateMonthlyReport($students, $request);
                foreach ($records as $record) {
                    fputcsv($handle, [
                        $record->id_no,
                        $record->name,
                        $record->school,
                        $record->teacher,
                        $record->grade_level,
                        $record->section,
                        $record->total_day,
                        $record->present,
                        $record->absent,
                        $record->partial,
                        $record->remarks,
                    ]);
                }
            } elseif ($type === 'quarterly') {
                // Similar to monthly but with different date range
                fputcsv($handle, ['ID No', 'Name', 'School', 'Teacher', 'Grade', 'Section', 'Attendance Pattern']);
                
                $records = $this->generateQuarterlyReport($students, $request);
                foreach ($records as $record) {
                    $pattern = implode(' ', array_values($record->checks));
                    fputcsv($handle, [
                        $record->id_no,
                        $record->name,
                        $record->school,
                        $record->teacher,
                        $record->grade_level,
                        $record->section,
                        $pattern,
                    ]);
                }
            }
            
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get date range from predefined time range
     */
    private function getDateRangeFromTimeRange($timeRange)
    {
        $now = Carbon::now();
        
        switch ($timeRange) {
            case 'today':
                return ['start' => $now->toDateString(), 'end' => $now->toDateString()];
            case 'yesterday':
                $yesterday = $now->subDay();
                return ['start' => $yesterday->toDateString(), 'end' => $yesterday->toDateString()];
            case 'this_week':
                return ['start' => $now->startOfWeek()->toDateString(), 'end' => $now->endOfWeek()->toDateString()];
            case 'last_week':
                $lastWeek = $now->subWeek();
                return ['start' => $lastWeek->startOfWeek()->toDateString(), 'end' => $lastWeek->endOfWeek()->toDateString()];
            case 'this_month':
                return ['start' => $now->startOfMonth()->toDateString(), 'end' => $now->endOfMonth()->toDateString()];
            case 'last_month':
                $lastMonth = $now->subMonth();
                return ['start' => $lastMonth->startOfMonth()->toDateString(), 'end' => $lastMonth->endOfMonth()->toDateString()];
            case 'this_quarter':
                return ['start' => $now->startOfQuarter()->toDateString(), 'end' => $now->endOfQuarter()->toDateString()];
            case 'last_quarter':
                $lastQuarter = $now->subQuarter();
                return ['start' => $lastQuarter->startOfQuarter()->toDateString(), 'end' => $lastQuarter->endOfQuarter()->toDateString()];
            case 'this_year':
                return ['start' => $now->startOfYear()->toDateString(), 'end' => $now->endOfYear()->toDateString()];
            default:
                return ['start' => $now->toDateString(), 'end' => $now->toDateString()];
        }
    }
    
    /**
     * Get filtered students based on admin selections
     */
    private function getFilteredStudents(Request $request)
    {
        $studentQuery = Student::with(['section', 'user', 'school', 'semester']);
        
        if ($request->filled('teacher_id')) {
            $studentQuery->where('user_id', $request->teacher_id);
        }
        
        if ($request->filled('school_id')) {
            $studentQuery->where('school_id', $request->school_id);
        }
        
        if ($request->filled('semester_id')) {
            $studentQuery->where('semester_id', $request->semester_id);
        }
        
        if ($request->filled('section_id')) {
            $studentQuery->where('section_id', $request->section_id);
        }
        
        // Apply grade_section filter if provided
        if ($request->filled('grade_section')) {
            $parts = explode('|', $request->grade_section);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
                $studentQuery->whereHas('section', function($query) use ($gradeLevel, $sectionName) {
                    $query->where('gradelevel', $gradeLevel)->where('name', $sectionName);
                });
            }
        }
        
        return $studentQuery->orderBy('name')->get();
    }
    
    /**
     * Get grade section options for dropdown
     */
    private function getGradeSectionOptions(Request $request)
    {
        $query = Student::with('section');
        
        if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }
        
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        
        return $query->get()
            ->filter(function($student) {
                return $student->section; // Only include students with sections
            })
            ->map(function ($student) {
                return $student->section->gradelevel . '|' . $student->section->name;
            })
            ->unique()
            ->sort()
            ->values();
    }
    
    /**
     * Generate daily report
     */
    private function generateDailyReport($students, Request $request)
    {
        $date = $request->input('start_date', now()->toDateString());
        
        $semester = $request->filled('semester_id') ? Semester::find($request->semester_id) : null;
        
        if ($semester) {
            $semester_start = Carbon::parse($semester->start_date)->toDateString();
            $semester_end = Carbon::parse($semester->end_date)->toDateString();
            if ($date < $semester_start || $date > $semester_end) {
                return collect();
            }
        }

        $attendances = Attendance::whereDate('date', $date)->get()->keyBy('student_id');
        
        return $students->map(function ($student) use ($attendances, $date) {
            $att = $attendances->get($student->id);

            $status = '--';
            if ($att) {
                if ($att->time_in_am && $att->time_out_am && $att->time_in_pm && $att->time_out_pm) {
                    $status = 'Present';
                } elseif ($att->time_in_am || $att->time_in_pm) {
                    $status = 'Partial';
                } else {
                    $status = 'Absent';
                }
            } else {
                $status = 'Absent';
            }
            
            return (object)[
                'date'      => $date,
                'id_no'     => $student->id_no,
                'name'      => $student->name,
                'grade_level' => $student->grade_level,
                'section'   => $student->section_name,
                'school'    => $student->school ? $student->school->name : 'N/A',
                'teacher'   => $student->user ? $student->user->name : 'N/A',
                'am_in'     => $att && $att->time_in_am ? Carbon::parse($att->time_in_am)->setTimezone('Asia/Manila')->format('h:i A') : null,
                'am_out'    => $att && $att->time_out_am ? Carbon::parse($att->time_out_am)->setTimezone('Asia/Manila')->format('h:i A') : null,
                'pm_in'     => $att && $att->time_in_pm ? Carbon::parse($att->time_in_pm)->setTimezone('Asia/Manila')->format('h:i A') : null,
                'pm_out'    => $att && $att->time_out_pm ? Carbon::parse($att->time_out_pm)->setTimezone('Asia/Manila')->format('h:i A') : null,
                'status'    => $status,
            ];
        });
    }
    
    /**
     * Generate monthly report
     */
    private function generateMonthlyReport($students, Request $request)
    {
        $semester = $request->filled('semester_id') ? Semester::find($request->semester_id) : null;
        
        if ($semester) {
            $semester_start = Carbon::parse($semester->start_date)->startOfDay();
            $semester_end = Carbon::parse($semester->end_date)->endOfDay();
        } else {
            $semester_start = null;
            $semester_end = null;
        }

        $month = $request->input('month') ?: $request->input('start_date');

        if ($month) {
            $start = Carbon::parse($month)->startOfMonth();
            $end = Carbon::parse($month)->endOfMonth();
        } else {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        }

        if ($semester_start && $start < $semester_start) $start = $semester_start;
        if ($semester_end && $end > $semester_end) $end = $semester_end;

        if ($semester_start && $semester_end && $start > $semester_end) {
            $classDays = [];
        } else {
            $classDays = $this->getClassDays($start, $end);
        }
        $totalDays = count($classDays);

        if (empty($classDays)) {
            return $students->map(function ($student) {
                return (object)[
                    'id_no'      => $student->id_no,
                    'name'       => $student->name,
                    'grade_level' => $student->grade_level,
                    'section'    => $student->section_name,
                    'school'     => $student->school ? $student->school->name : 'N/A',
                    'teacher'    => $student->user ? $student->user->name : 'N/A',
                    'total_day'  => 0,
                    'present'    => 0,
                    'absent'     => 0,
                    'partial'    => 0,
                    'remarks'    => 'No class days in range',
                ];
            });
        } else {
            return $students->map(function ($student) use ($classDays, $totalDays) {
                $atts = Attendance::where('student_id', $student->id)
                    ->whereIn('date', $classDays)
                    ->get()
                    ->keyBy('date');

                $present = 0;
                $absent = 0;
                $partial = 0;

                foreach ($classDays as $day) {
                    $dayAtt = $atts->get($day);
                    if ($dayAtt) {
                        if ($dayAtt->time_in_am && $dayAtt->time_out_am && $dayAtt->time_in_pm && $dayAtt->time_out_pm) {
                            $present++;
                        } elseif ($dayAtt->time_in_am || $dayAtt->time_in_pm) {
                            $partial++;
                        } else {
                            $absent++;
                        }
                    } else {
                        $absent++;
                    }
                }

                if ($present == $totalDays && $totalDays > 0) {
                    $remarks = 'Good';
                } elseif ($present > 0 || $partial > 0) {
                    $remarks = 'Poor';
                } else {
                    $remarks = 'Bad';
                }

                return (object)[
                    'id_no'      => $student->id_no,
                    'name'       => $student->name,
                    'grade_level' => $student->grade_level,
                    'section'    => $student->section_name,
                    'school'     => $student->school ? $student->school->name : 'N/A',
                    'teacher'    => $student->user ? $student->user->name : 'N/A',
                    'total_day'  => $totalDays,
                    'present'    => $present,
                    'absent'     => $absent,
                    'partial'    => $partial,
                    'remarks'    => $remarks,
                ];
            });
        }
    }
    
    /**
     * Generate quarterly report
     */
    private function generateQuarterlyReport($students, Request $request)
    {
        $semester = $request->filled('semester_id') ? Semester::find($request->semester_id) : null;
        
        if ($semester) {
            $start = Carbon::parse($semester->start_date)->startOfDay();
            $end = Carbon::parse($semester->end_date)->endOfDay();
        } else {
            // If no semester selected, use current academic year
            $start = now()->startOfYear();
            $end = now()->endOfYear();
        }

        $classDays = $this->getClassDays($start, $end);

        // Always return student data, even if no class days
        return $students->map(function ($student) use ($classDays) {
            $checks = [];
            
            if (!empty($classDays)) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->whereIn('date', $classDays)
                    ->get()
                    ->keyBy('date');

                foreach ($classDays as $date) {
                    $att = $attendances->get($date);
                    if ($att) {
                        if ($att->time_in_am && $att->time_out_am && $att->time_in_pm && $att->time_out_pm) {
                            $checks[$date] = '✓';
                        } elseif ($att->time_in_am || $att->time_in_pm) {
                            $checks[$date] = '◐';
                        } else {
                            $checks[$date] = '✗';
                        }
                    } else {
                        $checks[$date] = '✗';
                    }
                }
            }

            $totalDays = count($classDays);
            $presentDays = count(array_filter($checks, function($check) { return $check === '✓'; }));
            $partialDays = count(array_filter($checks, function($check) { return $check === '◐'; }));
            $absentDays = count(array_filter($checks, function($check) { return $check === '✗'; }));
            
            // Calculate attendance rate including partial attendance as half
            $attendanceRate = $totalDays > 0 ? (($presentDays + ($partialDays * 0.5)) / $totalDays) * 100 : 0;
            
            // Determine remarks based on attendance rate
            if ($attendanceRate >= 80) {
                $remarks = 'Good';
            } elseif ($attendanceRate >= 60) {
                $remarks = 'Poor';
            } else {
                $remarks = 'Bad';
            }

            return (object)[
                'id_no'       => $student->id_no,
                'name'        => $student->name,
                'grade_level' => $student->section ? $student->section->gradelevel : 'N/A',
                'section'     => $student->section ? $student->section->name : 'N/A',
                'school'      => $student->school ? $student->school->name : 'N/A',
                'teacher'     => $student->user ? $student->user->name : 'N/A',
                'checks'      => $checks,
                'total_days'  => $totalDays,
                'present_days' => $presentDays,
                'partial_days' => $partialDays,
                'absent_days' => $absentDays,
                'remarks'     => $remarks,
            ];
        });
    }
    
    /**
     * Get class days (excluding weekends)
     */
    private function getClassDays($start, $end)
    {
        $classDays = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($current->dayOfWeek !== 0 && $current->dayOfWeek !== 6) {
                $classDays[] = $current->toDateString();
            }
            $current->addDay();
        }
        
        return $classDays;
    }
    
    /**
     * Get detailed attendance data for table display
     */
    private function getDetailedAttendanceData(Request $request)
    {
        $query = Attendance::with(['student', 'student.user', 'student.school', 'student.section'])
                          ->whereBetween('date', [$request->start_date, $request->end_date]);
        
        // Apply filters
        if ($request->filled('school_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('school_id', $request->school_id);
            });
        }
        
        if ($request->filled('semester_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('semester_id', $request->semester_id);
            });
        }
        
        if ($request->filled('teacher_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('user_id', $request->teacher_id);
            });
        }
        
        if ($request->filled('section_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }
        
        return $query->orderBy('date', 'desc')->paginate(20);
    }
    
    /**
     * Extract school year from semester name
     */
    private function extractSchoolYearFromSemester($semesterName)
    {
        // Try to extract year from semester name (e.g., "1st Semester 2025" -> "2024-2025")
        if (preg_match('/(\d{4})/', $semesterName, $matches)) {
            $year = (int)$matches[1];
            return ($year - 1) . '-' . $year;
        }
        
        // Fallback to current academic year
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        // If we're in the first half of the year (Jan-June), it's the previous academic year
        if ($currentMonth <= 6) {
            return ($currentYear - 1) . '-' . $currentYear;
        } else {
            return $currentYear . '-' . ($currentYear + 1);
        }
    }

    /**
     * Generate summary data for teacher attendance reports
     */
    private function generateTeacherAttendanceReportData(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        $baseQuery = Attendance::with(['student', 'student.user', 'student.school', 'student.section'])
                               ->whereBetween('date', [$startDate, $endDate]);
        
        // Apply same filters as main query
        if ($request->filled('school_id')) {
            $baseQuery->whereHas('student', function($q) use ($request) {
                $q->where('school_id', $request->school_id);
            });
        }
        
        if ($request->filled('semester_id')) {
            $baseQuery->whereHas('student', function($q) use ($request) {
                $q->where('semester_id', $request->semester_id);
            });
        }
        
        if ($request->filled('teacher_id')) {
            $baseQuery->whereHas('student', function($q) use ($request) {
                $q->where('user_id', $request->teacher_id);
            });
        }
        
        if ($request->filled('section_id')) {
            $baseQuery->whereHas('student', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }
        
        $attendanceRecords = $baseQuery->get();
        
        // Calculate statistics by teacher
        $teacherStats = [];
        $schoolStats = [];
        $sectionStats = [];
        
        foreach ($attendanceRecords as $record) {
            $teacher = $record->student->user;
            $school = $record->student->school;
            $section = $record->student->section;
            
            // Teacher statistics
            if ($teacher) {
                $teacherId = $teacher->id;
                if (!isset($teacherStats[$teacherId])) {
                    $teacherStats[$teacherId] = [
                        'teacher_name' => $teacher->name,
                        'school_name' => $school ? $school->name : 'Unknown',
                        'total_records' => 0,
                        'unique_students' => [],
                        'sections' => []
                    ];
                }
                $teacherStats[$teacherId]['total_records']++;
                $teacherStats[$teacherId]['unique_students'][$record->student_id] = true;
                
                if ($section) {
                    $sectionKey = "Grade {$section->gradelevel} - {$section->name}";
                    $teacherStats[$teacherId]['sections'][$sectionKey] = true;
                }
            }
            
            // School statistics
            if ($school) {
                $schoolId = $school->id;
                if (!isset($schoolStats[$schoolId])) {
                    $schoolStats[$schoolId] = [
                        'school_name' => $school->name,
                        'total_records' => 0,
                        'unique_students' => [],
                        'teachers' => []
                    ];
                }
                $schoolStats[$schoolId]['total_records']++;
                $schoolStats[$schoolId]['unique_students'][$record->student_id] = true;
                
                if ($teacher) {
                    $schoolStats[$schoolId]['teachers'][$teacher->id] = $teacher->name;
                }
            }
            
            // Section statistics
            if ($section) {
                $sectionId = $section->id;
                if (!isset($sectionStats[$sectionId])) {
                    $sectionStats[$sectionId] = [
                        'section_name' => "Grade {$section->gradelevel} - {$section->name}",
                        'teacher_name' => $teacher ? $teacher->name : 'Unknown',
                        'school_name' => $school ? $school->name : 'Unknown',
                        'total_records' => 0,
                        'unique_students' => []
                    ];
                }
                $sectionStats[$sectionId]['total_records']++;
                $sectionStats[$sectionId]['unique_students'][$record->student_id] = true;
            }
        }
        
        // Convert unique students arrays to counts
        foreach ($teacherStats as &$stats) {
            $stats['unique_students'] = count($stats['unique_students']);
            $stats['sections'] = array_keys($stats['sections']);
        }
        
        foreach ($schoolStats as &$stats) {
            $stats['unique_students'] = count($stats['unique_students']);
            $stats['teacher_count'] = count($stats['teachers']);
            $stats['teachers'] = array_values($stats['teachers']);
        }
        
        foreach ($sectionStats as &$stats) {
            $stats['unique_students'] = count($stats['unique_students']);
        }
        
        return [
            'total_records' => $attendanceRecords->count(),
            'total_unique_students' => $attendanceRecords->pluck('student_id')->unique()->count(),
            'date_range_days' => \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1,
            'teacher_stats' => array_values($teacherStats),
            'school_stats' => array_values($schoolStats),
            'section_stats' => array_values($sectionStats)
        ];
    }

    /**
     * Show manage students new page
     */
    public function manageStudentsNew()
    {
        $schools = School::all();
        $teachers = User::where('role', 'teacher')->get();
        $semesters = Semester::all();
        
        return view('admin.manage-students-new', compact('schools', 'teachers', 'semesters'));
    }

    /**
     * Show manage sections page
     */
    public function manageSections()
    {
        $sections = Section::with(['semester', 'teachers', 'students'])->paginate(10);
        $schools = School::all();
        $teachers = User::where('role', 'teacher')->get();
        $semesters = Semester::where('status', 'active')->get();
        
        return view('admin.manage-sections', compact('sections', 'schools', 'teachers', 'semesters'));
    }

    /**
     * Show attendance overview page
     */
    public function attendance()
    {
        $totalStudents = Student::count();
        $attendanceToday = Attendance::whereDate('date', today())->distinct('student_id')->count();
        $attendanceRate = $totalStudents > 0 ? round(($attendanceToday / $totalStudents) * 100, 1) : 0;
        
        $recentAttendance = Attendance::with(['student'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        return view('admin.attendance', compact('totalStudents', 'attendanceToday', 'attendanceRate', 'recentAttendance'));
    }

    /**
     * Show reports page
     */
    public function reports()
    {
        $totalStudents = Student::count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalSections = Section::count();
        $totalSchools = School::count();
        
        return view('admin.reports', compact('totalStudents', 'totalTeachers', 'totalSections', 'totalSchools'));
    }

    /**
     * Show semester management page
     */
    public function semester()
    {
        return redirect()->route('admin.manage-semesters');
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        $settings = [
            'system_name' => 'QR Attendance System',
            'version' => '1.0.0',
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
        
        return view('admin.settings', compact('settings'));
    }

    /**
     * Store a new student
     */
    public function storeStudent(Request $request)
    {
        $validated = $this->validateForResponse($request, [
            'id_no' => 'required|string|max:255|unique:students,id_no',
            'name' => 'required|string|max:255',
            'gender' => 'required|string|max:1',
            'age' => 'required|integer',
            'address' => 'nullable|string|max:255',
            'cp_no' => 'nullable|string|max:15',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:15',
            'school_id' => 'nullable|exists:schools,school_id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        Student::create($request->all());

        return redirect()->route('admin.manage-students')->with('success', 'Student added successfully.');
    }

    /**
     * Update a student
     */
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $validated = $this->validateForResponse($request, [
            'id_no' => 'required|string|max:255|unique:students,id_no,' . $id,
            'name' => 'required|string|max:255',
            'gender' => 'required|string|max:1',
            'age' => 'required|integer',
            'address' => 'nullable|string|max:255',
            'cp_no' => 'nullable|string|max:15',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:15',
            'school_id' => 'nullable|exists:schools,school_id',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $student->update($request->all());

        return redirect()->route('admin.manage-students')->with('success', 'Student updated successfully.');
    }

    /**
     * Delete a student
     */
    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        
        // Clear QR code files
        if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
            Storage::disk('public')->delete($student->qr_code);
        }
        
        // Clear picture
        if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
            Storage::disk('public')->delete('student_pictures/' . $student->picture);
        }

        $student->delete();

        return redirect()->route('admin.manage-students')->with('success', 'Student deleted successfully.');
    }

    /**
     * Bulk delete students
     */
    public function bulkDeleteStudents(Request $request)
    {
        $validated = $this->validateForResponse($request, [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $students = Student::whereIn('id', $request->student_ids)->get();

        $deletedCount = 0;
        foreach ($students as $student) {
            // Clear QR code files
            if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                Storage::disk('public')->delete($student->qr_code);
            }
            
            // Clear picture
            if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                Storage::disk('public')->delete('student_pictures/' . $student->picture);
            }

            $student->delete();
            $deletedCount++;
        }

        return redirect()->route('admin.manage-students')->with('success', "{$deletedCount} student(s) deleted successfully.");
    }

    /**
     * Generate QR codes for students
     */
    public function generateQrs(Request $request)
    {
        // If specific student IDs are provided, generate QR codes for those students only
        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $students = Student::whereIn('id', $request->student_ids)->get();
        } else {
            // Generate for all students
            $students = Student::all();
        }
        
        $generated = 0;

        foreach ($students as $student) {
            if ($this->generateQrForStudent($student)) {
                $generated++;
            }
        }

        if ($generated > 0) {
            return back()->with('success', "$generated QR code(s) generated for students missing them.");
        } else {
            return back()->with('info', 'All selected students already have QR codes.');
        }
    }

    /**
     * Generate QR code for individual student
     */
    public function generateQr($id)
    {
        $student = Student::findOrFail($id);

        if ($this->generateQrForStudent($student)) {
            return back()->with('success', 'QR code generated for student.');
        } else {
            return back()->with('info', 'Student already has a QR code.');
        }
    }

    /**
     * Generate QR code for a student
     */
    private function generateQrForStudent(Student $student)
    {
        // Generate random 10-character string (alphanumeric)
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $qrCodeData = $student->id_no . '_' . $randomString;
        
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $qrPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';

        if (!Storage::disk('public')->exists($qrPath) || !$student->qr_code) {
            $data = [
                'student_id' => $student->id,
                'name' => $student->name,
                'school_id' => $student->school_id,
                'qr_data' => $qrCodeData, // Add the new QR data format
            ];
            
            $qrImage = QrCode::format('svg')
                ->size(200)
                ->errorCorrection('M')
                ->generate($qrCodeData); // Use the new format instead of JSON
            
            Storage::disk('public')->put($qrPath, $qrImage);
            
            // Save both qr_code (file path) and stud_code (the data)
            $student->update([
                'qr_code' => $qrPath,
                'stud_code' => $qrCodeData
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Print QR codes
     */
    public function printQrs()
    {
        $students = Student::whereNotNull('qr_code')->get();
        return view('admin.print-qrs', compact('students'));
    }

    /**
     * Download QR codes as ZIP
     */
    public function downloadQrs()
    {
        $students = Student::whereNotNull('qr_code')->get();
        
        if ($students->isEmpty()) {
            return back()->with('error', 'No QR codes found to download.');
        }

        $zipFileName = 'student_qr_codes_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive();
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($students as $student) {
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    $qrFilePath = Storage::disk('public')->path($student->qr_code);
                    $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                    $fileName = $student->id_no . '_' . $sanitizedName . '.svg';
                    $zip->addFile($qrFilePath, $fileName);
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Failed to create QR codes archive.');
    }

    /**
     * Export students data
     */
    public function exportStudents()
    {
        $students = Student::with(['user', 'school'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_export_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID No',
                'Name',
                'Gender',
                'Age',
                'School',
                'Teacher',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Relationship',
                'Contact Person Contact',
                'QR Code Status'
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->id_no ?? '',
                    $student->name,
                    $student->gender == 'M' ? 'Male' : ($student->gender == 'F' ? 'Female' : $student->gender),
                    $student->age,
                    $student->school->name ?? 'N/A',
                    $student->user->name ?? 'N/A',
                    $student->address,
                    $student->cp_no ?? '',
                    $student->contact_person_name ?? '',
                    $student->contact_person_relationship ?? '',
                    $student->contact_person_contact ?? '',
                    $student->qr_code ? 'Available' : 'Missing'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download Excel template for student import
     */
    public function downloadTemplate()
    {
        return $this->generateStandardTemplate();
    }

    /**
     * Generate standard student import template
     */
    private function generateStandardTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            
            // Standard headers matching ImportController expected order
            fputcsv($handle, [
                'ID No',
                'Name',
                'Gender',
                'Age',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Phone',
                'Relationship'
            ]);
            
            // Add example data rows
            fputcsv($handle, [
                '0001',
                'Juan Dela Cruz',
                'M',
                '17',
                'Barangay Example, San Guillermo, Isabela',
                "\t09171234567",
                'Maria Dela Cruz',
                "\t09987654321",
                'Mother'
            ]);
            
            fputcsv($handle, [
                '0002',
                'Maria Santos',
                'F',
                '16',
                'Zone 2, San Guillermo, Isabela',
                "\t09281234567",
                'Jose Santos',
                "\t09123456789",
                'Father'
            ]);
            
            fputcsv($handle, [
                '0003',
                'Ana Rodriguez',
                'F',
                '18',
                'Poblacion, San Guillermo, Isabela',
                "\t09123456789",
                'Carlos Rodriguez',
                "\t09234567890",
                'Father'
            ]);
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download sample data for reference
     */
    public function downloadSampleData()
    {
        $schools = School::with('users')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_sample_data.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () use ($schools) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'id_no',
                'name',
                'gender',
                'age',
                'school_id',
                'teacher_id',
                'address',
                'cp_no',
                'contact_person_name',
                'contact_person_relationship',
                'contact_person_contact'
            ]);
            
            // Sample data
            $sampleData = [
                ['2024001', 'Alice Johnson', 'F', '19', '1', '1', '456 Oak Ave, Springfield', '09111111111', 'Robert Johnson', 'Father', '09222222222'],
                ['2024002', 'Bob Smith', 'M', '20', '1', '1', '789 Pine St, Springfield', '09333333333', 'Mary Smith', 'Mother', '09444444444'],
                ['2024003', 'Carol Davis', 'F', '18', '1', '', '321 Elm St, Springfield', '09555555555', 'David Davis', 'Father', '09666666666'],
            ];

            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            
            fputcsv($handle, []);
            fputcsv($handle, ['AVAILABLE SCHOOLS:']);
            foreach ($schools as $school) {
                fputcsv($handle, ["School ID: {$school->id}", "Name: {$school->name}"]);
            }
            
            fputcsv($handle, []);
            fputcsv($handle, ['AVAILABLE TEACHERS:']);
            foreach ($schools as $school) {
                foreach ($school->users->where('role', 'teacher') as $teacher) {
                    fputcsv($handle, ["Teacher ID: {$teacher->id}", "Name: {$teacher->name}", "School: {$school->name}"]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }



    /**
     * Show import guide
     */
    public function importGuide()
    {
        $schools = School::with('users')->get();
        return view('admin.import-guide', compact('schools'));
    }

    /**
     * Bulk export selected students
     */
    public function bulkExportStudents(Request $request)
    {
        $studentIds = $request->input('student_ids', []);
        
        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'No students selected for export.');
        }
        
        $students = Student::with(['school', 'user'])
            ->whereIn('id', $studentIds)
            ->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="selected_students_' . date('Y-m-d') . '.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID No',
                'Name',
                'Gender',
                'Age',
                'School',
                'Teacher',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Relationship',
                'Contact Person Contact',
                'QR Code Status'
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->id_no ?? '',
                    $student->name,
                    $student->gender == 'M' ? 'Male' : ($student->gender == 'F' ? 'Female' : $student->gender),
                    $student->age,
                    $student->school->name ?? 'N/A',
                    $student->user->name ?? 'N/A',
                    $student->address,
                    $student->cp_no ?? '',
                    $student->contact_person_name ?? '',
                    $student->contact_person_relationship ?? '',
                    $student->contact_person_contact ?? '',
                    $student->qr_code ? 'Available' : 'Missing'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
    
    /**
     * Show admin message page
     */
    public function message()
    {
        $teachers = User::where('role', 'teacher')
                       ->with('section:id,name')
                       ->select('id', 'name', 'section_id')
                       ->orderBy('name')
                       ->get();
        
        $schools = School::select('id', 'name')->orderBy('name')->get();
        
        return view('admin.message', compact('teachers', 'schools'));
    }
    
    /**
     * Get teachers for API (for admin messaging)
     */
    public function getTeachersForApi()
    {
        try {
            $teachers = User::where('role', 'teacher')
                          ->with(['school:id,name', 'section:id,name'])
                          ->select('id', 'name', 'phone_number', 'school_id', 'section_id')
                          ->orderBy('name')
                          ->get();
            
            return response()->json([
                'success' => true,
                'teachers' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching teachers: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all students for API (for admin messaging)
     */
    public function getAllStudentsForApi()
    {
        try {
            $students = Student::select('id', 'name', 'user_id', 'contact_person_name', 'contact_person_contact', 'school_id')
                             ->with(['user:id,name', 'school:id,name'])
                             ->orderBy('name')
                             ->get();
            
            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sections for a specific teacher (API endpoint)
     */
    public function getTeacherSections($teacherId)
    {
        try {
            $teacher = User::findOrFail($teacherId);
            
            if ($teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a teacher'
                ], 400);
            }

            // Get sections where teacher is assigned (both legacy teacher_id and many-to-many)
            $sections = Section::with(['semester', 'students'])
                              ->where(function($query) use ($teacherId) {
                                  $query->where('teacher_id', $teacherId)
                                        ->orWhereHas('teachers', function($subQuery) use ($teacherId) {
                                            $subQuery->where('users.id', $teacherId);
                                        });
                              })
                              ->get()
                              ->map(function($section) {
                                  return [
                                      'id' => $section->id,
                                      'name' => $section->name,
                                      'gradelevel' => $section->gradelevel,
                                      'display_name' => "Grade {$section->gradelevel} - {$section->name}",
                                      'semester_name' => $section->semester ? $section->semester->name : 'No Semester',
                                      'students_count' => $section->students->count(),
                                      'value' => $section->gradelevel . '|' . $section->name // For grade_section filter format
                                  ];
                              });

            return response()->json($sections);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show edit student form (admin)
     */
    public function editStudent($id)
    {
        $student = \App\Models\Student::with(['school', 'section'])->findOrFail($id);
        $schools = School::all();
        $sections = \App\Models\Section::all();
        
        return view('admin.edit_student', compact('student', 'schools', 'sections'));
    }

    /**
     * Update student (admin)
     */
    public function updateStudentAdmin(Request $request, $id)
    {
        $student = \App\Models\Student::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'id_no' => 'required|string|max:20|unique:students,id_no,' . $id,
            'age' => 'required|integer|min:1|max:100',
            'gender' => 'required|in:M,F',
            'address' => 'required|string|max:500',
            'cp_no' => 'required|string|max:15',
            'section_id' => 'required|exists:sections,id',
            'semester_id' => 'required|exists:semesters,id',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:50',
            'contact_person_contact' => 'nullable|string|max:15',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'captured_image' => 'nullable|string',
        ]);

         $updateData = $request->except(['picture', 'captured_image']);
        
        if ($request->hasFile('picture')) {
             if ($student->picture && file_exists(storage_path('app/public/student_pictures/' . $student->picture))) {
                unlink(storage_path('app/public/student_pictures/' . $student->picture));
            }
            
            $picture = $request->file('picture');
            $pictureName = time() . '_' . $student->id . '.' . $picture->getClientOriginalExtension();
            $picture->storeAs('public/student_pictures', $pictureName);
            $updateData['picture'] = $pictureName;
        } elseif ($request->filled('captured_image')) {
             $imageData = $request->captured_image;
            if (strpos($imageData, 'data:image/') === 0) {
                 if ($student->picture && file_exists(storage_path('app/public/student_pictures/' . $student->picture))) {
                    unlink(storage_path('app/public/student_pictures/' . $student->picture));
                }
                
                $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $imageData = base64_decode($imageData);
                
                $pictureName = time() . '_' . $student->id . '.jpg';
                file_put_contents(storage_path('app/public/student_pictures/' . $pictureName), $imageData);
                $updateData['picture'] = $pictureName;
            }
        }

        $student->update($updateData);

        return redirect()->route('admin.students.edit', $student->id)->with('success', 'Student updated successfully!');
    }

    /**
     * Get teachers by school for cascading dropdown
     */
    public function getTeachersBySchool($schoolId)
    {
        $teachers = User::where('role', 'teacher')
                       ->where('school_id', $schoolId)
                       ->where('is_active', true)
                       ->with(['sections' => function($query) {
                           $query->select('sections.id', 'sections.name', 'sections.gradelevel');
                       }])
                       ->select('id', 'name', 'position')
                       ->orderBy('name')
                       ->get()
                       ->map(function($teacher) {
                           return [
                               'id' => $teacher->id,
                               'name' => $teacher->name,
                               'position' => $teacher->position,
                               'sections_count' => $teacher->sections->count(),
                               'sections_preview' => $teacher->sections->take(2)->map(function($section) {
                                   return "Grade {$section->gradelevel} - {$section->name}";
                               })->implode(', ') . ($teacher->sections->count() > 2 ? '...' : '')
                           ];
                       });
        
        return response()->json($teachers);
    }

    /**
     * Get sections by teacher for cascading dropdown
     */
    public function getSectionsByTeacher($teacherId)
    {
        // Get sections where teacher is assigned (both legacy teacher_id and many-to-many)
        $sections = Section::with(['semester', 'students'])
                          ->where(function($query) use ($teacherId) {
                              $query->where('teacher_id', $teacherId)
                                    ->orWhereHas('teachers', function($subQuery) use ($teacherId) {
                                        $subQuery->where('users.id', $teacherId);
                                    });
                          })
                          ->select('id', 'name', 'gradelevel', 'semester_id', 'teacher_id')
                          ->orderBy('gradelevel')
                          ->orderBy('name')
                          ->get()
                          ->map(function($section) {
                              return [
                                  'id' => $section->id,
                                  'name' => $section->name,
                                  'gradelevel' => $section->gradelevel,
                                  'display_name' => "Grade {$section->gradelevel} - {$section->name}",
                                  'semester_name' => $section->semester ? $section->semester->name : 'No Semester',
                                  'students_count' => $section->students->count(),
                                  'value' => $section->gradelevel . '|' . $section->name // For grade_section filter format
                              ];
                          });
        
        return response()->json($sections);
    }

    /**
     * Get schools with teacher and section counts for enhanced dropdown
     */
    public function getSchoolsWithCounts()
    {
        $schools = School::with(['users' => function($query) {
                              $query->where('role', 'teacher')->where('is_active', true);
                          }])
                         ->get()
                         ->map(function($school) {
                             $teacherCount = $school->users->count();
                             $sectionCount = Section::whereHas('teachers', function($query) use ($school) {
                                 $query->where('school_id', $school->id);
                             })->orWhereHas('teacher', function($query) use ($school) {
                                 $query->where('school_id', $school->id);
                             })->count();

                             return [
                                 'id' => $school->id,
                                 'name' => $school->name,
                                 'location' => $school->location ?? '',
                                 'teachers_count' => $teacherCount,
                                 'sections_count' => $sectionCount,
                                 'display_name' => "{$school->name} ({$teacherCount} teachers, {$sectionCount} sections)"
                             ];
                         });

        return response()->json($schools);
    }

    /**
     * Get valid months for a semester based on start and end dates
     */
    public function getSemesterMonths($semesterId)
    {
        try {
            $semester = Semester::findOrFail($semesterId);
            
            $startDate = Carbon::parse($semester->start_date);
            $endDate = Carbon::parse($semester->end_date);
            
            $months = [];
            $current = $startDate->copy()->startOfMonth();
            
            while ($current <= $endDate->endOfMonth()) {
                $monthNumber = $current->month;
                $monthName = $current->format('F');
                $year = $current->year;
                
                $months[] = [
                    'value' => $monthNumber,
                    'name' => $monthName,
                    'year' => $year,
                    'full_date' => $current->format('Y-m'),
                    'display' => $monthName . ' ' . $year
                ];
                
                $current->addMonth();
            }
            
            return response()->json([
                'success' => true,
                'months' => $months,
                'semester' => [
                    'name' => $semester->name,
                    'start_date' => $semester->start_date->format('Y-m-d'),
                    'end_date' => $semester->end_date->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching semester months: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schools by semester for cascading dropdown
     */
    public function getSchoolsBySemester($semesterId)
    {
        // For now, return all schools since semester-school relationship might not be direct
        // You can modify this logic based on your specific business requirements
        $schools = \App\Models\School::select('id', 'name')
                                    ->orderBy('name')
                                    ->get();
        
        return response()->json($schools);
    }

    /**
     * Show admin account management page
     */
    public function account()
    {
        $admin = Auth::user();
        return view('admin.account', compact('admin'));
    }

    /**
     * Update admin profile information
     */
    public function updateAccount(Request $request)
    {
        $admin = Auth::user();
        
        $validated = $this->validateForResponse($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'username' => 'required|string|max:255|unique:users,username,' . $admin->id,
            'phone_number' => 'nullable|string|max:20',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('admin.account')->with('success', 'Account updated successfully!');
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::user();
        
        $validated = $this->validateForResponse($request, [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (is_object($validated)) {
            return $validated;
        }

        // Check if current password is correct
        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        // Update password
        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('admin.account')->with('success', 'Password updated successfully!');
    }
}
