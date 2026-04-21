<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\School;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\ValidatesForResponse;

class SchoolYearController extends Controller
{
    use ValidatesForResponse;
    public function index()
    {
        try {
            Log::info('School Year index accessed', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role ?? 'unknown'
            ]);

            $user = Auth::user();
            
            if ($user->role === 'admin') {
                $schoolYears = SchoolYear::with('school')
                    ->orderBy('school_year_start', 'desc')
                    ->paginate(10);
                    
                $schools = School::orderBy('name')->get();
                
                $sections = \App\Models\Section::with(['schoolYear', 'teacher', 'students'])
                    ->orderBy('gradelevel')
                    ->orderBy('name')
                    ->get();
                
                $teachers = \App\Models\User::where('role', 'teacher')
                    ->orderBy('name')
                    ->get();
                    
                Log::info('Admin viewing all school years', [
                    'user_id' => $user->id,
                    'school_years_count' => $schoolYears->count(),
                    'sections_count' => $sections->count()
                ]);

                return view('admin.manage-school-years', compact('schoolYears', 'schools', 'sections', 'teachers'));
            } else {
                $schoolYears = SchoolYear::with('school')
                    ->where('school_id', $user->school_id)
                    ->orderBy('school_year_start', 'desc')
                    ->paginate(10);
                    
                Log::info('Teacher viewing school years', [
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'school_years_count' => $schoolYears->count()
                ]);

                $sections = \App\Models\Section::where('teacher_id', $user->id)
                    ->with(['schoolYear'])
                    ->orderBy('gradelevel')
                    ->orderBy('name')
                    ->get();

                return view('teacher.school-year', compact('schoolYears', 'sections'));
            }
            
        } catch (\Exception $e) {
            Log::error('Error in school year index', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error loading school years: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            Log::info('School Year create form accessed', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role ?? 'unknown'
            ]);

            $user = Auth::user();
            
            if ($user->role !== 'admin') {
                Log::warning('Non-admin tried to access school year create form', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
                
                return redirect()->back()->with('error', 'Only administrators can create school years.');
            }
            
            $schools = School::orderBy('name')->get();

            return view('admin.school-year-create', compact('schools'));
            
        } catch (\Exception $e) {
            Log::error('Error in school year create form', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Error loading create form: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('School Year store attempt', [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $validated = $this->validateForResponse($request, [
                'name' => 'nullable|string|max:255',
                'school_id' => 'required|exists:schools,id',
                'school_year_start' => 'required|integer|min:2020|max:2100',
                'school_year_end' => 'required|integer|gt:school_year_start|max:2100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'description' => 'nullable|string|max:1000',
            ]);

            if (is_object($validated)) {  
                return $validated;
            }

            $user = Auth::user();
            if ($user->role !== 'admin' && $validated['school_id'] != $user->school_id) {
                Log::warning('Unauthorized school year creation attempt', [
                    'user_id' => $user->id,
                    'user_school_id' => $user->school_id,
                    'requested_school_id' => $validated['school_id']
                ]);
                
                return redirect()->back()->with('error', 'You can only create school years for your school.');
            }

            if (!isset($validated['name']) || empty($validated['name'])) {
                $validated['name'] = $validated['school_year_start'] . '–' . $validated['school_year_end'];
            }

            $validated['status'] = 'active';

            if ($validated['status'] === 'active') {
                SchoolYear::where('school_id', $validated['school_id'])
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
                    
                Log::info('Deactivated other school years in school', [
                    'school_id' => $validated['school_id'],
                    'user_id' => $user->id
                ]);
            }

            $schoolYear = SchoolYear::create($validated);

            Log::info('School Year created successfully', [
                'school_year_id' => $schoolYear->id,
                'school_year_name' => $schoolYear->name,
                'school_id' => $schoolYear->school_id,
                'user_id' => $user->id
            ]);

            return redirect()->route('admin.manage-school-years')->with('success', 'School Year created successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('School Year validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);
            
            return redirect()->back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error creating school year', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error creating school year: ' . $e->getMessage())->withInput();
        }
    }

    public function getActiveSchoolYear(Request $request)
    {
        try {
            $user = Auth::user();
            $schoolId = $request->get('school_id', $user->school_id);
            
            if ($user->role !== 'admin' && $schoolId != $user->school_id) {
                Log::warning('Unauthorized active school year request', [
                    'user_id' => $user->id,
                    'requested_school_id' => $schoolId,
                    'user_school_id' => $user->school_id
                ]);
                
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $schoolYear = SchoolYear::where('school_id', $schoolId)
                ->where('status', 'active')
                ->with('school')
                ->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active school year found for this school.'
                ]);
            }

            Log::info('Active school year retrieved', [
                'school_year_id' => $schoolYear->id,
                'school_id' => $schoolId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'schoolYear' => $schoolYear
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting active school year', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Error retrieving active school year'], 500);
        }
    }

    public function edit(SchoolYear $schoolYear)
    {
        try {
            $this->authorize('view', $schoolYear);

            Log::info('School Year edit accessed', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id()
            ]);

            return response()->json($schoolYear);
        } catch (\Exception $e) {
            Log::error('Error loading school year for edit', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'School year not found'], 404);
        }
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        try {
            Log::info('School Year update attempt', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $this->authorize('view', $schoolYear);

            $validated = $this->validateForResponse($request, [
                'school_id' => 'required|exists:schools,id',
                'school_year_start' => 'required|integer|min:2020|max:2100',
                'school_year_end' => 'required|integer|gt:school_year_start|max:2100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:active,inactive',
                'description' => 'nullable|string|max:1000',
            ]);

            if (is_object($validated)) {
                return $validated;
            }

            $user = Auth::user();
            if ($user->role !== 'admin' && $schoolYear->school_id != $user->school_id) {
                Log::warning('Unauthorized school year update attempt', [
                    'user_id' => $user->id,
                    'school_year_id' => $schoolYear->id,
                    'school_year_school_id' => $schoolYear->school_id,
                    'user_school_id' => $user->school_id
                ]);

                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if ($validated['status'] === 'active' && $schoolYear->status !== 'active') {
                SchoolYear::where('school_id', $validated['school_id'])
                    ->where('id', '!=', $schoolYear->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                Log::info('Deactivated other school years in school', [
                    'school_id' => $validated['school_id'],
                    'current_school_year_id' => $schoolYear->id
                ]);
            }

            $validated['name'] = $validated['school_year_start'] . '–' . $validated['school_year_end'];

            $schoolYear->update($validated);

            Log::info('School Year updated successfully', [
                'school_year_id' => $schoolYear->id,
                'school_year_name' => $schoolYear->name,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'School year updated successfully',
                'schoolYear' => $schoolYear
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('School Year update validation failed', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);

            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating school year', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Error updating school year: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(SchoolYear $schoolYear)
    {
        try {
            $this->authorize('view', $schoolYear);

            Log::info('School Year delete attempt', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id()
            ]);

            $user = Auth::user();
            if ($user->role !== 'admin' && $schoolYear->school_id != $user->school_id) {
                Log::warning('Unauthorized school year deletion attempt', [
                    'user_id' => $user->id,
                    'school_year_id' => $schoolYear->id
                ]);

                return redirect()->back()->with('error', 'Unauthorized action.');
            }

            $schoolYear->delete();

            Log::info('School Year deleted successfully', [
                'school_year_id' => $schoolYear->id,
                'user_id' => $user->id
            ]);

            return redirect()->route('admin.manage-school-years')->with('success', 'School year deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting school year', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error deleting school year: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, SchoolYear $schoolYear)
    {
        try {
            Log::info('School Year toggle status attempt', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id()
            ]);

            $this->authorize('view', $schoolYear);
            $user = Auth::user();

            if ($user->role !== 'admin' && $schoolYear->school_id != $user->school_id) {
                Log::warning('Unauthorized school year status toggle attempt', [
                    'user_id' => $user->id,
                    'school_year_id' => $schoolYear->id
                ]);

                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $newStatus = $schoolYear->status === 'active' ? 'inactive' : 'active';

            if ($newStatus === 'active') {
                SchoolYear::where('school_id', $schoolYear->school_id)
                    ->where('id', '!=', $schoolYear->id)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                Log::info('Deactivated other school years in school', [
                    'school_id' => $schoolYear->school_id,
                    'current_school_year_id' => $schoolYear->id
                ]);
            }

            $oldStatus = $schoolYear->status;
            $schoolYear->update(['status' => $newStatus]);

            Log::info('School Year status toggled successfully', [
                'school_year_id' => $schoolYear->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "School year status changed to {$newStatus}",
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling school year status', [
                'school_year_id' => $schoolYear->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error toggling status: ' . $e->getMessage()], 500);
        }
    }
}
