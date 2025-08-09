<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SemesterController extends Controller
{
    /**
     * Update an existing semester
     */
    public function update(Request $request, $id)
    {
        \Log::info('updateSemester method called', [
            'semester_id' => $id,
            'request_data' => $request->all()
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

            \Log::info('Validation passed for updateSemester');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('updateSemester validation failed', [
                'errors' => $e->errors(),
                'semester_id' => $id
            ]);
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please fix the validation errors and try again.');
        }

        $semester = Semester::findOrFail($id);
        
        \Log::info('Found semester to update', [
            'semester' => $semester->toArray()
        ]);

        // Calculate weekdays (Monday to Friday) between start and end date
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

        \Log::info('Calculated weekdays', ['weekdays' => $weekdays]);

        if ($weekdays < 5) {
            \Log::warning('Insufficient weekdays', [
                'weekdays' => $weekdays,
                'required_minimum' => 5
            ]);
            return redirect()->back()
                ->withErrors(['end_date' => "Semester must have at least 1 week (5 weekdays). Currently has {$weekdays} weekdays."])
                ->withInput()
                ->with('error', 'Semester duration too short.');
        }

         $warningMessage = '';
        if ($weekdays < 400) {
            $weeks = round($weekdays / 5, 1);
            $warningMessage = "Note: Current semester is {$weeks} weeks. Recommended duration is at least 80 weeks for a full academic semester.";
            \Log::info('Semester duration warning', [
                'weekdays' => $weekdays,
                'weeks' => $weeks,
                'recommended_weeks' => 80
            ]);
        }

         if ($request->status === 'active') {
            \Log::info('Checking for existing active semesters');
            
            $activeSemester = Semester::where('school_id', auth()->user()->school_id)
                ->where('status', 'active')
                ->where('id', '!=', $id) 
                ->first();

            if ($activeSemester) {
                \Log::warning('Found existing active semester', [
                    'existing_active' => $activeSemester->toArray()
                ]);
                return redirect()->back()
                    ->withErrors(['status' => "Cannot activate semester. '{$activeSemester->name}' is already active. Please deactivate it first."])
                    ->withInput()
                    ->with('error', 'Active semester conflict detected.');
            }
        }
        
         \Log::info('Checking for overlapping semesters');
        
        $overlapping = Semester::where('school_id', auth()->user()->school_id)
            ->where('id', '!=', $id) // Exclude current semester
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
            \Log::warning('Found overlapping semester', [
                'overlapping_semester' => $overlapping->toArray()
            ]);
            return redirect()->back()
                ->withErrors(['start_date' => "Date range conflicts with existing semester '{$overlapping->name}' ({$overlapping->start_date} to {$overlapping->end_date})."])
                ->withInput()
                ->with('error', 'Date range overlap detected.');
        }
        
        // Validate time ranges
        if ($request->am_time_in_start >= $request->am_time_in_end) {
            \Log::warning('Invalid AM time range');
            return redirect()->back()
                ->withErrors(['am_time_in_end' => 'AM time in end must be after start time.'])
                ->withInput()
                ->with('error', 'Invalid time range detected.');
        }
        
        if ($request->pm_time_out_start >= $request->pm_time_out_end) {
            \Log::warning('Invalid PM time range');
            return redirect()->back()
                ->withErrors(['pm_time_out_end' => 'Afternoon time out end must be after start time.'])
                ->withInput()
                ->with('error', 'Invalid time range detected.');
        }
        
        \Log::info('All validations passed, preparing update data');
        
        // Prepare update data with explicit time formatting
        try {
            $updateData = [
                'name' => $request->name,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'am_time_in_start' => Carbon::createFromFormat('H:i', $request->am_time_in_start)->format('H:i:s'),
                'am_time_in_end' => Carbon::createFromFormat('H:i', $request->am_time_in_end)->format('H:i:s'),
                'pm_time_out_start' => Carbon::createFromFormat('H:i', $request->pm_time_out_start)->format('H:i:s'),
                'pm_time_out_end' => Carbon::createFromFormat('H:i', $request->pm_time_out_end)->format('H:i:s'),
            ];
            
            \Log::info('Successfully prepared update data', ['update_data' => $updateData]);
        } catch (\Exception $e) {
            \Log::error('Error formatting time values', [
                'error' => $e->getMessage(),
                'raw_times' => [
                    'am_time_in_start' => $request->am_time_in_start,
                    'am_time_in_end' => $request->am_time_in_end,
                    'pm_time_out_start' => $request->pm_time_out_start,
                    'pm_time_out_end' => $request->pm_time_out_end,
                ]
            ]);
            throw $e;
        }
        
        // Log before update
        \Log::info('About to update semester', [
            'semester_id' => $semester->id,
            'current_data' => $semester->toArray(),
            'new_data' => $updateData
        ]);
        
        // Update semester
        try {
            $result = $semester->update($updateData);
            
            \Log::info('Update operation completed', [
                'update_success' => $result,
                'semester_id' => $semester->id
            ]);
            
            // Get fresh data to verify update
            $freshSemester = $semester->fresh();
            \Log::info('Fresh semester data after update', [
                'updated_semester' => $freshSemester->toArray()
            ]);
            
            if (!$result) {
                \Log::error('Update operation returned false', [
                    'semester_id' => $semester->id,
                    'update_data' => $updateData
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to update semester. Please try again.');
            }
            
        } catch (\Exception $e) {
            \Log::error('Database update failed', [
                'error' => $e->getMessage(),
                'semester_id' => $semester->id,
                'update_data' => $updateData,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Database error occurred: ' . $e->getMessage());
        }

        \Log::info('updateSemester completed successfully, redirecting');
        
        $successMessage = "Semester updated successfully";
        if (!empty($warningMessage)) {
            $successMessage .= " " . $warningMessage;
        }
        
        return redirect()->route('teacher.semesters')->with('success', $successMessage);
    }

    /**
     * Delete a semester
     */
    public function destroy($id)
    {
        try {
            $semester = Semester::findOrFail($id);
            
            // Get counts for confirmation
            $studentCount = Student::where('semester_id', $id)->count();
            $attendanceCount = Attendance::where('semester_id', $id)->count();
            
            if ($studentCount > 0 || $attendanceCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete semester with existing students or attendance records.',
                    'student_count' => $studentCount,
                    'attendance_count' => $attendanceCount
                ], 422);
            }
            
            $semester->delete();
            
            return redirect()->route('teacher.semesters')
                ->with('success', 'Semester deleted successfully.');
                
        } catch (\Exception $e) {
            \Log::error('Error deleting semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('teacher.semesters')
                ->with('error', 'Failed to delete semester: ' . $e->getMessage());
        }
    }
}
