<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\SchoolYear;

class StudentDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        \Log::info('=== StudentDashboardController::dashboard() CALLED ===', [
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'session_data' => $request->session()->all()
        ]);

        $student = Auth::user();

        \Log::info('Student loaded', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_class' => class_basename(get_class($student))
        ]);

         $school = $student->school;

         $section = $student->section;
                 $schoolYear = $student->schoolYear ?? SchoolYear::getCurrentSchoolYear($student->school_id);

           $today = Carbon::today();
        $todayAttendance = $student->attendances()
            ->where('date', $today)
            ->first();

        $attendanceCharts = $this->buildAttendanceCharts($student);
        $attendanceSummary = $this->buildAttendanceSummary($student);
                $attendanceDateRange = $this->buildAttendanceDateRange($student, $schoolYear);

        \Log::info('Dashboard data loaded successfully', [
            'has_school' => !is_null($school),
            'has_section' => !is_null($section),
            'has_today_attendance' => !is_null($todayAttendance)
        ]);

        return view('student.dashboard', [
            'student' => $student,
            'school' => $school,
            'section' => $section,
            'schoolYear' => $schoolYear,
            'todayAttendance' => $todayAttendance,
            'attendanceCharts' => $attendanceCharts,
            'attendanceSummary' => $attendanceSummary,
            'attendanceDateRange' => $attendanceDateRange,
        ]);
    }

    public function attendance(Request $request)
    {
        $student = Auth::user();

        $query = $student->attendances()
            ->orderBy('date', 'desc');

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $attendances = $query->paginate(15);

        return view('student.attendance', [
            'student' => $student,
            'attendances' => $attendances,
        ]);
    }

    public function account()
    {
        $student = Auth::user();
        return view('student.account', [
            'student' => $student,
            'requiresPasswordChange' => $student->usesDefaultPassword(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $student = Auth::user();
        $requiresPasswordChange = $student->usesDefaultPassword();

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if (! $requiresPasswordChange) {
            $rules['current_password'] = 'required|string';
        }

        $request->validate($rules);

        if (! $requiresPasswordChange && !\Hash::check($request->current_password, $student->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $student->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    private function buildAttendanceCharts($student): array
    {
        $today = Carbon::today();
        $todayAttendance = $student->attendances()
            ->whereDate('date', $today)
            ->first();

        return [
            'today' => $this->buildTodayTimelineChart($todayAttendance),
            'weekly' => $this->buildTrendChart($student, Carbon::today()->subDays(6), Carbon::today(), 'weekly'),
            'monthly' => $this->buildTrendChart($student, Carbon::today()->subDays(29), Carbon::today(), 'monthly'),
        ];
    }

    private function buildTrendChart($student, Carbon $startDate, Carbon $endDate, string $format): array
    {
        $attendances = $student->attendances()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->date->toDateString());

        $labels = [];
        $amInValues = [];
        $amOutValues = [];
        $pmInValues = [];
        $pmOutValues = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (in_array($format, ['weekly', 'monthly'], true) && $date->isWeekend()) {
                continue;
            }

            $key = $date->toDateString();
            $attendance = $attendances->get($key);

            $labels[] = $format === 'weekly'
                ? $date->format('D')
                : $date->format('M j');

            $amInValues[] = $this->attendanceTimeToMinutes($attendance?->time_in_am);
            $amOutValues[] = $this->attendanceTimeToMinutes($attendance?->time_out_am);
            $pmInValues[] = $this->attendanceTimeToMinutes($attendance?->time_in_pm);
            $pmOutValues[] = $this->attendanceTimeToMinutes($attendance?->time_out_pm);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'AM In',
                    'data' => $amInValues,
                    'borderColor' => 'rgba(13, 110, 253, 1)',
                    'backgroundColor' => 'rgba(13, 110, 253, 0.12)',
                    'fill' => false,
                    'tension' => 0.25,
                    'pointBackgroundColor' => 'rgba(13, 110, 253, 1)',
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'AM Out',
                    'data' => $amOutValues,
                    'borderColor' => 'rgba(25, 135, 84, 1)',
                    'backgroundColor' => 'rgba(25, 135, 84, 0.12)',
                    'fill' => false,
                    'tension' => 0.25,
                    'pointBackgroundColor' => 'rgba(25, 135, 84, 1)',
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'PM In',
                    'data' => $pmInValues,
                    'borderColor' => 'rgba(255, 193, 7, 1)',
                    'backgroundColor' => 'rgba(255, 193, 7, 0.12)',
                    'fill' => false,
                    'tension' => 0.25,
                    'pointBackgroundColor' => 'rgba(255, 193, 7, 1)',
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'PM Out',
                    'data' => $pmOutValues,
                    'borderColor' => 'rgba(220, 53, 69, 1)',
                    'backgroundColor' => 'rgba(220, 53, 69, 0.12)',
                    'fill' => false,
                    'tension' => 0.25,
                    'pointBackgroundColor' => 'rgba(220, 53, 69, 1)',
                    'pointRadius' => 3,
                ],
            ],
        ];
    }

    private function buildTodayTimelineChart($attendance): array
    {
        return [
            'labels' => ['AM In', 'AM Out', 'PM In', 'PM Out'],
            'datasets' => [[
                'label' => 'Today',
                'data' => [
                    $this->attendanceTimeToMinutes($attendance?->time_in_am),
                    $this->attendanceTimeToMinutes($attendance?->time_out_am),
                    $this->attendanceTimeToMinutes($attendance?->time_in_pm),
                    $this->attendanceTimeToMinutes($attendance?->time_out_pm),
                ],
                'borderColor' => 'rgba(13, 110, 253, 1)',
                'backgroundColor' => 'rgba(13, 110, 253, 0.12)',
                'fill' => false,
                'tension' => 0.3,
                'pointBackgroundColor' => 'rgba(13, 110, 253, 1)',
                'pointRadius' => 5,
                'pointHoverRadius' => 7,
            ]],
        ];
    }

    private function attendanceTimeToMinutes(?string $time): ?int
    {
        if (! $time) {
            return null;
        }

        $parsedTime = Carbon::parse($time);

        return ($parsedTime->hour * 60) + $parsedTime->minute;
    }

    private function buildAttendanceSummary($student): array
    {
        $records = $student->attendances()
            ->whereDate('date', '<=', Carbon::today())
            ->orderBy('date')
            ->get();

        $schoolDayRecords = $records->filter(fn ($attendance) => ! $attendance->date->isWeekend());

        $totalSchoolDays = $schoolDayRecords->count();

        $totalPresent = $schoolDayRecords->filter(function ($attendance) {
            return ! is_null($attendance->time_in_am)
                || ! is_null($attendance->time_out_am)
                || ! is_null($attendance->time_in_pm)
                || ! is_null($attendance->time_out_pm);
        })->count();

        $totalAbsent = max(0, $totalSchoolDays - $totalPresent);
        $attendanceRate = $totalSchoolDays > 0
            ? round(($totalPresent / $totalSchoolDays) * 100, 2)
            : 0;

        return [
            'totalSchoolDays' => $totalSchoolDays,
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'attendanceRate' => $attendanceRate,
        ];
    }

    private function buildAttendanceDateRange($student, $schoolYear): string
    {
        if ($schoolYear?->start_date && $schoolYear?->end_date) {
            return $schoolYear->start_date->format('M d, Y') . ' - ' . $schoolYear->end_date->format('M d, Y');
        }

        $bounds = $student->attendances()
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();

        if (! empty($bounds?->min_date) && ! empty($bounds?->max_date)) {
            return Carbon::parse($bounds->min_date)->format('M d, Y') . ' - ' . Carbon::parse($bounds->max_date)->format('M d, Y');
        }

        return 'No date range available';
    }

}