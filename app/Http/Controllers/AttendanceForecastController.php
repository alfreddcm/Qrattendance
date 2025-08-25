<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Charts\AttendanceForecastChart;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceForecastController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = auth()->id();
        $startDate = $request->get('start_date', Carbon::now()->subMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

         $attendance = Attendance::selectRaw('DATE(date) as attendance_date, COUNT(DISTINCT student_id) as present_count')
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [Carbon::now()->subDays(14), $endDate])
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        $labels = $attendance->pluck('attendance_date')->toArray();
        $presentCounts = $attendance->pluck('present_count')->toArray();

         $average = count($presentCounts) ? array_sum($presentCounts) / count($presentCounts) : 0;
        $forecastLabels = [];
        $forecastData = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::parse($endDate)->addDays($i)->toDateString();
            $forecastLabels[] = $date;
            $forecastData[] = round($average, 2);
        }

        $chart = new AttendanceForecastChart(array_merge($labels, $forecastLabels), array_merge($presentCounts, $forecastData));

        return view('teacher.statistics', [
            'attendanceForecastChart' => $chart,
         ]);
    }
}
