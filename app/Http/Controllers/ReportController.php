<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolYear;
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
        $schoolYearId = $request->input('school_year_id');
        $gradeSection = $request->input('grade_section');
        $schoolYears = SchoolYear::all();
        $records = [];
 
        // Get students through teacher's sections
        $teacherId = Auth::id();
        $studentQuery = Student::whereHas('section', function($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        });
        
        if ($schoolYearId) {
            $studentQuery->where('school_year_id', $schoolYearId);
            $schoolYear = SchoolYear::find($schoolYearId);
        } else {
            $schoolYear = null;
        }

        // Get available grade/section options first
        $gradeSectionOptionsQuery = Student::with('section')->whereHas('section', function($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        });
        if ($schoolYearId) {
            $gradeSectionOptionsQuery->where('school_year_id', $schoolYearId);
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

        // If no grade_section is selected, use the first available option
        if (!$gradeSection && $gradeSectionOptions->isNotEmpty()) {
            $gradeSection = $gradeSectionOptions->first();
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

        if ($type === 'daily') {

            $date = $request->input('date', now()->toDateString());

             if ($schoolYear) {
                $schoolYear_start = \Carbon\Carbon::parse($schoolYear->start_date)->toDateString();
                $schoolYear_end = \Carbon\Carbon::parse($schoolYear->end_date)->toDateString();
                if ($date < $schoolYear_start || $date > $schoolYear_end) {
                     $records = collect();
                    return view('teacher.report', compact('schoolYears', 'records', 'schoolYear_start', 'schoolYear_end', 'gradeSectionOptions'));
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
             if ($schoolYear) {
                $schoolYear_start = Carbon::parse($schoolYear->start_date)->startOfDay();
                $schoolYear_end = Carbon::parse($schoolYear->end_date)->endOfDay();
            } else {
                $schoolYear_start = null;
                $schoolYear_end = null;
            }

            $month = $request->input('month'); 

            if ($month) {
 
                $start = Carbon::parse($month . '-01')->startOfMonth();
                $end = Carbon::parse($month . '-01')->endOfMonth();
            } else {
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
            }

             if ($schoolYear_start && $start < $schoolYear_start) $start = $schoolYear_start;
            if ($schoolYear_end && $end > $schoolYear_end) $end = $schoolYear_end;

             if ($schoolYear_start && $schoolYear_end && $start > $schoolYear_end) {
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
                        'rate'       => 0,
                        'checks'     => [],
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

                    // Build daily checks for each class day
                    $checks = [];
                    foreach ($classDays as $day) {
                        $dayAtt = $atts->get($day);
                        if ($dayAtt) {
                            if ($dayAtt->time_in_am && $dayAtt->time_out_am && $dayAtt->time_in_pm && $dayAtt->time_out_pm) {
                                $present++;
                                $checks[$day] = 'P';
                            } elseif ($dayAtt->time_in_am || $dayAtt->time_in_pm) {
                                $partial++;
                                $checks[$day] = 'H';
                            } else {
                                $absent++;
                                $checks[$day] = 'A';
                            }
                        } else {
                            $absent++;
                            $checks[$day] = 'A';
                        }
                    }

                    // compute rate: full present = 1, half-day counts as 0.5
                    $attendancePoints = $present + ($partial * 0.5);
                    $rate = $totalDays > 0 ? (int) round(($attendancePoints / $totalDays) * 100) : 0;

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
                        'rate'       => $rate,
                        'checks'     => $checks,
                    ];
                });
            }
        } elseif ($type === 'quarterly') {
            if ($schoolYear) {
            $start = \Carbon\Carbon::parse($schoolYear->start_date)->startOfDay();
            $end = \Carbon\Carbon::parse($schoolYear->end_date)->endOfDay();
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
 
                $totalDays = count($classDays);

                $records = $students->map(function ($student) use ($classDays, $totalDays) {
                $attendances = Attendance::where('student_id', $student->id)
                ->whereIn('date', $classDays)
                ->get()
                ->keyBy('date');

                 $checks = [];
                $present = 0;
                $partial = 0;
                $absent = 0;

                foreach ($classDays as $date) {
                    $att = $attendances->get($date);
                    if ($att) {
                        if ($att->time_in_am && $att->time_out_am && $att->time_in_pm && $att->time_out_pm) {
                            $present++;
                            $checks[$date] = 'P';
                        } elseif ($att->time_in_am || $att->time_in_pm) {
                            $partial++;
                            $checks[$date] = 'H';
                        } else {
                            $absent++;
                            $checks[$date] = 'A';
                        }
                    } else {
                        $absent++;
                        $checks[$date] = 'A';
                    }
                }

                // compute rate: full present = 1, half-day = 0.5
                $attendancePoints = $present + ($partial * 0.5);
                $rate = $totalDays > 0 ? (int) round(($attendancePoints / $totalDays) * 100) : 0;

                return (object)[
                    'id_no'    => $student->id_no,
                    'name'     => $student->name,
                    'grade_level' => $student->grade_level,
                    'section'  => $student->section_name,
                    'checks'   => $checks,
                    'present'  => $present,
                    'absent'   => $absent,
                    'partial'  => $partial,
                    'rate'     => $rate,
                ];
            });
            }
        }


        $schoolYear_start = $schoolYear ? $schoolYear->start_date : null;
        $schoolYear_end = $schoolYear ? $schoolYear->end_date : null;

        return view('teacher.report', compact('schoolYears', 'records', 'schoolYear_start', 'schoolYear_end', 'gradeSectionOptions', 'gradeSection'));
    }

    public function exportCsv(Request $request)
    {
        $type = $request->input('type', 'daily');
        $schoolYearId = $request->input('school_year_id');
        $gradeSection = $request->input('grade_section');
        $schoolYears = SchoolYear::all();

         $studentQuery = Student::where('user_id', Auth::id());
        
        if ($schoolYearId) {
            $studentQuery->where('school_year_id', $schoolYearId);
            $schoolYear = SchoolYear::find($schoolYearId);
        } else {
            $schoolYear = null;
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

        $students = $studentQuery->with('school')->get();

        $filename = 'attendance_report_' . now()->format('Ymd_His') . '.csv';

        // Get school info and grade/section info
        $gradeLevel = null;
        $sectionName = null;
        if ($gradeSection) {
            $parts = explode('|', $gradeSection);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
            }
        }

        $callback = function () use ($type, $students, $schoolYear, $gradeLevel, $sectionName) {
            $handle = fopen('php://output', 'w');

            // Get school name from the first student's school
            $schoolName = 'School Name Not Set';
            if ($students->isNotEmpty() && $students->first()->school) {
                $schoolName = $students->first()->school->name;
            }

            // Get school year from semester
            $schoolYear = '';
            if ($schoolYear) {
                $schoolYear = $this->extractSchoolYearFromSemester($schoolYear->name);
            } else {
                $currentYear = \Carbon\Carbon::now()->year;
                $currentMonth = \Carbon\Carbon::now()->month;
                if ($currentMonth <= 6) {
                    $schoolYear = ($currentYear - 1) . '-' . $currentYear;
                } else {
                    $schoolYear = $currentYear . '-' . ($currentYear + 1);
                }
            }

            // Grade and Section - only show if a specific section is selected
            if ($gradeLevel && $sectionName) {
                $gradeSectionText = 'Grade ' . $gradeLevel . ' - ' . $sectionName;
                // Write header information
                fputcsv($handle, ['School Name:', $schoolName]);
                fputcsv($handle, ['School Year:', $schoolYear]);
                fputcsv($handle, ['Grade/Section:', $gradeSectionText]);
            } else {
                // No grade/section header if not filtered
                fputcsv($handle, ['School Name:', $schoolName]);
                fputcsv($handle, ['School Year:', $schoolYear]);
            }

            if ($type === 'daily') {
                $date = request()->input('date', now()->toDateString());
                $formattedDate = \Carbon\Carbon::parse($date)->format('F d, Y');
                fputcsv($handle, ['Report Type:', 'Daily - ' . $formattedDate]);
                fputcsv($handle, []); // Empty row
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
                 if ($schoolYear) {
                    $schoolYear_start = \Carbon\Carbon::parse($schoolYear->start_date)->startOfDay();
                    $schoolYear_end = \Carbon\Carbon::parse($schoolYear->end_date)->endOfDay();
                } else {
                    $schoolYear_start = null;
                    $schoolYear_end = null;
                }

                $month = request()->input('month');

                if ($month) {
                    $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
                    $end = \Carbon\Carbon::parse($month . '-01')->endOfMonth();
                } else {
                    $start = now()->startOfMonth();
                    $end = now()->endOfMonth();
                }

                if ($schoolYear_start && $start < $schoolYear_start) $start = $schoolYear_start;
                if ($schoolYear_end && $end > $schoolYear_end) $end = $schoolYear_end;

                $formattedMonth = $start->format('F Y');
                $formattedStartDate = $start->format('d F Y');
                $formattedEndDate = $end->format('d F Y');
                fputcsv($handle, ['Report Type:', 'Monthly']);
                fputcsv($handle, ['Date Range:', $formattedStartDate . ' - ' . $formattedEndDate]);
                fputcsv($handle, []); // Empty row

                if ($schoolYear_start && $schoolYear_end && $start > $schoolYear_end) {
                    $classDays = [];
                } else {
                    $classDays = self::getClassDays($start, $end);
                }
                $totalDays = count($classDays);

                // Build header with No., ID No, Name, dates, and totals
                $header = ['No.', 'ID No', 'Name'];
                foreach ($classDays as $date) {
                    $header[] = $date;
                }
                $header[] = 'Total P';
                $header[] = 'Total H';
                $header[] = 'Total A';
                fputcsv($handle, $header);

                $rowNumber = 1;
                $totalPresent = 0;
                $totalHalf = 0;
                $totalAbsent = 0;

                if (empty($classDays)) {
                    foreach ($students as $student) {
                        fputcsv($handle, [
                            $rowNumber,
                            $student->id_no,
                            $student->name,
                            'No class days in range',
                        ]);
                        $rowNumber++;
                    }
                } else {
                    foreach ($students as $student) {
                        $atts = Attendance::where('student_id', $student->id)
                            ->whereIn('date', $classDays)
                            ->get()
                            ->keyBy('date');

                        $row = [$rowNumber, $student->id_no, $student->name];
                        $studentPresent = 0;
                        $studentHalf = 0;
                        $studentAbsent = 0;

                        // First column after name is column D (index 3), dates start at column D
                        $startCol = 4; // Column D in Excel (A=1, B=2, C=3, D=4)

                        foreach ($classDays as $day) {
                            $dayAtt = $atts->get($day);
                            if ($dayAtt) {
                                if ($dayAtt->time_in_am && $dayAtt->time_out_am && $dayAtt->time_in_pm && $dayAtt->time_out_pm) {
                                    $row[] = 'P';
                                    $studentPresent++;
                                } elseif ($dayAtt->time_in_am || $dayAtt->time_in_pm) {
                                    $row[] = 'H';
                                    $studentHalf++;
                                } else {
                                    $row[] = 'A';
                                    $studentAbsent++;
                                }
                            } else {
                                $row[] = 'A';
                                $studentAbsent++;
                            }
                        }

                        $totalPresent += $studentPresent;
                        $totalHalf += $studentHalf;
                        $totalAbsent += $studentAbsent;

                        // Add Excel formulas for counting P, H, A in this row
                        // Excel columns: A=No, B=ID, C=Name, D onwards are dates
                        $lastDateCol = $this->numberToExcelColumn($startCol + count($classDays) - 1);
                        $currentRow = $rowNumber + 1; // +1 because row 1 is header
                        
                        $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"P")';
                        $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"H")';
                        $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"A")';
                        
                        fputcsv($handle, $row);
                        $rowNumber++;
                    }

                    // Add summary section at the bottom
                    fputcsv($handle, []); // Empty row
                    fputcsv($handle, ['Summary']);
                    fputcsv($handle, ['Total Present', $totalPresent]);
                    fputcsv($handle, ['Total Half Day', $totalHalf]);
                    fputcsv($handle, ['Total Absent', $totalAbsent]);
                }
                } elseif ($type === 'quarterly') {
                if ($schoolYear) {
                    $start = \Carbon\Carbon::parse($schoolYear->start_date)->startOfDay();
                    $end = \Carbon\Carbon::parse($schoolYear->end_date)->endOfDay();
                } else {
                    $start = now()->startOfYear();
                    $end = now()->endOfYear();
                }
                
                $formattedStartDate = $start->format('d F Y');
                $formattedEndDate = $end->format('d F Y');
                fputcsv($handle, ['Report Type:', 'Quarterly']);
                fputcsv($handle, ['Date Range:', $formattedStartDate . ' - ' . $formattedEndDate]);
                fputcsv($handle, []); // Empty row
                
                $classDays = self::getClassDays($start, $end);

                // Build header with No., ID No, Name, dates, and totals
                $header = ['No.', 'ID No', 'Name'];
                foreach ($classDays as $date) {
                    $header[] = $date;
                }
                $header[] = 'Total P';
                $header[] = 'Total H';
                $header[] = 'Total A';
                fputcsv($handle, $header);

                $rowNumber = 1;
                $totalPresent = 0;
                $totalHalf = 0;
                $totalAbsent = 0;

                foreach ($students as $student) {
                    $attendances = Attendance::where('student_id', $student->id)
                        ->whereIn('date', $classDays)
                        ->get()
                        ->keyBy('date');
                    
                    $row = [$rowNumber, $student->id_no, $student->name];
                    $studentPresent = 0;
                    $studentHalf = 0;
                    $studentAbsent = 0;

                    // First column after name is column D (index 3), dates start at column D
                    $startCol = 4; // Column D in Excel (A=1, B=2, C=3, D=4)
                    
                    foreach ($classDays as $date) {
                        $att = $attendances->get($date);
                        if ($att) {
                            if ($att->time_in_am && $att->time_out_am && $att->time_in_pm && $att->time_out_pm) {
                                $row[] = 'P';
                                $studentPresent++;
                            } elseif ($att->time_in_am || $att->time_in_pm) {
                                $row[] = 'H';
                                $studentHalf++;
                            } else {
                                $row[] = 'A';
                                $studentAbsent++;
                            }
                        } else {
                            $row[] = 'A';
                            $studentAbsent++;
                        }
                    }

                    $totalPresent += $studentPresent;
                    $totalHalf += $studentHalf;
                    $totalAbsent += $studentAbsent;

                    // Add Excel formulas for counting P, H, A in this row
                    // Excel columns: A=No, B=ID, C=Name, D onwards are dates
                    $lastDateCol = $this->numberToExcelColumn($startCol + count($classDays) - 1);
                    $currentRow = $rowNumber + 1; // +1 because row 1 is header
                    
                    $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"P")';
                    $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"H")';
                    $row[] = '=COUNTIF(D' . $currentRow . ':' . $lastDateCol . $currentRow . ',"A")';
                    
                    fputcsv($handle, $row);
                    $rowNumber++;
                }

                // Add summary section at the bottom
                fputcsv($handle, []); // Empty row
                fputcsv($handle, ['Summary']);
                fputcsv($handle, ['Total Present', $totalPresent]);
                fputcsv($handle, ['Total Half Day', $totalHalf]);
                fputcsv($handle, ['Total Absent', $totalAbsent]);
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
            'school_year_id' => 'required|exists:semesters,id',
            'grade_section' => 'nullable|string',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'teacher_id' => 'nullable|integer|exists:users,id'  
        ]);

        try {
            $schoolYear = SchoolYear::find($request->school_year_id);
            $month = $request->month;
            $year = $request->year;

             $schoolYearName = $schoolYear->name;
            $schoolYear = $this->extractSchoolYearFromSemester($schoolYearName);
            
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
                'school_year_id' => $request->school_year_id,
                'school_year' => $schoolYear,
                'grade_level' => $gradeLevel ?: $schoolYearName,
                'section' => $section ?: 'All Students',
                'month' => $month,
                'year' => $year,
                'filter_grade_level' => $gradeLevel,
                'filter_section' => $section,
                'teacher_id' => $request->teacher_id ?? null  
            ]);

            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => 'SF2 generated successfully!',
                    'download_url' => $result['download_url'],
                    'filename' => $result['filename'],
                    'student_count' => $result['student_count']
                ];
                
                // Add warnings if present
                if (isset($result['warnings']) && !empty($result['warnings'])) {
                    $response['warnings'] = $result['warnings'];
                }
                
                return response()->json($response);
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
    private function extractSchoolYearFromSemester($schoolYearName)
    {
         if (preg_match('/(\d{4})/', $schoolYearName, $matches)) {
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
         $schoolYears = SchoolYear::whereHas('students', function($query) {
            $query->where('user_id', Auth::id());
        })->select('id', 'name', 'start_date', 'end_date')->orderBy('created_at', 'desc')->get();

         $schoolYearsWithDates = $schoolYears->map(function($schoolYear) {
            return [
                'id' => $schoolYear->id,
                'name' => $schoolYear->name,
                'start_date' => $schoolYear->start_date,
                'end_date' => $schoolYear->end_date,
                'start_month' => \Carbon\Carbon::parse($schoolYear->start_date)->month,
                'start_year' => \Carbon\Carbon::parse($schoolYear->start_date)->year,
                'end_month' => \Carbon\Carbon::parse($schoolYear->end_date)->month,
                'end_year' => \Carbon\Carbon::parse($schoolYear->end_date)->year
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
            'semesters' => $schoolYearsWithDates,
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

    /**
     * Convert column number to Excel column letter
     * e.g., 1 => A, 2 => B, 27 => AA, etc.
     */
    private function numberToExcelColumn($num) {
        $col = '';
        while ($num > 0) {
            $num--;
            $col = chr(65 + ($num % 26)) . $col;
            $num = intval($num / 26);
        }
        return $col;
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
