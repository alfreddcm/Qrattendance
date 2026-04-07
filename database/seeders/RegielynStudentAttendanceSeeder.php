<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolYear;
use App\Models\User;
use Carbon\Carbon;

class RegielynStudentAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates attendance records for Regielyn's students from January to April with:
     * - Random time-in (with some late arrivals)
     * - Random time-out (with some missing time-outs)
     */
    public function run(): void
    {
        // Find Regielyn teacher (case-insensitive search)
        $teacher = User::where('role', 'teacher')
            ->where('name', 'like', '%Regielyn%')
            ->first();
        
        if (!$teacher) {
            $this->command->error('Teacher "Regielyn" not found. Please check the teacher name in the database.');
            $this->command->info('Available teachers:');
            User::where('role', 'teacher')->get()->each(function($t) {
                $this->command->info("  - ID: {$t->id}, Name: {$t->name}");
            });
            return;
        }

        $this->command->info("Found teacher: {$teacher->name} (ID: {$teacher->id})");

        // Get students assigned to this teacher
        $students = Student::where('user_id', $teacher->id)->get();
        
        if ($students->isEmpty()) {
            $this->command->error("No students found for teacher {$teacher->name}.");
            return;
        }

        $this->command->info("Found {$students->count()} students for {$teacher->name}");

        $schoolYear = SchoolYear::first();
        if (!$schoolYear) {
            $this->command->error('No school year found. Please run SchoolYearsSeeder first.');
            return;
        }

        // Define date range: January 1 to April 6, 2026 (skip today April 7)
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 4, 6); // Yesterday - skip today for QR testing
        $today = Carbon::today();

        $this->command->info("Generating attendance from {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->command->info("Skipping today ({$today->toDateString()}) for QR testing");

        // Generate all weekdays (Monday-Friday) in the date range
        $dates = [];
        $period = $startDate->copy();
        while ($period->lte($endDate)) {
            // Skip weekends AND skip today
            if (!$period->isWeekend() && !$period->isSameDay($today)) {
                $dates[] = $period->copy();
            }
            $period->addDay();
        }

        $this->command->info("Total school days: " . count($dates));

        // Delete existing attendance records for these students in the date range (including today)
        $studentIds = $students->pluck('id')->toArray();
        $deleted = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate->toDateString(), $today->toDateString()])
            ->delete();
        $this->command->info("Deleted {$deleted} existing attendance records for these students");

        $totalRecords = 0;
        $lateCount = 0;
        $missingTimeoutCount = 0;
        $absentCount = 0;

        foreach ($students as $student) {
            foreach ($dates as $date) {
                // 5% chance student is absent (no attendance record)
                if (rand(1, 100) <= 5) {
                    $absentCount++;
                    continue;
                }

                // Generate morning attendance
                $amTimeIn = $this->generateMorningTimeIn();
                $amTimeOut = $this->generateMorningTimeOut();
                $amStatus = $this->determineStatus($amTimeIn, '08:00:00');

                if ($amStatus === 'Late' || $amStatus === 'Tardy') {
                    $lateCount++;
                }

                // Generate afternoon attendance
                $pmTimeIn = $this->generateAfternoonTimeIn();
                $pmTimeOut = $this->generateAfternoonTimeOut();
                $pmStatus = $this->determineStatus($pmTimeIn, '13:00:00');

                // 15% chance of missing time-out (either AM or PM)
                if (rand(1, 100) <= 15) {
                    $missingTimeoutCount++;
                    if (rand(0, 1)) {
                        $amTimeOut = null;
                    } else {
                        $pmTimeOut = null;
                    }
                }

                Attendance::create([
                    'school_year_id' => $schoolYear->id,
                    'student_id' => $student->id,
                    'school_id' => $student->school_id,
                    'teacher_id' => $teacher->id,
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
        }

        $this->command->info("✓ Generated {$totalRecords} attendance records for {$teacher->name}'s students");
        $this->command->info("  - Late arrivals: {$lateCount}");
        $this->command->info("  - Missing time-outs: {$missingTimeoutCount}");
        $this->command->info("  - Absent days (skipped): {$absentCount}");
    }

    private function generateMorningTimeIn(): string
    {
        // 30% chance of being late (after 8:00 AM)
        if (rand(1, 100) <= 30) {
            $hour = rand(8, 9);
            $minute = $hour === 9 ? rand(0, 30) : rand(1, 59);
        } else {
            $hour = 7;
            $minute = rand(0, 59);
        }
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    private function generateMorningTimeOut(): string
    {
        $hour = rand(11, 12);
        $minute = $hour === 11 ? rand(30, 59) : rand(0, 30);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    private function generateAfternoonTimeIn(): string
    {
        $hour = rand(12, 13);
        $minute = $hour === 12 ? rand(30, 59) : rand(0, 30);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    private function generateAfternoonTimeOut(): string
    {
        $hour = rand(16, 17);
        $minute = $hour === 17 ? rand(0, 30) : rand(0, 59);
        $second = rand(0, 59);
        return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }

    private function determineStatus(string $timeIn, string $thresholdTime): string
    {
        $timeInCarbon = Carbon::parse($timeIn);
        $threshold = Carbon::parse($thresholdTime);

        if ($timeInCarbon->lessThan($threshold->copy()->subMinutes(30))) {
            return 'Early';
        }
        if ($timeInCarbon->lessThanOrEqualTo($threshold)) {
            return 'On Time';
        }
        if ($timeInCarbon->lessThanOrEqualTo($threshold->copy()->addMinutes(15))) {
            return 'Tardy';
        }
        return 'Late';
    }
}
