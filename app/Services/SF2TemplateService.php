<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;

class SF2TemplateService
{
    private $templatePath;
    private $outputDirectory;
    private $weekdayColumns = [];
    private $totalWeekdays = 0;

    public function __construct()
    {
        $this->templatePath = storage_path('app/public/templates/SF2_template.xlsx');
        $this->outputDirectory = storage_path('app/public/generated/SF2/');
        
        // Create output directory if it doesn't exist
        if (!file_exists($this->outputDirectory)) {
            mkdir($this->outputDirectory, 0755, true);
        }
    }

    /**
     * Generate SF2 report
     */
    public function generateSF2($params)
    {
        try {
            // Check if ZipArchive is available (required for Excel files)
            if (!class_exists('ZipArchive')) {
                return [
                    'success' => false,
                    'error' => 'ZIP extension is not enabled in PHP. Please enable the ZIP extension to generate Excel files. Contact your system administrator.'
                ];
            }

            // Validate parameters
            $semesterId = $params['semester_id'];
            $schoolYear = $params['school_year'];
            $gradeLevel = $params['grade_level'];
            $section = $params['section'];
            $month = $params['month'];
            $year = $params['year'];
            $filterGradeLevel = $params['filter_grade_level'] ?? null;
            $filterSection = $params['filter_section'] ?? null;
            $teacherId = $params['teacher_id'] ?? null; // Accept teacher ID from frontend

            // Load the template
            $spreadsheet = IOFactory::load($this->templatePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get user/teacher information
            $user = null;
            $currentUser = auth()->user();
            
            if ($teacherId) {
                // Admin side: Use provided teacher ID
                $user = User::find($teacherId);
                if (!$user) {
                    return [
                        'success' => false,
                        'error' => 'Teacher not found. Please select a valid teacher.'
                    ];
                }
            } else {
                // Teacher side: Use authenticated user
                $user = $currentUser;
            }
            
            $school = null;
            
            if ($user && $user->school_id) {
                $school = School::find($user->school_id);
            }
            
            if (!$school) {
                $school = School::first();
            }
            
            if (!$school) {
                return [
                    'success' => false,
                    'error' => 'No school information found. Please contact your administrator to set up school information.'
                ];
            }

            // Populate school information
            $this->populateSchoolInfo($worksheet, $school);

            // Populate class information
            $this->populateClassInfo($worksheet, $gradeLevel, $section, $month, $year, $schoolYear);

            // Get students for the specified semester with optional grade/section filtering
            $studentsQuery = Student::with('section')->where('semester_id', $semesterId);
            
            // Add user filter based on context
            if ($teacherId) {
                // Admin side: Get students for specific teacher
                $studentsQuery->where('user_id', $teacherId);
            } else {
                // Teacher side: Get students for authenticated teacher only
                if ($currentUser && $currentUser->role === 'teacher') {
                    $studentsQuery->where('user_id', $currentUser->id);
                }
            }
            
            if ($filterGradeLevel) {
                $studentsQuery->whereHas('section', function($query) use ($filterGradeLevel) {
                    $query->where('gradelevel', $filterGradeLevel);
                });
            }
            
            if ($filterSection) {
                $studentsQuery->whereHas('section', function($query) use ($filterSection) {
                    $query->where('name', $filterSection);
                });
            }
            
            $students = $studentsQuery->with('section')
                                   ->get()
                                   ->sortBy(function($student) {
                                       return [
                                           $student->section ? $student->section->gradelevel : 0,
                                           $student->section ? $student->section->name : '',
                                           $student->name
                                       ];
                                   });

            // Check if any students were found
            if ($students->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No students found for the selected semester' . ($filterGradeLevel || $filterSection ? ' and grade/section combination' : '') . '. Please check your selection and try again.'
                ];
            }

            // Populate student data and attendance
            $attendanceData = $this->populateStudentData($worksheet, $students, $month, $year, $user, $semesterId);

            // Generate filename
            $gradeSection = $filterGradeLevel && $filterSection ? "_{$filterGradeLevel}_{$filterSection}" : '';
            $filename = "SF2_{$schoolYear}{$gradeSection}_{$month}_{$year}.xlsx";
            $filepath = $this->outputDirectory . $filename;

            // Save the file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            $result = [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => asset('storage/generated/SF2/' . $filename),
                'student_count' => $students->count(),
                'attendance_data' => $attendanceData
            ];
            
            // Add data warnings if present
            if (isset($attendanceData['data_warnings']) && !empty($attendanceData['data_warnings'])) {
                $result['warnings'] = $attendanceData['data_warnings'];
            }
            
            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Populate school information in the template
     */
    private function populateSchoolInfo($worksheet, $school)
    {
        if ($school) {
            // School Information based on the actual SF2 template
            $worksheet->setCellValue('C6', $school->school_id ?? '');  
            $worksheet->setCellValue('C8', $school->name ?? '');  
            
            // Debug: Log school information being set
            \Log::info('SF2 School Info', [
                'school_id' => $school->school_id,
                'school_name' => $school->name,
                'C6_value' => $school->school_id ?? '',
                'C8_value' => $school->name ?? ''
            ]);
        } else {
            \Log::warning('SF2 School Info: No school data provided');
        }
    }

    /**
     * Populate class information in the template
     */
    private function populateClassInfo($worksheet, $gradeLevel, $section, $month, $year, $schoolYear)
    {
        // Class Information based on the actual SF2 template
        $worksheet->setCellValue('k6', $schoolYear); // School Year
        $worksheet->setCellValue('X6', Carbon::create($year, $month, 1)->format('F Y')); // Month Reporting
        $worksheet->setCellValue('X8', $gradeLevel); // Grade Level
        $worksheet->setCellValue('AC8', $section); // Section
        
        // Populate dates and days for the month
        $this->populateDatesAndDays($worksheet, $month, $year);
        
        // Footer details
        $worksheet->setCellValue('AD63', Carbon::create($year, $month, 1)->format('F')); // Month
        $worksheet->setCellValue('AE63', $this->getWorkingDaysInMonth($month, $year)); // No. of Days of Classes
    }

    /**
     * Populate dates and days in the header (weekdays only)
     */
    private function populateDatesAndDays($worksheet, $month, $year)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Base columns - will extend if needed
        $baseColumns = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB'];
        
        // Get all weekdays in the month
        $weekdays = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            // Only include weekdays (Monday=1 to Friday=5)
            if ($date->dayOfWeek >= 1 && $date->dayOfWeek <= 5) {
                $weekdays[] = $date->copy();
            }
        }
        
        // Generate additional columns if needed
        $columns = $this->generateColumnLetters(count($weekdays));
        
        // Populate weekdays only
        foreach ($weekdays as $index => $date) {
            if ($index < count($columns)) {
                $column = $columns[$index];
                
                // Date (1st row): D11 onwards
                $worksheet->setCellValue($column . '11', $date->day);
                
                // Day (M, T, W, Th, F): D12 onwards
                $dayAbbrev = $this->getDayAbbreviation($date->dayOfWeek);
                $worksheet->setCellValue($column . '12', $dayAbbrev);
            }
        }
        
        // Store columns for use in attendance population
        $this->weekdayColumns = array_slice($columns, 0, count($weekdays));
        $this->totalWeekdays = count($weekdays);
    }

    /**
     * Generate column letters dynamically (D, E, F... Z, AA, AB, AC...)
     */
    private function generateColumnLetters($count)
    {
        $columns = [];
        
        // Start from column D (which is index 3 in alphabet)
        for ($i = 0; $i < $count; $i++) {
            $columnIndex = 3 + $i; // Start from D (A=0, B=1, C=2, D=3)
            $columnLetter = '';
            
            if ($columnIndex < 26) {
                // Single letter columns (D-Z)
                $columnLetter = chr(65 + $columnIndex);
            } else {
                // Double letter columns (AA, AB, AC...)
                $first = intval(($columnIndex - 26) / 26);
                $second = ($columnIndex - 26) % 26;
                $columnLetter = chr(65 + $first) . chr(65 + $second);
            }
            
            $columns[] = $columnLetter;
        }
        
        return $columns;
    }

    /**
     * Get day abbreviation
     */
    private function getDayAbbreviation($dayOfWeek)
    {
        $days = [
            0 => 'Su', // Sunday
            1 => 'M',  // Monday
            2 => 'T',  // Tuesday
            3 => 'W',  // Wednesday
            4 => 'Th', // Thursday
            5 => 'F',  // Friday
            6 => 'Sa'  // Saturday
        ];
        
        return $days[$dayOfWeek] ?? '';
    }

    /**
     * Get working days in month (excluding weekends)
     */
    private function getWorkingDaysInMonth($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $workingDays = 0;
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if (!$date->isWeekend()) {
                $workingDays++;
            }
        }
        
        return $workingDays;
    }

    /**
     * Populate student data and attendance marks
     */
    private function populateStudentData($worksheet, $students, $month, $year, $user = null, $semesterId = null)
    {
        // Separate students by gender
        $maleStudents = $students->filter(function($student) {
            return strtolower($student->gender) === 'male' || strtolower($student->gender) === 'm';
        });
        
        $femaleStudents = $students->filter(function($student) {
            return strtolower($student->gender) === 'female' || strtolower($student->gender) === 'f';
        });
        
        // Populate male students (A13:A33, B13:B33)
        $maleData = $this->populateGenderSection($worksheet, $maleStudents, 13, 33, $month, $year, 'male');
        
        // Populate female students (A35:A59, B35:B59)
        $femaleData = $this->populateGenderSection($worksheet, $femaleStudents, 35, 59, $month, $year, 'female');
        
        // Populate daily totals
        $this->populateDailyTotals($worksheet, $month, $year);
        
        // Populate monthly totals
        $this->populateMonthlyTotals($worksheet, $maleData, $femaleData);
        
        // Populate summary statistics and teacher info
        $this->populateMonthlyStatistics($worksheet, $maleStudents->count(), $femaleStudents->count(), $maleData, $femaleData, $month, $year, $user, $semesterId);
        
        // Collect any data warnings
        $warnings = [];
        if (isset($maleData['data_warning']) && $maleData['data_warning']) {
            $warnings[] = $maleData['data_warning'];
        }
        if (isset($femaleData['data_warning']) && $femaleData['data_warning']) {
            $warnings[] = $femaleData['data_warning'];
        }
        
        $result = array_merge($maleData, $femaleData);
        if (!empty($warnings)) {
            $result['data_warnings'] = array_unique($warnings);
        }
        
        return $result;
    }

    /**
     * Populate students for a specific gender section
     */
    private function populateGenderSection($worksheet, $students, $startRow, $endRow, $month, $year, $gender)
    {
        $currentRow = $startRow;
        $totalAbsent = 0;
        $totalTardy = 0;
        $totalPresent = 0;
        
         $latestAttendanceDate = Attendance::max('date');
        $latestDate = $latestAttendanceDate ? Carbon::parse($latestAttendanceDate) : null;
        
         $attendanceColumns = $this->weekdayColumns;
        
         $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $weekdaysInMonth = [];
        
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            // Only include weekdays (Monday=1 to Friday=5)
            if ($date->dayOfWeek >= 1 && $date->dayOfWeek <= 5) {
                $weekdaysInMonth[$date->day] = $date->copy();
            }
        }
        
        foreach ($students as $index => $student) {
            if ($currentRow <= $endRow) {
                // Numbering
                $worksheet->setCellValue('A' . $currentRow, $index + 1);
                
                // Name (use the 'name' field from the database)
                $fullName = $student->name;
                $worksheet->setCellValue('B' . $currentRow, $fullName);
                
                 $attendanceRecords = Attendance::where('student_id', $student->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->get()
                    ->keyBy(function($item) {
                        return Carbon::parse($item->date)->day;
                    });
                
                $studentAbsent = 0;
                $studentTardy = 0;
                $studentPresent = 0;
                
                 $columnIndex = 0;
                foreach ($weekdaysInMonth as $day => $date) {
                    if ($columnIndex < count($attendanceColumns)) {
                        $column = $attendanceColumns[$columnIndex];
                        $cellRef = $column . $currentRow;
                        
                        if (isset($attendanceRecords[$day])) {
                            $attendance = $attendanceRecords[$day];
                            $mark = $this->getSF2AttendanceMark($attendance);
                            $worksheet->setCellValue($cellRef, $mark);
                            
                            // Count attendance types based on mark
                            if ($mark === 'x') {
                                $studentAbsent++;
                            } elseif ($mark === '/') {
                                $studentTardy++;
                            } else {
                                $studentPresent++;
                            }
                        } else {
                            // No attendance record for this weekday
                            // Check if this date is beyond our available data
                            if ($latestDate && $date->gt($latestDate)) {
                                // Date is beyond available attendance data - leave blank
                                $worksheet->setCellValue($cellRef, '');
                            } else {
                                // Date is within expected range but no record - consider absent
                                $worksheet->setCellValue($cellRef, 'x');
                                $studentAbsent++;
                            }
                        }
                        
                        $columnIndex++;
                    }
                }
                
                // Monthly totals for this student
                $worksheet->setCellValue('AC' . $currentRow, $studentAbsent); // Absent
                $worksheet->setCellValue('AD' . $currentRow, $studentTardy); // Tardy
                
                // Remarks (can be populated based on business logic)
                $worksheet->setCellValue('AE' . $currentRow, ''); // Remarks
                
                $totalAbsent += $studentAbsent;
                $totalTardy += $studentTardy;
                $totalPresent += $studentPresent;
                
                $currentRow++;
            }
        }
        
        // Check if requested month extends beyond available data
        $requestedMonth = Carbon::create($year, $month, 1);
        $dataAvailabilityWarning = null;
        
        if ($latestDate && $requestedMonth->endOfMonth()->gt($latestDate)) {
            $dataAvailabilityWarning = "Note: Attendance data is only available until {$latestDate->format('F j, Y')}. Days after this date are left blank.";
        }
        
        return [
            'total_absent' => $totalAbsent,
            'total_tardy' => $totalTardy,
            'total_present' => $totalPresent,
            'student_count' => $students->count(),
            'data_warning' => $dataAvailabilityWarning,
            'latest_data_date' => $latestDate ? $latestDate->format('Y-m-d') : null
        ];
    }

    /**
     * Get SF2 attendance mark based on attendance record
     */
    private function getSF2AttendanceMark($attendance)
    {
        // Check if student has any time_in record (either AM or PM)
        $hasTimeIn = !empty($attendance->time_in_am) || !empty($attendance->time_in_pm);
        
        if (!$hasTimeIn) {
            return 'x'; // Absent - no time in recorded
        }
        
        // For now, we'll consider any time_in as present
        // You could add logic here to check for tardiness based on expected times
        return ''; // Present - blank as per SF2 convention
    }

    /**
     * Populate daily totals
     */
    private function populateDailyTotals($worksheet, $month, $year)
    {
        $attendanceColumns = $this->weekdayColumns;
        
        for ($i = 0; $i < count($attendanceColumns); $i++) {
            $column = $attendanceColumns[$i];
            
            // Total per day (male): for each weekday column
            $maleFormula = '=COUNTIF(' . $column . '13:' . $column . '33,"x")';
            $worksheet->setCellValue($column . '34', $maleFormula);
            
            // Total per day (female): for each weekday column
            $femaleFormula = '=COUNTIF(' . $column . '35:' . $column . '59,"x")';
            $worksheet->setCellValue($column . '60', $femaleFormula);
            
            // Combined Total per day: for each weekday column
            $combinedFormula = '=' . $column . '34+' . $column . '60';
            $worksheet->setCellValue($column . '61', $combinedFormula);
        }
    }

    /**
     * Populate monthly totals
     */
    private function populateMonthlyTotals($worksheet, $maleData, $femaleData)
    {
        // Male totals
        $worksheet->setCellValue('AC34', $maleData['total_absent']); // Total Absent (Male)
        $worksheet->setCellValue('AD34', $maleData['total_tardy']); // Total Tardy (Male)
        
        // Female totals
        $worksheet->setCellValue('AC60', $femaleData['total_absent']); // Total Absent (Female)
        $worksheet->setCellValue('AD60', $femaleData['total_tardy']); // Total Tardy (Female)
        
        // Combined totals
        $worksheet->setCellValue('AC61', $maleData['total_absent'] + $femaleData['total_absent']); // Combined Absent
        $worksheet->setCellValue('AD61', $maleData['total_tardy'] + $femaleData['total_tardy']); // Combined Tardy
    }

    /**
     * Populate monthly statistics summary
     */
    private function populateMonthlyStatistics($worksheet, $maleCount, $femaleCount, $maleData, $femaleData, $month, $year, $user = null, $semesterId = null)
    {
        $totalStudents = $maleCount + $femaleCount;
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        
        // Get students for consecutive absence calculation
        $studentsQuery = Student::where('semester_id', $semesterId);
        if ($user && $user->role === 'teacher') {
            $studentsQuery->where('user_id', $user->id);
        }
        $allStudents = $studentsQuery->get();
        
        // Calculate consecutive absences for male and female students
        $maleConsecutiveAbsences = $this->calculateConsecutiveAbsences($allStudents, $month, $year, 'Male');
        $femaleConsecutiveAbsences = $this->calculateConsecutiveAbsences($allStudents, $month, $year, 'Female');
        $totalConsecutiveAbsences = $maleConsecutiveAbsences + $femaleConsecutiveAbsences;
        
        // Add teacher name at AC87
        if ($user && $user->name) {
            $worksheet->setCellValue('AC87', $user->name);
        }
        
        // Male statistics
        $maleEnrollment = $maleCount;
        $maleAvgAttendance = $maleCount > 0 && $workingDays > 0 ? ($maleData['total_present'] / ($workingDays * $maleCount)) * 100 : 0;
        
        $worksheet->setCellValue('AH65', $maleEnrollment); // Enrollment as of 1st Friday of June (Male)
        $worksheet->setCellValue('AH67', 0); // Late Enrollment (Male 
        $worksheet->setCellValue('AH69', $maleEnrollment); // Registered Learner (end of month) (Male)
        $worksheet->setCellValue('AH71', 100); // Percentage of Enrollment (Male)
        $worksheet->setCellValue('AH73', $workingDays > 0 ? round($maleData['total_present'] / $workingDays, 1) : 0); // Average Daily Attendance (Male)
        $worksheet->setCellValue('AH74', round($maleAvgAttendance, 1)); // Percentage Attendance (Male)
        $worksheet->setCellValue('AH76', $maleConsecutiveAbsences); // 5 Consecutive Absences (Male)
        $worksheet->setCellValue('AH78', 0); // Dropout (Male 
        $worksheet->setCellValue('AH80', 0); // Transferred Out (Male 
        $worksheet->setCellValue('AH82', 0); // Transferred In (Male 
        
        // Female statistics
        $femaleEnrollment = $femaleCount;
        $femaleAvgAttendance = $femaleCount > 0 && $workingDays > 0 ? ($femaleData['total_present'] / ($workingDays * $femaleCount)) * 100 : 0;
        
        $worksheet->setCellValue('AI65', $femaleEnrollment); // Enrollment as of 1st Friday of June (Female)
        $worksheet->setCellValue('AI67', 0); // Late Enrollment (Female 
        $worksheet->setCellValue('AI69', $femaleEnrollment); // Registered Learner (end of month) (Female)
        $worksheet->setCellValue('AI71', 100); // Percentage of Enrollment (Female)
        $worksheet->setCellValue('AI73', $workingDays > 0 ? round($femaleData['total_present'] / $workingDays, 1) : 0); // Average Daily Attendance (Female)
        $worksheet->setCellValue('AI74', round($femaleAvgAttendance, 1)); // Percentage Attendance (Female)
        $worksheet->setCellValue('AI76', $femaleConsecutiveAbsences); // 5 Consecutive Absences (Female)
        $worksheet->setCellValue('AI78', 0); // Dropout (Female 
        $worksheet->setCellValue('AI80', 0); // Transferred Out (Female 
        $worksheet->setCellValue('AI82', 0); // Transferred In (Female 
        
        // Total statistics
        $totalEnrollment = $totalStudents;
        $totalAvgAttendance = $totalStudents > 0 && $workingDays > 0 ? (($maleData['total_present'] + $femaleData['total_present']) / ($workingDays * $totalStudents)) * 100 : 0;
        
        $worksheet->setCellValue('AJ65', $totalEnrollment); // Enrollment as of 1st Friday of June (Total)
        $worksheet->setCellValue('AJ67', 0); // Late Enrollment (Total 
        $worksheet->setCellValue('AJ69', $totalEnrollment); // Registered Learner (end of month) (Total)
        $worksheet->setCellValue('AJ71', 100); // Percentage of Enrollment (Total)
        $worksheet->setCellValue('AJ73', $workingDays > 0 ? round(($maleData['total_present'] + $femaleData['total_present']) / $workingDays, 1) : 0); // Average Daily Attendance (Total)
        $worksheet->setCellValue('AJ74', round($totalAvgAttendance, 1)); // Percentage Attendance (Total)
        $worksheet->setCellValue('AJ76', $totalConsecutiveAbsences); // 5 Consecutive Absences (Total)
        $worksheet->setCellValue('AJ78', 0); // Dropout (Total 
        $worksheet->setCellValue('AJ80', 0); // Transferred Out (Total 
        $worksheet->setCellValue('AJ82', 0); // Transferred In (Total 
    }

    /**
     * Generate PDF version of the SF2
     */
    public function generatePDF($excelFilePath)
    {
        try {
            // Check if ZipArchive is available (required for Excel files)
            if (!class_exists('ZipArchive')) {
                return [
                    'success' => false,
                    'error' => 'ZIP extension is not enabled in PHP. Please enable the ZIP extension to generate PDF files. Contact your system administrator.'
                ];
            }

            $spreadsheet = IOFactory::load($excelFilePath);
            
            $pdfFilePath = str_replace('.xlsx', '.pdf', $excelFilePath);
            
            $writer = new Mpdf($spreadsheet);
            $writer->save($pdfFilePath);
            
            return [
                'success' => true,
                'pdf_path' => $pdfFilePath,
                'download_url' => asset('storage/generated/SF2/' . basename($pdfFilePath))
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of generated SF2 files
     */
    public function getGeneratedFiles()
    {
        $files = [];
        
        if (file_exists($this->outputDirectory)) {
            $fileList = scandir($this->outputDirectory);
            
            foreach ($fileList as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'xlsx') {
                    $files[] = [
                        'filename' => $file,
                        'path' => $this->outputDirectory . $file,
                        'download_url' => asset('storage/generated/SF2/' . $file),
                        'created_at' => date('Y-m-d H:i:s', filemtime($this->outputDirectory . $file))
                    ];
                }
            }
        }
        
        return $files;
    }

    /**
     * Calculate number of students with 5 consecutive absences
     */
    private function calculateConsecutiveAbsences($students, $month, $year, $gender = null)
    {
        $consecutiveAbsenceCount = 0;
        
        // Debug: Log the parameters
        \Log::info("calculateConsecutiveAbsences called", [
            'students_count' => $students->count(),
            'month' => $month,
            'year' => $year,
            'gender' => $gender
        ]);
        
        // Filter students by gender if specified
        if ($gender) {
            $students = $students->filter(function($student) use ($gender) {
                $studentGender = strtolower(trim($student->gender));
                $filterGender = strtolower(trim($gender));
                
                // Handle different gender formats
                if ($filterGender === 'male') {
                    return in_array($studentGender, ['male', 'm', 'boy']);
                } elseif ($filterGender === 'female') {
                    return in_array($studentGender, ['female', 'f', 'girl']);
                }
                
                return $studentGender === $filterGender;
            });
            
            \Log::info("After gender filter", [
                'gender' => $gender,
                'filtered_count' => $students->count()
            ]);
        }
        
        // Get the latest attendance date to avoid counting absences beyond available data
        $latestAttendanceDate = Attendance::max('date');
        $latestDate = $latestAttendanceDate ? Carbon::parse($latestAttendanceDate) : null;
        
        // Get all weekdays in the month
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $weekdaysInMonth = [];
        
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            // Only include weekdays (Monday=1 to Friday=5)
            if ($date->dayOfWeek >= 1 && $date->dayOfWeek <= 5) {
                // Only include dates within our data range
                if (!$latestDate || $date->lte($latestDate)) {
                    $weekdaysInMonth[] = $date->copy();
                }
            }
        }
        
        foreach ($students as $student) {
            // Get attendance records for this student in the specified month
            $attendanceRecords = Attendance::where('student_id', $student->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->keyBy(function($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });
            
            \Log::info("Student attendance check", [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_records' => $attendanceRecords->count(),
                'weekdays_in_month' => count($weekdaysInMonth)
            ]);
            
            // Check for consecutive absences
            $consecutiveDays = 0;
            $hasConsecutiveAbsences = false;
            
            foreach ($weekdaysInMonth as $date) {
                $dateKey = $date->format('Y-m-d');
                $isAbsent = false;
                
                if (isset($attendanceRecords[$dateKey])) {
                    $attendance = $attendanceRecords[$dateKey];
                    // Consider absent if no time_in records
                    if (!$attendance->time_in_am && !$attendance->time_in_pm) {
                        $isAbsent = true;
                    }
                } else {
                    // No attendance record = absent
                    $isAbsent = true;
                }
                
                if ($isAbsent) {
                    $consecutiveDays++;
                    if ($consecutiveDays >= 5) {
                        $hasConsecutiveAbsences = true;
                        break; // Found 5 consecutive absences for this student
                    }
                } else {
                    $consecutiveDays = 0; // Reset counter
                }
            }
            
            if ($hasConsecutiveAbsences) {
                $consecutiveAbsenceCount++;
                \Log::info("Found student with consecutive absences", [
                    'student_id' => $student->id,
                    'student_name' => $student->name
                ]);
            }
        }
        
        \Log::info("Final consecutive absence count", [
            'count' => $consecutiveAbsenceCount,
            'gender' => $gender,
            'weekdays_checked' => count($weekdaysInMonth),
            'latest_data_date' => $latestDate ? $latestDate->format('Y-m-d') : 'No attendance data'
        ]);
        
        return $consecutiveAbsenceCount;
    }
}
