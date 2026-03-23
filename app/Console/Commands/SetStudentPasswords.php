<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class SetStudentPasswords extends Command
{
    protected $signature = 'students:set-password {--id_no= : Set password for specific student} {--school-id= : Set passwords for all students in a school} {--password= : Password to set}';
    protected $description = 'Set initial passwords for student accounts';

    public function handle()
    {
        $idNo = $this->option('id_no');
        $schoolId = $this->option('school-id');
        $password = $this->option('password');

        // If no password provided, use ID number as default
        if (!$password) {
            $password = null;
        }

        if ($idNo) {
            $this->setSingleStudentPassword($idNo, $password);
        } elseif ($schoolId) {
            $this->setSchoolStudentsPasswords($schoolId, $password);
        } else {
            $this->setAllStudentsPasswords($password);
        }
    }

    private function setSingleStudentPassword($idNo, $password = null)
    {
        $student = Student::where('id_no', $idNo)->first();

        if (!$student) {
            $this->error("Student with ID '{$idNo}' not found.");
            return;
        }

        $newPassword = $password ?? $idNo;
        $student->update(['password' => Hash::make($newPassword)]);

        $this->info("Password set for student: {$student->name} ({$idNo})");
        if (!$password) {
            $this->line("Password: {$newPassword}");
        }
    }

    private function setSchoolStudentsPasswords($schoolId, $password = null)
    {
        $query = Student::where('school_id', $schoolId)->whereNull('password');
        $count = $query->count();

        if ($count === 0) {
            $this->info('All students in this school already have passwords set.');
            return;
        }

        if (!$this->confirm("Set passwords for {$count} students? (Default password will be their ID number)")) {
            return;
        }

        foreach ($query->get() as $student) {
            $newPassword = $password ?? $student->id_no;
            $student->update(['password' => Hash::make($newPassword)]);
            $this->line("✓ {$student->name} ({$student->id_no})");
        }

        $this->info("Passwords set for {$count} students.");
    }

    private function setAllStudentsPasswords($password = null)
    {
        $query = Student::whereNull('password');
        $count = $query->count();

        if ($count === 0) {
            $this->info('All students already have passwords set.');
            return;
        }

        if (!$this->confirm("Set passwords for {$count} students? (Default password will be their ID number)")) {
            return;
        }

        $bar = $this->output->createProgressBar($count);

        foreach ($query->get() as $student) {
            $newPassword = $password ?? $student->id_no;
            $student->update(['password' => Hash::make($newPassword)]);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nPasswords set for {$count} students.");
    }
}
