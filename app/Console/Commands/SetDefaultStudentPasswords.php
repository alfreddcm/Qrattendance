<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetDefaultStudentPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:set-default-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default passwords (hashed LRN) for students who don\'t have one yet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $students = Student::whereNull('password')->get();
        
        if ($students->isEmpty()) {
            $this->info('No students without passwords found.');
            return;
        }

        $this->info("Found {$students->count()} students without passwords.");
        $updated = 0;

        foreach ($students as $student) {
            if ($student->id_no) {
                $student->update([
                    'password' => Hash::make($student->id_no),
                    'password_changed' => false,
                ]);
                $updated++;
                $this->line("✓ Set password for {$student->name} ({$student->id_no})");
            } else {
                $this->warn("⚠ Skipped {$student->name} - no LRN available");
            }
        }

        $this->info("Successfully updated $updated student(s).");
    }
}
