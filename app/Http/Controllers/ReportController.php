<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'daily');
        $semesterId = $request->input('semester_id');
        $semesters = Semester::all();
        $records = [];

        if ($semesterId) {
            $students = Student::where('semester_id', $semesterId)->where('user_id', Auth::id())->get();
            $semester = Semester::find($semesterId);
        } else {
            $students = Student::where('user_id', Auth::id())->get();
            $semester = null;
        }



        if ($type === 'daily') {

            $date = $request->input('date', now()->toDateString());

             if ($semester) {
                $semester_start = \Carbon\Carbon::parse($semester->start_date)->toDateString();
                $semester_end = \Carbon\Carbon::parse($semester->end_date)->toDateString();
                if ($date < $semester_start || $date > $semester_end) {
                     $records = collect();
                    return view('teacher.report', compact('semesters', 'records', 'semester_start', 'semester_end'));
                }
            }

            $attendances = Attendance::whereDate('date', $date)->get()->keyBy('student_id');
            $records = $students->map(function ($student) use ($attendances, $date) {
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
                    'am_in'     => $att && $att->time_in_am ? \Carbon\Carbon::parse($att->time_in_am)->setTimezone('Asia/Manila')->format('h:i A') : null,
                    'am_out'    => $att && $att->time_out_am ? \Carbon\Carbon::parse($att->time_out_am)->setTimezone('Asia/Manila')->format('h:i A') : null,
                    'pm_in'     => $att && $att->time_in_pm ? \Carbon\Carbon::parse($att->time_in_pm)->setTimezone('Asia/Manila')->format('h:i A') : null,
                    'pm_out'    => $att && $att->time_out_pm ? \Carbon\Carbon::parse($att->time_out_pm)->setTimezone('Asia/Manila')->format('h:i A') : null,
                    'status'    => $status,
                ];
            });
        } elseif ($type === 'monthly') {
             if ($semester) {
                $semester_start = Carbon::parse($semester->start_date)->startOfDay();
                $semester_end = Carbon::parse($semester->end_date)->endOfDay();
            } else {
                $semester_start = null;
                $semester_end = null;
            }

            $month = $request->input('month'); 

            if ($month) {
 
                $start = Carbon::parse($month . '-01')->startOfMonth();
                $end = Carbon::parse($month . '-01')->endOfMonth();
            } else {
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
            }

             if ($semester_start && $start < $semester_start) $start = $semester_start;
            if ($semester_end && $end > $semester_end) $end = $semester_end;

             if ($semester_start && $semester_end && $start > $semester_end) {
                $classDays = [];
            } else {
                $classDays = self::getClassDays($start, $end);
            }
            $totalDays = count($classDays);

            if (empty($classDays)) {
                $records = $students->map(function ($student) {
                    return (object)[
                        'id_no'      => $student->id_no,
                        'name'       => $student->name,
                        'total_day'  => 0,
                        'present'    => 0,
                        'absent'     => 0,
                        'partial'    => 0,
                        'remarks'    => 'No class days in range',
                    ];
                });
            } else {
                $records = $students->map(function ($student) use ($classDays, $totalDays) {
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
                        'total_day'  => $totalDays,
                        'present'    => $present,
                        'absent'     => $absent,
                        'partial'    => $partial,
                        'remarks'    => $remarks,
                    ];
                });
            }
        } elseif ($type === 'quarterly') {
            if ($semester) {
            $start = \Carbon\Carbon::parse($semester->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($semester->end_date)->endOfDay();
            } else {
            $start = now()->startOfYear();
            $end = now()->endOfYear();
            }

            $classDays = self::getClassDays($start, $end);

            // If no class days, show empty data
            if (empty($classDays)) {
            $records = $students->map(function ($student) {
                return (object)[
                'id_no'    => $student->id_no,
                'name'     => $student->name,
                'checks'   => [],
                ];
            });
            } else {
 
                $records = $students->map(function ($student) use ($classDays) {
                $attendances = Attendance::where('student_id', $student->id)
                ->whereIn('date', $classDays)
                ->get()
                ->keyBy('date');

                 $checks = [];
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

                return (object)[
                'id_no'    => $student->id_no,
                'name'     => $student->name,
                'checks'   => $checks,
                ];
            });
            }
        }


        $semester_start = $semester ? $semester->start_date : null;
        $semester_end = $semester ? $semester->end_date : null;

        return view('teacher.report', compact('semesters', 'records', 'semester_start', 'semester_end'));
    }

    public function exportCsv(Request $request)
    {
        $type = $request->input('type', 'daily');
        $semesterId = $request->input('semester_id');
        $semesters = Semester::all();

        if ($semesterId) {
            $students = Student::where('semester_id', $semesterId)->where('user_id', Auth::id())->get();
            $semester = Semester::find($semesterId);
        } else {
            $students = Student::where('user_id', Auth::id())->get();
            $semester = null;
        }

        $filename = 'attendance_report_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($type, $students, $semester) {
            $handle = fopen('php://output', 'w');

            if ($type === 'daily') {
                $date = request()->input('date', now()->toDateString());
                fputcsv($handle, ['Date', 'ID No', 'Name', 'AM In', 'AM Out', 'PM In', 'PM Out', 'Status']);
                $attendances = Attendance::whereDate('date', $date)->get()->keyBy('student_id');
                foreach ($students as $student) {
                    $att = $attendances->get($student->id);
                    
                    // Determine status
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
                    
                    fputcsv($handle, [
                        $date,
                        $student->id_no,
                        $student->name,
                        $att && $att->time_in_am ? \Carbon\Carbon::parse($att->time_in_am)->setTimezone('Asia/Manila')->format('h:i A') : '--',
                        $att && $att->time_out_am ? \Carbon\Carbon::parse($att->time_out_am)->setTimezone('Asia/Manila')->format('h:i A') : '--',
                        $att && $att->time_in_pm ? \Carbon\Carbon::parse($att->time_in_pm)->setTimezone('Asia/Manila')->format('h:i A') : '--',
                        $att && $att->time_out_pm ? \Carbon\Carbon::parse($att->time_out_pm)->setTimezone('Asia/Manila')->format('h:i A') : '--',
                        $status,
                    ]);
                }
            } elseif ($type === 'monthly') {
                // Apply the same approach as in the index() method
                if ($semester) {
                    $semester_start = \Carbon\Carbon::parse($semester->start_date)->startOfDay();
                    $semester_end = \Carbon\Carbon::parse($semester->end_date)->endOfDay();
                } else {
                    $semester_start = null;
                    $semester_end = null;
                }

                $month = request()->input('month');

                if ($month) {
                    $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
                    $end = \Carbon\Carbon::parse($month . '-01')->endOfMonth();
                } else {
                    $start = now()->startOfMonth();
                    $end = now()->endOfMonth();
                }

                if ($semester_start && $start < $semester_start) $start = $semester_start;
                if ($semester_end && $end > $semester_end) $end = $semester_end;

                if ($semester_start && $semester_end && $start > $semester_end) {
                    $classDays = [];
                } else {
                    $classDays = self::getClassDays($start, $end);
                }
                $totalDays = count($classDays);

                fputcsv($handle, ['ID No', 'Name', 'Total Days', 'Present', 'Absent', 'Partial', 'Remarks']);
                if (empty($classDays)) {
                    foreach ($students as $student) {
                        fputcsv($handle, [
                            $student->id_no,
                            $student->name,
                            0,
                            0,
                            0,
                            0,
                            'No class days in range',
                        ]);
                    }
                } else {
                    foreach ($students as $student) {
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

                        fputcsv($handle, [
                            $student->id_no,
                            $student->name,
                            $totalDays,
                            $present,
                            $absent,
                            $partial,
                            $remarks,
                        ]);
                    }
                }} elseif ($type === 'quarterly') {
                if ($semester) {
                    $start = \Carbon\Carbon::parse($semester->start_date)->startOfDay();
                    $end = \Carbon\Carbon::parse($semester->end_date)->endOfDay();
                } else {
                    $start = now()->startOfYear();
                    $end = now()->endOfYear();
                }
                $classDays = self::getClassDays($start, $end);

                $header = ['ID No', 'Name'];
                foreach ($classDays as $date) {
                    $header[] = $date;
                }
                fputcsv($handle, $header);

                foreach ($students as $student) {
                    $attendances = Attendance::where('student_id', $student->id)
                        ->whereIn('date', $classDays)
                        ->get()
                        ->keyBy('date');
                    $row = [$student->id_no, $student->name];
                    foreach ($classDays as $date) {
                        $att = $attendances->get($date);
                        if ($att) {
                            if ($att->time_in_am && $att->time_out_am && $att->time_in_pm && $att->time_out_pm) {
                                $row[] = '/'; // Full day
                            } elseif ($att->time_in_am || $att->time_in_pm) {
                                $row[] = '='; 
                            } else {
                                $row[] = 'x'; 
                            }
                        } else {
                            $row[] = '✗';
                        }
                    }
                    fputcsv($handle, $row);
                }
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }



        private static function getClassDays($start, $end) {
            $days = [];
            $period = \Carbon\CarbonPeriod::create($start, $end);
            foreach ($period as $date) {
                if (!in_array($date->dayOfWeek, [Carbon::SATURDAY,Carbon::SUNDAY])) {
                    $days[] = $date->toDateString();
                }
            }
            return $days;
        }
}