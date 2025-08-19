<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SemesterController extends Controller
{
    /**
     * Display a listing of the semesters.
     */
    public function index()
    {
        try {
            Log::info('Semester index accessed', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role ?? 'unknown'
            ]);

            $user = Auth::user();
            
            // Get semesters based on user role
            if ($user->role === 'admin') {
                $semesters = Semester::with('school')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                    
                // Get all schools for admin dropdown
                $schools = School::orderBy('name')->get();
                
                // Get all sections with their relationships for section management
                $sections = Section::with(['teacher', 'semester', 'students'])
                    ->orderBy('semester_id', 'desc')
                    ->orderBy('gradelevel')
                    ->orderBy('name')
                    ->get();
                
                // Get all teachers for section assignment
                $teachers = User::where('role', 'teacher')->orderBy('name')->get();
                    
                Log::info('Admin viewing all semesters', [
                    'user_id' => $user->id,
                    'semesters_count' => $semesters->count(),
                    'sections_count' => $sections->count()
                ]);

                return view('admin.manage-semesters', compact('semesters', 'schools', 'sections', 'teachers'));
            } else {
                // Teachers can only see semesters from their school
                $semesters = Semester::with('school')
                    ->where('school_id', $user->school_id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);
                
                // Get sections for teacher's school only (through semester relationship)
                $sections = Section::with(['teacher:id,name', 'semester:id,name'])
                    ->withCount('students')
                    ->whereHas('semester', function ($query) use ($user) {
                        $query->where('school_id', $user->school_id);
                    })
                    ->orderBy('semester_id', 'desc')
                    ->orderBy('gradelevel')
                    ->orderBy('name')
                    ->get();
                
                // Get teachers from same school only
                $teachers = User::where('role', 'teacher')
                    ->where('school_id', $user->school_id)
                    ->orderBy('name')
                    ->get();
                    
                Log::info('Teacher viewing school semesters', [
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'semesters_count' => $semesters->count(),
                    'sections_count' => $sections->count()
                ]);

                return view('teacher.semester', compact('semesters', 'sections', 'teachers'));
            }
            
        } catch (\Exception $e) {
            Log::error('Error in semester index', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error loading semesters: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new semester.
     */
    public function create()
    {
        try {
            Log::info('Semester create form accessed', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role ?? 'unknown'
            ]);

            $user = Auth::user();
            
             if ($user->role !== 'admin') {
                Log::warning('Non-admin tried to access semester create form', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                
                return redirect()->back()->with('error', 'Only administrators can create semesters.');
            }
            
            // Get schools for admin
            $schools = School::orderBy('name')->get();

            return view('admin.semester-create', compact('schools'));
            
        } catch (\Exception $e) {
            Log::error('Error in semester create form', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Error loading create form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created semester in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Semester store attempt', [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'school_id' => 'required|exists:schools,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:active,inactive',
                'morning_period_start' => 'nullable|date_format:H:i',
                'morning_period_end' => 'nullable|date_format:H:i|after:morning_period_start',
                'afternoon_period_start' => 'nullable|date_format:H:i',
                'afternoon_period_end' => 'nullable|date_format:H:i|after:afternoon_period_start',
            ]);

            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $validated['school_id'] != $user->school_id) {
                Log::warning('Unauthorized semester creation attempt', [
                    'user_id' => $user->id,
                    'user_school_id' => $user->school_id,
                    'requested_school_id' => $validated['school_id']
                ]);
                
                return redirect()->back()->with('error', 'You can only create semesters for your school.');
            }

            // If setting this semester as active, deactivate others in the same school
            if ($validated['status'] === 'active') {
                Semester::where('school_id', $validated['school_id'])
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
                    
                Log::info('Deactivated other semesters in school', [
                    'school_id' => $validated['school_id'],
                    'user_id' => $user->id
                ]);
            }

            $semester = Semester::create($validated);

            Log::info('Semester created successfully', [
                'semester_id' => $semester->id,
                'semester_name' => $semester->name,
                'school_id' => $semester->school_id,
                'user_id' => $user->id
            ]);

            return redirect()->route('teacher.semesters')->with('success', 'Semester created successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Semester validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);
            
            return redirect()->back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error creating semester', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error creating semester: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified semester.
     */
    public function show(Semester $semester)
    {
        try {
            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $semester->school_id != $user->school_id) {
                Log::warning('Unauthorized semester view attempt', [
                    'user_id' => $user->id,
                    'semester_id' => $semester->id,
                    'user_school_id' => $user->school_id,
                    'semester_school_id' => $semester->school_id
                ]);
                
                return redirect()->back()->with('error', 'You can only view semesters from your school.');
            }

            Log::info('Semester viewed', [
                'semester_id' => $semester->id,
                'user_id' => $user->id
            ]);

            $semester->load('school');
            return view('semesters.show', compact('semester'));
            
        } catch (\Exception $e) {
            Log::error('Error viewing semester', [
                'semester_id' => $semester->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Error viewing semester: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified semester.
     */
    public function edit(Semester $semester)
    {
        try {
            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $semester->school_id != $user->school_id) {
                Log::warning('Unauthorized semester edit attempt', [
                    'user_id' => $user->id,
                    'semester_id' => $semester->id,
                    'user_school_id' => $user->school_id,
                    'semester_school_id' => $semester->school_id
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
                
                return redirect()->back()->with('error', 'You can only edit semesters from your school.');
            }

            Log::info('Semester edit form accessed', [
                'semester_id' => $semester->id,
                'user_id' => $user->id
            ]);

            // Always return JSON data for AJAX requests (both admin and teacher use modals)
            return response()->json([
                'id' => $semester->id,
                'name' => $semester->name,
                'status' => $semester->status,
                'start_date' => $semester->start_date,
                'end_date' => $semester->end_date,
                'school_id' => $semester->school_id,
                'am_time_in_start_input' => $semester->am_time_in_start ? \Carbon\Carbon::parse($semester->am_time_in_start)->format('H:i') : '',
                'am_time_in_end_input' => $semester->am_time_in_end ? \Carbon\Carbon::parse($semester->am_time_in_end)->format('H:i') : '',
                'am_time_out_start_input' => $semester->am_time_out_start ? \Carbon\Carbon::parse($semester->am_time_out_start)->format('H:i') : '',
                'am_time_out_end_input' => $semester->am_time_out_end ? \Carbon\Carbon::parse($semester->am_time_out_end)->format('H:i') : '',
                'pm_time_in_start_input' => $semester->pm_time_in_start ? \Carbon\Carbon::parse($semester->pm_time_in_start)->format('H:i') : '',
                'pm_time_in_end_input' => $semester->pm_time_in_end ? \Carbon\Carbon::parse($semester->pm_time_in_end)->format('H:i') : '',
                'pm_time_out_start_input' => $semester->pm_time_out_start ? \Carbon\Carbon::parse($semester->pm_time_out_start)->format('H:i') : '',
                'pm_time_out_end_input' => $semester->pm_time_out_end ? \Carbon\Carbon::parse($semester->pm_time_out_end)->format('H:i') : '',
                'student_count' => $semester->students()->count(),
                'attendance_count' => Attendance::whereHas('student', function($query) use ($semester) {
                    $query->where('semester_id', $semester->id);
                })->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading semester edit form', [
                'semester_id' => $semester->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Error loading semester data'], 500);
        }
    }

    /**
     * Update the specified semester in storage.
     */
    public function update(Request $request, Semester $semester)
    {
        try {
            Log::info('Semester update attempt', [
                'semester_id' => $semester->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $semester->school_id != $user->school_id) {
                Log::warning('Unauthorized semester update attempt', [
                    'user_id' => $user->id,
                    'semester_id' => $semester->id,
                    'user_school_id' => $user->school_id,
                    'semester_school_id' => $semester->school_id
                ]);
                
                return redirect()->back()->with('error', 'You can only update semesters from your school.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'school_id' => 'required|exists:schools,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:active,inactive',
                'morning_period_start' => 'nullable|date_format:H:i',
                'morning_period_end' => 'nullable|date_format:H:i|after:morning_period_start',
                'afternoon_period_start' => 'nullable|date_format:H:i',
                'afternoon_period_end' => 'nullable|date_format:H:i|after:afternoon_period_start',
            ]);

             if ($user->role !== 'admin' && $validated['school_id'] != $user->school_id) {
                Log::warning('Unauthorized school change attempt', [
                    'user_id' => $user->id,
                    'user_school_id' => $user->school_id,
                    'requested_school_id' => $validated['school_id']
                ]);
                
                return redirect()->back()->with('error', 'You cannot move semesters to other schools.');
            }

            // Validate time ranges for overlaps
            $tempSemester = new Semester($validated);
            $validation = $tempSemester->validateTimeRanges();
            if (!$validation['valid']) {
                return redirect()->back()->with('error', $validation['message'])->withInput();
            }

             if ($validated['status'] === 'active' && $semester->status !== 'active') {
                Semester::where('school_id', $validated['school_id'])
                    ->where('id', '!=', $semester->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
                    
                Log::info('Deactivated other semesters in school during update', [
                    'school_id' => $validated['school_id'],
                    'updated_semester_id' => $semester->id,
                    'user_id' => $user->id
                ]);
            }

            $semester->update($validated);

            Log::info('Semester updated successfully', [
                'semester_id' => $semester->id,
                'semester_name' => $semester->name,
                'user_id' => $user->id
            ]);

            return redirect()->route('teacher.semesters')->with('success', 'Semester updated successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Semester update validation failed', [
                'semester_id' => $semester->id,
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);
            
            return redirect()->back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error updating semester', [
                'semester_id' => $semester->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error updating semester: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified semester from storage.
     */
    public function destroy(Semester $semester)
    {
        try {
            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $semester->school_id != $user->school_id) {
                Log::warning('Unauthorized semester delete attempt', [
                    'user_id' => $user->id,
                    'semester_id' => $semester->id,
                    'user_school_id' => $user->school_id,
                    'semester_school_id' => $semester->school_id
                ]);
                
                return redirect()->back()->with('error', 'You can only delete semesters from your school.');
            }

            // Check if semester has associated data that would prevent deletion
            $hasStudents = $semester->students()->exists();
            $hasSessions = $semester->attendanceSessions()->exists();
            
            if ($hasStudents || $hasSessions) {
                Log::warning('Cannot delete semester with associated data', [
                    'semester_id' => $semester->id,
                    'has_students' => $hasStudents,
                    'has_sessions' => $hasSessions,
                    'user_id' => $user->id
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'Cannot delete semester with associated students or attendance sessions.'], 422);
                }
                
                return redirect()->back()->with('error', 'Cannot delete semester with associated students or attendance sessions.');
            }

            $semesterName = $semester->name;
            $semesterId = $semester->id;
            
            $semester->delete();

            Log::info('Semester deleted successfully', [
                'semester_id' => $semesterId,
                'semester_name' => $semesterName,
                'user_id' => $user->id
            ]);

            return redirect()->route('teacher.semesters')->with('success', 'Semester deleted successfully.');
            
        } catch (\Exception $e) {
            Log::error('Error deleting semester', [
                'semester_id' => $semester->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error deleting semester: ' . $e->getMessage());
        }
    }

    /**
     * Toggle semester status (active/inactive).
     */
    public function toggleStatus(Semester $semester)
    {
        try {
            // Check user permissions
            $user = Auth::user();
            if ($user->role !== 'admin' && $semester->school_id != $user->school_id) {
                Log::warning('Unauthorized semester status toggle attempt', [
                    'user_id' => $user->id,
                    'semester_id' => $semester->id
                ]);
                
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $newStatus = $semester->status === 'active' ? 'inactive' : 'active';
            
            // If activating, deactivate others in the same school
            if ($newStatus === 'active') {
                Semester::where('school_id', $semester->school_id)
                    ->where('id', '!=', $semester->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
                    
                Log::info('Deactivated other semesters during status toggle', [
                    'school_id' => $semester->school_id,
                    'activated_semester_id' => $semester->id,
                    'user_id' => $user->id
                ]);
            }
            
            $semester->update(['status' => $newStatus]);

            Log::info('Semester status toggled', [
                'semester_id' => $semester->id,
                'old_status' => $semester->status,
                'new_status' => $newStatus,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "Semester {$newStatus} successfully."
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling semester status', [
                'semester_id' => $semester->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Error updating status'], 500);
        }
    }

    /**
     * Get active semester for a school.
     */
    public function getActiveSemester(Request $request)
    {
        try {
            $user = Auth::user();
            $schoolId = $request->get('school_id', $user->school_id);
            
            // Check permissions
            if ($user->role !== 'admin' && $schoolId != $user->school_id) {
                Log::warning('Unauthorized active semester request', [
                    'user_id' => $user->id,
                    'requested_school_id' => $schoolId,
                    'user_school_id' => $user->school_id
                ]);
                
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $semester = Semester::where('school_id', $schoolId)
                ->where('status', 'active')
                ->with('school')
                ->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found for this school.'
                ]);
            }

            Log::info('Active semester retrieved', [
                'semester_id' => $semester->id,
                'school_id' => $schoolId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'semester' => $semester
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting active semester', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Error retrieving active semester'], 500);
        }
    }
}
