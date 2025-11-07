<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixSchoolYearReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:school-year-references';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invalid school_year_id references in related tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking and fixing school year references...');
        
        // Get valid school year IDs
        $validSchoolYearIds = DB::table('school_years')->pluck('id')->toArray();
        $this->info('Valid school year IDs: ' . implode(', ', $validSchoolYearIds));
        
        // Check students table
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'school_year_id')) {
            $invalidStudents = DB::table('students')
                ->whereNotNull('school_year_id')
                ->whereNotIn('school_year_id', $validSchoolYearIds)
                ->count();
                
            if ($invalidStudents > 0) {
                $this->warn("Found {$invalidStudents} students with invalid school_year_id");
                // Set invalid references to null
                DB::table('students')
                    ->whereNotNull('school_year_id')
                    ->whereNotIn('school_year_id', $validSchoolYearIds)
                    ->update(['school_year_id' => null]);
                $this->info("Fixed invalid school_year_id references in students table");
            } else {
                $this->info("Students table: No invalid references found");
            }
        }
        
        // Check sections table
        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'school_year_id')) {
            $invalidSections = DB::table('sections')
                ->whereNotNull('school_year_id')
                ->whereNotIn('school_year_id', $validSchoolYearIds)
                ->count();
                
            if ($invalidSections > 0) {
                $this->warn("Found {$invalidSections} sections with invalid school_year_id");
                DB::table('sections')
                    ->whereNotNull('school_year_id')
                    ->whereNotIn('school_year_id', $validSchoolYearIds)
                    ->update(['school_year_id' => null]);
                $this->info("Fixed invalid school_year_id references in sections table");
            } else {
                $this->info("Sections table: No invalid references found");
            }
        }
        
        // Check attendances table
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'school_year_id')) {
            $invalidAttendances = DB::table('attendances')
                ->whereNotNull('school_year_id')
                ->whereNotIn('school_year_id', $validSchoolYearIds)
                ->count();
                
            if ($invalidAttendances > 0) {
                $this->warn("Found {$invalidAttendances} attendance records with invalid school_year_id");
                DB::table('attendances')
                    ->whereNotNull('school_year_id')
                    ->whereNotIn('school_year_id', $validSchoolYearIds)
                    ->update(['school_year_id' => null]);
                $this->info("Fixed invalid school_year_id references in attendances table");
            } else {
                $this->info("Attendances table: No invalid references found");
            }
        }
        
        // Check attendance_sessions table
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'school_year_id')) {
            $invalidSessions = DB::table('attendance_sessions')
                ->whereNotNull('school_year_id')
                ->whereNotIn('school_year_id', $validSchoolYearIds)
                ->count();
                
            if ($invalidSessions > 0) {
                $this->warn("Found {$invalidSessions} attendance sessions with invalid school_year_id");
                DB::table('attendance_sessions')
                    ->whereNotNull('school_year_id')
                    ->whereNotIn('school_year_id', $validSchoolYearIds)
                    ->update(['school_year_id' => null]);
                $this->info("Fixed invalid school_year_id references in attendance_sessions table");
            } else {
                $this->info("Attendance sessions table: No invalid references found");
            }
        }
        
        $this->info('Data integrity check completed!');
    }
}
