<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Semester;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class AttendanceController extends Controller
{
    public function verifyQrAndRecordAttendance(Request $request)
    {
         Log::info('QR scan request received', [
            'qr_data' => $request->input('qr_data'),
            'scanner_type' => $request->input('scanner_type'),
            'user_id' => Auth::id(),
        ]);

        $request->validate(['qr_data' => 'required|string']);
        $data = json_decode($request->qr_data, true);
        $scannerType = $this->detectScannerType($request);

        if (!$data || !isset($data['student_id'])) {
            Log::warning('Invalid QR code', [
                'scanner_type' => $scannerType,
                'qr_data' => $request->qr_data,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid QR code.']);
        }

        $student = Student::where('user_id', Auth::id())->find($data['student_id']);
        if (!$student) {
            Log::warning('Student not found', [
                'scanner_type' => $scannerType,
                'student_id' => $data['student_id'],
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Student not found.']);
        }

        $semester = Semester::find($student->semester_id);
        if (!$semester || $semester->status !== 'active') {
            Log::warning('No active semester', [
                'scanner_type' => $scannerType,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'semester_id' => $student->semester_id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['success' => false, 'message' => 'No active semester.']);
        }

        $attendance = Attendance::firstOrCreate([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'date' => Carbon::now()->toDateString(),
        ]);

        $now = Carbon::now();
        $time = $now->format('H:i:s');  
        
        $periods = [
            'AM Time In' => ['start' => $semester->am_time_in_start,  'end' => $semester->am_time_in_end,  'field' => 'time_in_am',  'requires' => null],
            'AM Time Out'=> ['start' => $semester->am_time_out_start,'end' => $semester->am_time_out_end,'field' => 'time_out_am','requires' => null],
            'PM Time In' => ['start' => $semester->pm_time_in_start,  'end' => $semester->pm_time_in_end,  'field' => 'time_in_pm',  'requires' => null],
            'PM Time Out'=> ['start' => $semester->pm_time_out_start,'end' => $semester->pm_time_out_end,'field' => 'time_out_pm','requires' => null],
        ];

        foreach ($periods as $label => $details) {
            if (!$details['start'] || !$details['end']) continue;

            $start = Carbon::createFromFormat('H:i:s', $details['start']);
            $end = Carbon::createFromFormat('H:i:s', $details['end']);

            if ($now->between($start, $end)) {
                if ($attendance->{$details['field']}) {
                    // Get the recorded time and format it safely
                    $existingTime = $attendance->{$details['field']};
                    if ($existingTime) {
                        try {
                            $recordedTime = Carbon::createFromFormat('H:i:s', $existingTime)->format('g:i:s A');
                        } catch (Exception $e) {
                            // Fallback if format fails
                            $recordedTime = $existingTime;
                        }
                    } else {
                        $recordedTime = 'Unknown time';
                    }
                    
                    Log::info('Duplicate scan', [
                        'scanner_type' => $scannerType,
                        'student_name' => $student->name,
                        'student_id_no' => $student->id_no,
                        'period' => $label,
                        'already_recorded_time' => $recordedTime,
                    ]);
                    
                    return response()->json([
                        'success' => true, 
                        'message' => "$label already recorded at " . $recordedTime, 
                        'period' => $label,
                        'student' => [
                            'id_no' => $student->id_no,
                            'name' => $student->name,
                            'picture' => $student->picture,
                            'section' => $student->section ?? 'No Section',
                            'semester' => $semester->name ?? "Semester {$student->semester_id}",
                        ],
                        'status' => "$label already recorded!",
                        'time_period' => $label,
                        'recorded_time' => $recordedTime,
                    ]); 
                }

                $attendance->{$details['field']} = $time;
                $attendance->save();

                // Record session activity if there's an active session
                $activeSession = AttendanceSession::where('teacher_id', Auth::id())
                                                 ->active()
                                                 ->first();
                if ($activeSession) {
                    $activeSession->recordAttendance();
                }

                Log::info('Attendance recorded', [
                    'scanner_type' => $scannerType,
                    'student_name' => $student->name,
                    'student_id_no' => $student->id_no,
                    'period' => $label,
                    'recorded_time' => $now->format('g:i:s A'),
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'student' => [
                        'id_no' => $student->id_no,
                        'name' => $student->name,
                        'picture' => $student->picture,
                        'section' => $student->section ?? 'No Section',
                        'semester' => $semester->name ?? "Semester {$student->semester_id}",
                    ],
                    'status' => "$label recorded successfully!",
                    'time_period' => $label,
                    'recorded_time' => $now->format('g:i:s A'), // Display in 12-hour format
                ]);
            }
        }

        Log::warning('Scan outside time period', [
            'scanner_type' => $scannerType,
            'student_name' => $student->name,
            'current_time' => $now->format('g:i:s A'),
            'user_id' => Auth::id(),
        ]);

        return response()->json(['success' => false, 'message' => 'Attendance not allowed at this time.']);
    }
   
    private function detectScannerType(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        $referer = $request->header('Referer', '');
        
         if ($request->has('scanner_type')) {
            return $request->input('scanner_type');
        }
        
         if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            return 'Mobile Camera';
        }
        
         return 'Webcam/USB Scanner';
    }
}
