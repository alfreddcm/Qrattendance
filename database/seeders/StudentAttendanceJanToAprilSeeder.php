<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentAttendanceJanToAprilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates attendance records from January to April with:
     * - Random time-in (with some late arrivals)
     * - Random time-out (with some missing time-outs)
     */
    public function run(): void
    {
        // Get all students
        $students = Student::all();
        
        if ($students->isEmpty()) {
            $this->command->error('No students found. Please run student seeders first.');
            return;
        }

        $schoolYear = SchoolYear::first();
        if (!$schoolYear) {
            $this->command->error('No school year found. Please run SchoolYearsSeeder first.');
            return;
        }

        // Define date range: January 1 to April 30, 2026
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 4, 30);

        $this->command->info("Generating attendance from {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->command->info("Found {$students->count()} students");

        // Generate all weekdays (Monday-Friday) in the date range
        $dates = [];
        $period = $startDate->copy();
        while ($period->lte($endDate)) {
            // Skip weekends (Saturday and Sunday)
            if (!$period->isWeekend()) {
                $dates[] = $period->copy();
            }
            $period->addDay();
        }

        $this->command->info("Total school days: " . count($dates));
        
        // Delete existing attendance records in the date range first
        $deleted = Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->delete();
        $this->command->info("Deleted {$deleted} existing attendance records in date range");

        $totalRecords = 0;
        $lateCount = 0;
        $missingTimeoutCount = 0;
        $absentCount = 0;

        // Create attendance records for each student for each school day
        foreach ($students as $student) {
            foreach ($dates as $date) {
                // 5% chance student is absent (no attendance record at all)
                if (rand(1, 100) <= 5) {
                    $absentCount++;
                    continue;
                }

                // Generate morning attendance
                $amTimeIn = $this->generateMorningTimeIn();
                $amTimeOut = $this->generateMorningTimeOut();
                
                // Determine AM status
                $amStatus = $this->determineStatus($amTimeIn, '08:00:00');
                
                // Track late arrivals
                $isLate = $amStatus === 'Late' || $amStatus === 'Tardy';
                if ($isLate) {
                    $lateCount++;
                }

                // Generate afternoon attendance
                $pmTimeIn = $this->generateAfternoonTimeIn();
                $pmTimeOut = $this->generateAfternoonTimeOut();
                
                // Determine PM status
                $pmStatus = $this->determineStatus($pmTimeIn, '13:00:00');

                // 15% chance of missing time-out (either AM or PM)
                $missingTimeout = rand(1, 100) <= 15;
                if ($missingTimeout) {
                    $missingTimeoutCount++;
                    if (rand(0, 1)) {
                        $amTimeOut = null; // Missing AM time-out
                    } else {
                        $pmTimeOut = null; // Missing PM time-out
                    }
                }

                Attendance::create([
                    'school_year_id' => $schoolYear->id,
                    'student_id' => $student->id,
                    'school_id' => $student->school_id,
                    'teacher_id' => $student->user_id,
                    'date' => $date->toDateString(),
                    'time_in_am' => $amTimeIn,
                    'time_out_am' => $amTimeOut,
                    'time_in_pm' => $pmTimeIn,
                    'time_out_pm' => $pmTimeOut,
                    'am_status' => $amStatus,
                    'pm_status' => $pmStatus,
                    'remarks' => null,
                ]);

                $totalRecords++;
            }
            
            // Show progress every 10 students
            if ($student->id % 10 === 0) {
                $this->command->info("Processed student #{$student->id}...");
            }
        }

        $this->command->info("✓ Generated {$totalRecords} attendance records");
        $this->command->info("  - Late arrivals: {$lateCount}");
        $this->command->info("  - Missing time-outs: {$missingTimeoutCount}");
        $this->command->info("  - Absent days (skipped): {$absentCount}");
    }

    /**
     * Generate random morning time-in (7:00 AM - 9:30 AM)
     * With some late arrivals after 8:00 AM
     */
    private function generateMorningTimeIn(): string
    {
        // 30% chance of being late (after 8:00 AM)
        if (rand(1, 100) <= 30) {
            // Late: 8:01 AM - 9:30 AM
            $hour = rand(8, 9);
            $minute = $hour === 9 ? rand(0, 30) : rand(1, 59);
        } else {
            // On time: 7:00 AM - 8:00 AM
            $hour = 7;
            $minute = rand(0, 59);
        }
        
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    /**
     * Generate random morning time-out (11:30 AM - 12:30 PM)
     */
    private function generateMorningTimeOut(): string
    {
        $hour = rand(11, 12);
        $minute = $hour === 11 ? rand(30, 59) : rand(0, 30);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    /**
     * Generate random afternoon time-in (12:30 PM - 1:30 PM)
     */
    private function generateAfternoonTimeIn(): string
    {
        $hour = rand(12, 13);
        $minute = $hour === 12 ? rand(30, 59) : rand(0, 30);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    /**
     * Generate random afternoon time-out (4:00 PM - 5:30 PM)
     */
    private function generateAfternoonTimeOut(): string
    {
        $hour = rand(16, 17);
        $minute = $hour === 17 ? rand(0, 30) : rand(0, 59);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    /**
     * Determine attendance status based on time-in
     */
    private function determineStatus(string $timeIn, string $thresholdTime): string
    {
        $timeInCarbon = Carbon::parse($timeIn);
        $threshold = Carbon::parse($thresholdTime);
        
        // Early: 30+ minutes before threshold
        if ($timeInCarbon->lessThan($threshold->copy()->subMinutes(30))) {
            return 'Early';
        }
        
        // On Time: within threshold
        if ($timeInCarbon->lessThanOrEqualTo($threshold)) {
            return 'On Time';
        }
        
        // Tardy: 1-15 minutes late
        if ($timeInCarbon->lessThanOrEqualTo($threshold->copy()->addMinutes(15))) {
            return 'Tardy';
        }
        
        // Late: more than 15 minutes late
        return 'Late';
    }
}
