<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Semester;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SchoolSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call([
            SemesterSeeder::class,
        ]);

        $semester = Semester::first();
        $semesterId = $semester->id ?? 1;

        $school = \App\Models\School::where('id', '1')->first();
        $schoolId = $school ? $school->id : '1';

        // Seed attendance records
        $this->call([
            StudentsWithAttendanceSeeder::class,
        ]);
    }
}
