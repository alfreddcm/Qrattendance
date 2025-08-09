<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing attendance records to avoid duplicates
        Attendance::truncate();
        
        // Get teacher users
        $users = User::where('role', 'teacher')->get();
        
        // Assign users to students if not already assigned
        $students = Student::all();
        
        // Only assign teachers if there are teachers available
        if ($users->count() > 0) {
            foreach ($students as $index => $student) {
                if (!$student->user_id) {
                    $userIndex = $index % $users->count();
                    $student->update(['user_id' => $users[$userIndex]->id]);
                }
            }
        }
        
        // Create sample attendance records for the current month
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        $period = CarbonPeriod::create($startDate, $endDate);
        
        foreach ($period as $date) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }
            
            // Create attendance for first 3 students
            for ($studentId = 1; $studentId <= 3; $studentId++) {
                // 80% chance of attendance
                if (rand(1, 100) <= 80) {
                    Attendance::updateOrCreate([
                        'student_id' => $studentId,
                        'date' => $date->toDateString(),
                    ], [
                        'semester_id' => 1,
                        'time_in_am' => '08:00:00',
                        'time_out_am' => '12:00:00',
                        'time_in_pm' => '13:00:00',
                        'time_out_pm' => '17:00:00',
                    ]);
                }
            }
        }
        
        // Create some partial attendance records for variety
        $partialDates = [
            now()->subDays(3)->toDateString(),
            now()->subDays(5)->toDateString(),
            now()->subDays(7)->toDateString(),
        ];
        
        foreach ($partialDates as $date) {
            // Skip if it's a weekend
            if (Carbon::parse($date)->isWeekend()) {
                continue;
            }
            
            Attendance::updateOrCreate([
                'student_id' => 2,
                'date' => $date,
            ], [
                'semester_id' => 1,
                'time_in_am' => '08:30:00',
                'time_out_am' => null, 
                'time_in_pm' => '13:15:00',
                'time_out_pm' => '17:00:00',
            ]);
        }
    }
}
