<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\User;
use App\Models\OutboundMessage;
use App\Http\Controllers\MessageApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceSessionController extends Controller
{
    
    public function createSession(Request $request)
    {
        try {
            Log::info('Daily attendance session creation request', [
                'teacher_id' => Auth::id(),
                'request_data' => $request->all(),
                'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);

             $teacherId = Auth::id();
            $user = Auth::user();

            if (!$user || !$user->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to a school. Please contact administrator.'
                ], 400);
            }

            
            $today = Carbon::now('Asia/Manila');
            $currentSemester = Semester::where('school_id', $user->school_id)
                ->whereDate('start_date', '<=', $today->format('Y-m-d'))
                ->whereDate('end_date', '>=', $today->format('Y-m-d'))
                ->first();

            if (!$currentSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found for today. Please contact administrator.'
                ], 404);
            }

            
             $existingSession = AttendanceSession::where('teacher_id', $teacherId)
                                                ->where('semester_id', $currentSemester->id)
                                                ->where('status', 'active')
                                                ->first();
            
            $isNewSession = !$existingSession;
            $session = AttendanceSession::createDailySession($teacherId, $currentSemester->id);

            $message = $isNewSession ? 
                'Daily attendance session created successfully!' : 
                'Retrieved existing daily attendance session!';

            Log::info('Daily attendance session ' . ($isNewSession ? 'created' : 'retrieved'), [
                'session_id' => $session->id,
                'teacher_id' => $teacherId,
                'semester_id' => $currentSemester->id,
                'session_name' => $session->session_name,
                'is_new_session' => $isNewSession,
                'expires_at' => 'Never (Permanent)',
                'public_url' => $session->getPublicUrl()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'session' => [
                    'id' => $session->id,
                    'token' => $session->session_token,
                    'name' => $session->session_name,
                    'public_url' => $session->getPublicUrl(),
                    'type' => 'daily',
                    'expires_at' => 'Never (Permanent Daily Link)',
                    'semester_name' => $currentSemester->name,
                    'access_hours' => 'Always Available (Recording based on time periods)'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create daily attendance session', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create attendance session. Error: ' . $e->getMessage()
            ], 500);
        }
    }

    
    private function createDailySession($teacherId, $semesterId)
    {
        $now = Carbon::now('Asia/Manila');
        $today = $now->copy()->startOfDay();
        
        
        $existingSession = AttendanceSession::where('teacher_id', $teacherId)
            ->where('semester_id', $semesterId)
            ->where('status', 'active')
            ->whereDate('started_at', $today)
            ->first();
            
        if ($existingSession) {
            return $existingSession;
        }

        
        
        $closedCount = AttendanceSession::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->update([
                'status' => 'closed',
                'closed_at' => $now,
                'updated_at' => $now
            ]);

        
        if ($closedCount > 0) {
            Log::info('Automatically closed previous active sessions', [
                'teacher_id' => $teacherId,
                'closed_sessions_count' => $closedCount,
                'reason' => 'Creating new daily session'
            ]);
        }

        
        $token = $this->generateUniqueToken();

        
        $endOfDay = Carbon::today('Asia/Manila')->addDay(); 

        return AttendanceSession::create([
            'session_token' => $token,
            'teacher_id' => $teacherId,
            'semester_id' => $semesterId,
            'session_name' => 'Daily Attendance - ' . $today->format('M j, Y'),
            'status' => 'active',
            'expires_at' => $endOfDay, 
            'started_at' => $now,
            'last_activity_at' => $now,
            'access_count' => 0,
            'attendance_count' => 0,
            'access_log' => []
        ]);
    }

    
    private function generateUniqueToken()
    {
        do {
            $token = \Illuminate\Support\Str::random(32);
        } while (AttendanceSession::where('session_token', $token)->exists());
        
        return $token;
    }

    
    private function expireOldSessions($teacherId)
    {
        
        
        
        Log::info('Session expiry skipped - using permanent daily links', [
            'teacher_id' => $teacherId,
            'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
        ]);
    }

    
    private function isWithinAccessHours()
    {
        $now = Carbon::now('Asia/Manila');
        $startTime = $now->copy()->setTime(5, 0, 0); 
        
        $endTime = $now->copy()->setTime(20, 0, 0);  

        return $now->between($startTime, $endTime);
    }

    
    public function getActiveSessions()
    {
        try {
            $teacherId = Auth::id();
            
            $sessions = AttendanceSession::with('semester')
                ->where('teacher_id', $teacherId)
                ->where('status', 'active')
                ->whereDate('started_at', Carbon::today('Asia/Manila'))
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'sessions' => $sessions->map(function($session) {
                    return [
                        'id' => $session->id,
                        'token' => $session->session_token,
                        'name' => $session->session_name,
                        'public_url' => $session->getPublicUrl(),
                        'expires_at' => 'Never (Permanent)',
                        'created_at' => $session->created_at->format('M j, Y g:i A'),
                        'access_count' => $session->access_count,
                        'attendance_count' => $session->attendance_count,
                        'semester_name' => $session->semester->name ?? 'Unknown',
                        'access_hours' => 'Always Available',
                        'date' => $session->started_at->format('M j, Y')
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get active sessions', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sessions.'
            ], 500);
        }
    }

    
    public function closeSession(Request $request, $sessionId)
    {
        try {
            $teacherId = Auth::id();
            
            $session = AttendanceSession::where('id', $sessionId)
                ->where('teacher_id', $teacherId)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found.'
                ], 404);
            }

            
            $session->close();

            Log::info('Attendance session closed manually', [
                'session_id' => $session->id,
                'teacher_id' => $teacherId,
                'session_name' => $session->session_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session closed successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to close session', [
                'session_id' => $sessionId,
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close session.'
            ], 500);
        }
    }

    
    public function sessions()
    {
        $teacherId = Auth::id();
        
        $activeSessions = AttendanceSession::with('semester')
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->whereDate('started_at', Carbon::today('Asia/Manila'))
            ->orderBy('created_at', 'desc')
            ->get();
                                         
        $recentSessions = AttendanceSession::with('semester')
            ->where('teacher_id', $teacherId)
            ->where('status', '!=', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
                                         
        $semesters = Semester::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get();
        
        return view('teacher.sessions', compact('activeSessions', 'recentSessions', 'semesters'));
    }

    
    public function publicAttendance($token)
    {
        try {
            $session = AttendanceSession::with('teacher', 'semester')->where('session_token', $token)->first();

            if (!$session) {
                Log::warning('Invalid attendance session token accessed', [
                    'token' => $token,
                    'ip_address' => request()->ip()
                ]);
                return view('public.attendance-error', [
                    'error' => 'Invalid attendance session link.'
                ]);
            }

            if ($session->status !== 'active') {
                Log::info('Inactive attendance session accessed', [
                    'session_id' => $session->id,
                    'token' => $token,
                    'ip_address' => request()->ip()
                ]);
                return view('public.attendance-error', [
                    'error' => 'This attendance session is not active.'
                ]);
            }

            
            $now = Carbon::now('Asia/Manila');
            $today = $now->format('Y-m-d');
            $sessionDate = $session->started_at ? $session->started_at->format('Y-m-d') : null;
            
            
            if ($sessionDate && $sessionDate < $today) {
                $session->update(['status' => 'expired']);
                
                Log::info('Expired attendance session accessed', [
                    'session_id' => $session->id,
                    'session_date' => $sessionDate,
                    'current_date' => $today,
                    'token' => $token,
                    'ip_address' => request()->ip()
                ]);
                
                return view('public.attendance-error', [
                    'error' => 'This attendance session has expired. Sessions are valid for one day only.',
                    'session_date' => $sessionDate,
                    'current_date' => $today
                ]);
            }

            
            $currentHour = $now->hour;
            if ($currentHour < 5 || $currentHour >= 18) {
                Log::info('Attendance session accessed outside allowed hours', [
                    'session_id' => $session->id,
                    'current_hour' => $currentHour,
                    'token' => $token,
                    'ip_address' => request()->ip()
                ]);
                
                return view('public.attendance-error', [
                    'error' => 'Attendance session is only accessible between 5:00 AM and 6:00 PM.',
                    'current_time' => $now->format('g:i A')
                ]);
            }

            
            $session->logAccess(request()->ip(), request()->header('User-Agent'));

            $semester = $session->semester;
            $students = Student::with('user')
                ->where('user_id', $session->teacher_id)
                ->where('semester_id', $session->semester_id)
                ->orderBy('name')
                ->get();

             $recentAttendance = Attendance::with(['student', 'student.user'])
                ->whereHas('student', function($query) use ($session) {
                    $query->where('user_id', $session->teacher_id)
                          ->where('semester_id', $session->semester_id);
                })
                ->whereDate('created_at', Carbon::today('Asia/Manila'))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            
            $periodInfo = $this->getCurrentPeriodInfo($semester);

            Log::info('Public attendance page accessed', [
                'session_id' => $session->id,
                'session_name' => $session->session_name,
                'ip_address' => request()->ip(),
                'access_count' => $session->access_count + 1,
                'period_info' => $periodInfo,
                
            ]);

            return view('public.attendance', [
                'session' => $session,
                'semester' => $semester,
                'students' => $students,
                'teacher_name' => $session->teacher->name ?? 'Unknown',
                'recentAttendance' => $recentAttendance,
                'period_info' => $periodInfo,
                'current_time' => Carbon::now('Asia/Manila')->timestamp * 1000,
                
            ]);

        } catch (\Exception $e) {
            Log::error('Error accessing public attendance page', [
                'token' => $token,
                'error' => $e->getMessage(),
                'ip_address' => request()->ip()
            ]);

            return view('public.attendance-error', [
                'error' => 'An error occurred while loading the attendance page.'
            ]);
        }
    }

    
    private function getCurrentPeriodInfo($semester)
    {
        $now = Carbon::now('Asia/Manila');
        $currentTimeMinutes = $now->hour * 60 + $now->minute;

        $amStart = $semester->am_time_in_start ? Carbon::createFromFormat('H:i:s', $semester->am_time_in_start) : Carbon::createFromFormat('H:i', '07:00');
        $amEnd = $semester->am_time_in_end ? Carbon::createFromFormat('H:i:s', $semester->am_time_in_end) : Carbon::createFromFormat('H:i', '07:30');
        
        
        $pmStart = $semester->pm_time_out_start ? Carbon::createFromFormat('H:i:s', $semester->pm_time_out_start) : Carbon::createFromFormat('H:i', '16:30');
        $pmEnd = $semester->pm_time_out_end ? Carbon::createFromFormat('H:i:s', $semester->pm_time_out_end) : Carbon::createFromFormat('H:i', '17:00');

        $amStartMinutes = $amStart->hour * 60 + $amStart->minute;
        $amEndMinutes = $amEnd->hour * 60 + $amEnd->minute;
        $pmStartMinutes = $pmStart->hour * 60 + $pmStart->minute;
        $pmEndMinutes = $pmEnd->hour * 60 + $pmEnd->minute;

        
        if ($currentTimeMinutes >= $amStartMinutes && $currentTimeMinutes <= $amEndMinutes) {
            return [
                'allowed' => true,
                'period_name' => 'AM Period (Time In/Out)',
                'period_type' => 'both',
                'start_time' => $amStart->format('g:i A'),
                'end_time' => $amEnd->format('g:i A'),
                'time_remaining' => max(0, $amEndMinutes - $currentTimeMinutes)
            ];
        }

        
        if ($currentTimeMinutes >= $pmStartMinutes && $currentTimeMinutes <= $pmEndMinutes) {
            return [
                'allowed' => true,
                'period_name' => 'PM Period (Time In/Out)',
                'period_type' => 'both',
                'start_time' => $pmStart->format('g:i A'),
                'end_time' => $pmEnd->format('g:i A'),
                'time_remaining' => max(0, $pmEndMinutes - $currentTimeMinutes)
            ];
        }

         $nextPeriod = null;
        if ($currentTimeMinutes < $amStartMinutes) {
            $nextPeriod = [
                'period_name' => 'AM Period (Time In/Out)',
                'period_type' => 'both',
                'start_time' => $amStart->format('g:i A')
            ];
        } elseif ($currentTimeMinutes < $pmStartMinutes) {
            $nextPeriod = [
                'period_name' => 'PM Period (Time In/Out)',
                'period_type' => 'both',
                'start_time' => $pmStart->format('g:i A')
            ];
        }

        return [
            'allowed' => false,
            'period_name' => 'Outside Recording Hours',
            'period_type' => 'none',
            'next_period' => $nextPeriod
        ];
    }

    
    private function logSessionAccess($session)
    {
        $accessLog = $session->access_log ?? [];
        $accessLog[] = [
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
        ];

        
        $session->update([
            'access_count' => $session->access_count + 1,
            'access_log' => array_slice($accessLog, -50), 
            'last_activity_at' => Carbon::now('Asia/Manila')
        ]);
    }

    
    public function checkSessionStatus($token)
    {
        try {
            $session = AttendanceSession::where('session_token', $token)->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'status' => 'invalid',
                    'message' => 'Session not found.'
                ]);
            }

            if ($session->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'status' => 'inactive',
                    'message' => 'Session is not active.'
                ]);
            }

            
            $periodInfo = $this->getCurrentPeriodInfo($session->semester);
            
            $now = Carbon::now('Asia/Manila');

            return response()->json([
                'success' => true,
                'status' => 'active',
                'period_info' => $periodInfo,
                'current_time' => $now->timestamp * 1000,
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking session status', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error checking session status.'
            ], 500);
        }
    }

    
    public function publicQrVerify(Request $request, $token)
    {
        try {
            // Log initial request details
            Log::info('Public QR verification started', [
                'session_token' => $token,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'request_data' => $request->all(),
                'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);

            $request->validate(['qr_data' => 'required|string']);

            $session = AttendanceSession::where('session_token', $token)->first();

            if (!$session) {
                Log::warning('Session not found for public QR verification', [
                    'session_token' => $token,
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found.'
                ]);
            }

            Log::info('Session found for public QR verification', [
                'session_id' => $session->id,
                'session_name' => $session->session_name,
                'teacher_id' => $session->teacher_id,
                'semester_id' => $session->semester_id,
                'session_status' => $session->status,
                'ip_address' => request()->ip()
            ]);

            
            if ($session->status !== 'active') {
                Log::warning('Inactive session accessed for public QR verification', [
                    'session_id' => $session->id,
                    'session_status' => $session->status,
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'This session is not active.',
                    'status' => 'session_inactive'
                ]);
            }

            
            $qrData = $request->qr_data;
            
            Log::info('QR data received for processing', [
                'session_id' => $session->id,
                'qr_data' => $qrData,
                'qr_data_length' => strlen($qrData),
                'qr_data_type' => gettype($qrData),
                'ip_address' => request()->ip()
            ]);
            
            
            if (empty($qrData) || strlen($qrData) < 3) {
                Log::warning('Invalid QR data format in public session', [
                    'session_id' => $session->id,
                    'qr_data' => $qrData,
                    'qr_data_length' => strlen($qrData),
                    'qr_data_empty' => empty($qrData),
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format.'
                ]);
            }

            Log::info('Starting student lookup', [
                'session_id' => $session->id,
                'teacher_id' => $session->teacher_id,
                'semester_id' => $session->semester_id,
                'stud_code' => $qrData,
                'ip_address' => request()->ip()
            ]);

             $student = Student::with('user') 
                ->where('user_id', $session->teacher_id)
                ->where('semester_id', $session->semester_id)
                ->where('stud_code', $qrData)
                ->whereNotNull('stud_code')
                ->where('stud_code', '!=', '')
                ->first();

            if (!$student) {
                // Try to find any student with this stud_code for debugging
                $anyStudent = Student::where('stud_code', $qrData)->first();
                
                Log::warning('Student not found in public session with stud_code', [
                    'session_id' => $session->id,
                    'teacher_id' => $session->teacher_id,
                    'semester_id' => $session->semester_id,
                    'stud_code' => $qrData,
                    'any_student_found' => $anyStudent ? true : false,
                    'any_student_teacher_id' => $anyStudent ? $anyStudent->user_id : null,
                    'any_student_semester_id' => $anyStudent ? $anyStudent->semester_id : null,
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found. Please check your QR code or contact your teacher.'
                ]);
            }

            Log::info('Student found and loaded successfully', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_id_no' => $student->id_no,
                'student_section' => $student->user ? $student->user->section_name : 'N/A',
                'stud_code' => $qrData,
                'ip_address' => request()->ip()
            ]);

            
            if (!$this->verifyStudentInfo($student, $session)) {
                Log::warning('Student verification failed in public session', [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_id_no' => $student->id_no,
                    'stud_code' => $qrData,
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Student verification failed. Please contact your teacher.'
                ]);
            }

            Log::info('Student verification passed', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'ip_address' => request()->ip()
            ]);

            
            $periodInfo = $this->getCurrentPeriodInfo($session->semester);
            
            Log::info('Period info retrieved', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'period_allowed' => $periodInfo['allowed'],
                'period_name' => $periodInfo['period_name'] ?? 'N/A',
                'period_type' => $periodInfo['period_type'] ?? 'N/A',
                'current_time' => Carbon::now('Asia/Manila')->format('H:i:s'),
                'ip_address' => request()->ip()
            ]);
            
            if (!$periodInfo['allowed']) {
                
                $message = 'Attendance recording is only allowed during scheduled periods: AM Period and PM Period (both Time In and Time Out allowed).';
                if (isset($periodInfo['next_period'])) {
                    $nextPeriod = $periodInfo['next_period'];
                    $message .= " Next period: {$nextPeriod['period_name']} at {$nextPeriod['start_time']}.";
                }
                
                Log::info('Student scanned outside recording period', [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_id_no' => $student->id_no,
                    'period_info' => $periodInfo,
                    'message' => $message,
                    'ip_address' => request()->ip(),
                    'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'status' => 'outside_recording_period',
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'id_no' => $student->id_no ?? $student->id,
                        'section' => $student->user ? $student->user->section_name : 'N/A',
                        'photo' => $student->picture
                    ],
                    'period_info' => $periodInfo,
                    'current_time' => Carbon::now('Asia/Manila')->format('g:i:s A')
                ]);
            }

            Log::info('Period validation passed, proceeding with attendance recording', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'period_name' => $periodInfo['period_name'],
                'period_type' => $periodInfo['period_type'],
                'ip_address' => request()->ip()
            ]);

            
            $attendanceController = new AttendanceController();
            $tempRequest = new Request();
            $tempRequest->merge([
                'qr_data' => $request->qr_data,
                'scanner_type' => $request->scanner_type ?? 'Daily Session - ' . $periodInfo['period_name']
            ]);

            Log::info('Calling AttendanceController for recording', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'teacher_id' => $session->teacher_id,
                'temp_request_data' => $tempRequest->all(),
                'ip_address' => request()->ip()
            ]);

            Auth::loginUsingId($session->teacher_id);
            $result = $attendanceController->verifyQrAndRecordAttendance($tempRequest);
            Auth::logout();

            $responseData = json_decode($result->getContent(), true);
            
            Log::info('AttendanceController response received', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'response_success' => $responseData['success'] ?? false,
                'response_message' => $responseData['message'] ?? 'N/A',
                'response_status' => $responseData['status'] ?? 'N/A',
                'ip_address' => request()->ip()
            ]);

            
            if ($responseData['success']) {
                $responseData['period_info'] = [
                    'period' => $periodInfo['period_name'],
                    'period_type' => $periodInfo['period_type'],
                    'time_remaining' => $periodInfo['time_remaining'],
                    'recorded_time' => Carbon::now('Asia/Manila')->format('g:i A')
                ];

                if (!empty($responseData['attendance_recorded'])) {
                    Log::info('Attendance recorded successfully, sending SMS notification', [
                        'session_id' => $session->id,
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'period_type' => $periodInfo['period_type'],
                        'recorded_time' => $responseData['period_info']['recorded_time'],
                        'contact_number' => $student->contact_person_contact,
                        'ip_address' => request()->ip()
                    ]);
                    // Send SMS notification to parent/guardian
                    $this->sendAttendanceNotification($student, $periodInfo['period_type'], $responseData['period_info']['recorded_time'], $session);
                }

                $session->increment('attendance_count');
                Log::info('Session attendance count incremented', [
                    'session_id' => $session->id,
                    'new_attendance_count' => $session->attendance_count + 1,
                    'ip_address' => request()->ip()
                ]);
            } else {
                Log::warning('Attendance recording failed', [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'failure_reason' => $responseData['message'] ?? 'Unknown error',
                    'response_data' => $responseData,
                    'ip_address' => request()->ip()
                ]);
            }

            
            Log::info('Final attendance processing result', [
                'session_id' => $session->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_id_no' => $student->id_no,
                'success' => $responseData['success'],
                'period' => $periodInfo['period_name'],
                'period_type' => $periodInfo['period_type'],
                'response_message' => $responseData['message'] ?? 'N/A',
                'ip_address' => request()->ip(),
                'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);

            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Critical error in public QR verification', [
                'token' => $token,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'ip_address' => request()->ip(),
                'timestamp' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing attendance.'
            ], 500);
        }
    }

    
    public function getTodaySession()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->school_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not assigned to a school.'
                ], 400);
            }

            
            $today = Carbon::today('Asia/Manila');
            $session = AttendanceSession::where('teacher_id', $user->id)
                ->whereDate('started_at', $today)
                ->with('semester')
                ->first();

            if ($session) {
                return response()->json([
                    'success' => true,
                    'session' => [
                        'id' => $session->id,
                        'name' => $session->name,
                        'public_url' => $session->public_url,
                        'date_created' => $session->started_at->format('M j, Y'),
                        'access_count' => $session->access_count ?? 0,
                        'semester_name' => $session->semester->name ?? 'Unknown',
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No session found for today.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error getting today session', [
                'error' => $e->getMessage(),
                'teacher_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving session data.'
            ], 500);
        }
    }

    
    private function verifyStudentInfo($student, $session)
    {
        
        if (empty($student->name) || empty($student->id_no)) {
            Log::warning('Student missing required information in public session', [
                'student_id' => $student->id,
                'session_id' => $session->id,
                'has_name' => !empty($student->name),
                'has_id_no' => !empty($student->id_no),
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        
        if (empty($student->stud_code)) {
            Log::warning('Student missing stud_code in public session', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'session_id' => $session->id,
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        
        $expectedPrefix = $student->id_no . '_';
        if (!str_starts_with($student->stud_code, $expectedPrefix)) {
            Log::warning('Student stud_code format invalid in public session', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'session_id' => $session->id,
                'stud_code' => $student->stud_code,
                'expected_prefix' => $expectedPrefix,
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        
        $expectedLength = strlen($student->id_no) + 1 + 10; 
        if (strlen($student->stud_code) !== $expectedLength) {
            Log::warning('Student stud_code length invalid in public session', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'session_id' => $session->id,
                'stud_code' => $student->stud_code,
                'stud_code_length' => strlen($student->stud_code),
                'expected_length' => $expectedLength,
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        
        if ($student->user_id !== $session->teacher_id) {
            Log::warning('Student does not belong to session teacher', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'session_id' => $session->id,
                'student_teacher_id' => $student->user_id,
                'session_teacher_id' => $session->teacher_id,
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        
        if ($student->semester_id !== $session->semester_id) {
            Log::warning('Student semester mismatch with session', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'session_id' => $session->id,
                'student_semester_id' => $student->semester_id,
                'session_semester_id' => $session->semester_id,
                'ip_address' => request()->ip(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send SMS notification for attendance recording
     */
    private function sendAttendanceNotification($student, $attendanceStatus, $recordedTime, $session = null)
    {
        try {
            if (!$student->contact_person_contact) {
                Log::info('No contact number for student, skipping SMS', [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
                return;
            }

            // Determine the teacher ID for the SMS record
            $teacherId = $session ? $session->teacher_id : $student->user_id;
            
            $smsService = new \App\Services\AndroidSmsGatewayService();
            $messageController = new MessageApiController($smsService);

            // Set the teacher ID in the MessageApiController for OutboundMessage creation
            $messageController->setTeacherId($teacherId);
            
            $success = $messageController->sendAttendanceNotification($student, $attendanceStatus, $recordedTime);

            if ($success) {
                Log::info('SMS notification sent for attendance', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'contact_number' => $student->contact_person_contact,
                    'attendance_status' => $attendanceStatus,
                    'teacher_id' => $teacherId,
                    'session_id' => $session ? $session->id : null
                ]);
            } else {
                Log::warning('SMS notification failed or skipped', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'contact_number' => $student->contact_person_contact,
                    'teacher_id' => $teacherId,
                    'session_id' => $session ? $session->id : null
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
 
    private function isValidPhoneNumber($number)
    {
        if (!$number) return false;
        
         $cleaned = preg_replace('/[\s\-]/', '', $number);
        
         if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return true;
        }
        
         if (preg_match('/^09\d{9}$/', $cleaned)) {
            return true;
        }
        
        return false;
    }

    
    public function getTimeSessions()
    {
        $user = auth()->user();
        $semester = null;

        if ($user) {
            if ($user->role === 'student') {
                 $student = \App\Models\Student::where('user_id', $user->id)->first();
                if ($student && $student->semester_id) {
                    $semester = Semester::find($student->semester_id);
                }
            } else {
                $semester = Semester::where('school_id', $user->school_id)
                    ->where('is_active', 1)
                    ->orderByDesc('start_date')
                    ->first();
            }
        }

        if (!$semester) {
            return response()->json(['error' => 'No active semester found for user.'], 404);
        }

        return response()->json([
            'am_time_in_start' => $semester->am_time_in_start,
            'am_time_in_end' => $semester->am_time_in_end,
            'pm_time_out_start' => $semester->pm_time_out_start,
            'pm_time_out_end' => $semester->pm_time_out_end,
            'start_date' => $semester->start_date,
            'end_date' => $semester->end_date,
        ]);
    }
}
