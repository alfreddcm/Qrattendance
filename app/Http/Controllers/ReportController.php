<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\SF2TemplateService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'daily');
        $semesterId = $request->input('semester_id');
        $gradeSection = $request->input('grade_section');
        $semesters = Semester::all();
        $records = [];
 
        $studentQuery = Student::where('user_id', Auth::id());
        
        if ($semesterId) {
            $studentQuery->where('semester_id', $semesterId);
            $semester = Semester::find($semesterId);
        } else {
            $semester = null;
        }

 
        if ($gradeSection) {
            $parts = explode('|', $gradeSection);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
                $studentQuery->whereHas('section', function($query) use ($gradeLevel, $sectionName) {
                    $query->where('gradelevel', $gradeLevel)->where('name', $sectionName);
                });
            }
        }

        $students = $studentQuery->orderBy('name')->get();

         $gradeSectionOptionsQuery = Student::with('section')->where('user_id', Auth::id());
        if ($semesterId) {
            $gradeSectionOptionsQuery->where('semester_id', $semesterId);
        }
        
        $gradeSectionOptions = $gradeSectionOptionsQuery
            ->get()
            ->filter(function($student) {
                return $student->section;  
            })
            ->map(function ($student) {
                return $student->section->gradelevel . '|' . $student->section->name;
            })
            ->unique()
            ->sort()
            ->values();

        if ($type === 'daily') {

            $date = $request->input('date', now()->toDateString());

             if ($semester) {
                $semester_start = \Carbon\Carbon::parse($semester->start_date)->toDateString();
                $semester_end = \Carbon\Carbon::parse($semester->end_date)->toDateString();
                if ($date < $semester_start || $date > $semester_end) {
                     $records = collect();
                    return view('teacher.report', compact('semesters', 'records', 'semester_start', 'semester_end', 'gradeSectionOptions'));
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
                    'grade_level' => $student->grade_level,
                    'section'   => $student->section_name,
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
                        'grade_level' => $student->grade_level,
                        'section'    => $student->section_name,
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
                        'grade_level' => $student->grade_level,
                        'section'    => $student->section_name,
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

             if (empty($classDays)) {
            $records = $students->map(function ($student) {
                return (object)[
                'id_no'    => $student->id_no,
                'name'     => $student->name,
                'grade_level' => $student->grade_level,
                'section'  => $student->section_name,
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
                'grade_level' => $student->grade_level,
                'section'  => $student->section_name,
                'checks'   => $checks,
                ];
            });
            }
        }


        $semester_start = $semester ? $semester->start_date : null;
        $semester_end = $semester ? $semester->end_date : null;

        return view('teacher.report', compact('semesters', 'records', 'semester_start', 'semester_end', 'gradeSectionOptions'));
    }

    public function exportCsv(Request $request)
    {
        $type = $request->input('type', 'daily');
        $semesterId = $request->input('semester_id');
        $gradeSection = $request->input('grade_section');
        $semesters = Semester::all();

         $studentQuery = Student::where('user_id', Auth::id());
        
        if ($semesterId) {
            $studentQuery->where('semester_id', $semesterId);
            $semester = Semester::find($semesterId);
        } else {
            $semester = null;
        }

         if ($gradeSection) {
            $parts = explode('|', $gradeSection);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
                $studentQuery->whereHas('section', function($query) use ($gradeLevel, $sectionName) {
                    $query->where('gradelevel', $gradeLevel)->where('name', $sectionName);
                });
            }
        }

        $students = $studentQuery->get();

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
                                $row[] = '/';  
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

    /**
     * Generate SF2 Form
     */
    public function generateSF2(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'grade_section' => 'nullable|string',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'teacher_id' => 'nullable|integer|exists:users,id'  
        ]);

        try {
            $semester = Semester::find($request->semester_id);
            $month = $request->month;
            $year = $request->year;

             $semesterName = $semester->name;
            $schoolYear = $this->extractSchoolYearFromSemester($semesterName);
            
             $gradeLevel = null;
            $section = null;
            
            if ($request->grade_section) {
                $parts = explode('|', $request->grade_section);
                if (count($parts) == 2) {
                    $gradeLevel = $parts[0];
                    $section = $parts[1];
                }
            }

            $sf2Service = new SF2TemplateService();
            
            $result = $sf2Service->generateSF2([
                'semester_id' => $request->semester_id,
                'school_year' => $schoolYear,
                'grade_level' => $gradeLevel ?: $semesterName,
                'section' => $section ?: 'All Students',
                'month' => $month,
                'year' => $year,
                'filter_grade_level' => $gradeLevel,
                'filter_section' => $section,
                'teacher_id' => $request->teacher_id ?? null  
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'SF2 generated successfully!',
                    'download_url' => $result['download_url'],
                    'filename' => $result['filename'],
                    'student_count' => $result['student_count']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating SF2: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating SF2: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract school year from semester name
     */
    private function extractSchoolYearFromSemester($semesterName)
    {
         if (preg_match('/(\d{4})/', $semesterName, $matches)) {
            $year = (int)$matches[1];
            return ($year - 1) . '-' . $year;
        }
        
         $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
         if ($currentMonth <= 6) {
            return ($currentYear - 1) . '-' . $currentYear;
        } else {
            return $currentYear . '-' . ($currentYear + 1);
        }
    }

    /**
     * Generate PDF version of SF2
     */
    public function generateSF2PDF(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|string'
        ]);

        try {
            $sf2Service = new SF2TemplateService();
            $excelPath = storage_path('app/public/generated/SF2/' . $request->excel_file);
            
            if (!file_exists($excelPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excel file not found'
                ], 404);
            }

            $result = $sf2Service->generatePDF($excelPath);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'PDF generated successfully!',
                    'download_url' => $result['download_url']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error generating PDF: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }

 
    public function getSF2Options()
    {
         $semesters = Semester::whereHas('students', function($query) {
            $query->where('user_id', Auth::id());
        })->select('id', 'name', 'start_date', 'end_date')->orderBy('created_at', 'desc')->get();

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

         $gradeSection = Student::with('section')
            ->where('user_id', Auth::id())
            ->whereHas('section')
            ->get()
            ->filter(function($student) {
                return $student->section; 
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

         $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return response()->json([
            'semesters' => $semestersWithDates,
            'grade_section_options' => $gradeSection,
            'months' => $months
        ]);
    }

  
    public function getGeneratedSF2Files()
    {
        try {
            $sf2Service = new SF2TemplateService();
            $files = $sf2Service->getGeneratedFiles();

            return response()->json([
                'success' => true,
                'files' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving files: ' . $e->getMessage()
            ], 500);
        }
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