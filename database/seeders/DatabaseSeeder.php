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
        // Seed schools first
        $this->call(SchoolSeeder::class);
        
        // Seed users after schools so we can assign school_id
        $this->call(UsersSeeder::class);

        // Seed semesters
        $this->call([
            SemesterSeeder::class,
        ]);

        // Get the first semester's id (or set as needed)
        $semester = Semester::first();
        $semesterId = $semester->id ?? 1;

        // Get the school ID from the schools table
        $school = \App\Models\School::where('id', '1')->first();
        $schoolId = $school ? $school->id : '1';

        // Get available users
        $users = User::all()->pluck('id')->toArray();

        // Prepare students data
        $students = [
            [
                'id_no' => '0001',
                'name' => 'Maria Angelica R. Dela Cruz',
                'gender' => 'F',
                'age' => 16,
                'address' => 'Purok 2, Barangay',
                'cp_no' => '09171234567',
                'semester_id' => $semesterId,
                'user_id' => 2, // Assign to Teacher One
                'school_id' => $schoolId,
            ],
            [
                'id_no' => '0002',
                'name' => 'Juan Miguel S. Santos',
                'gender' => 'M',
                'age' => 17,
                'address' => 'Zone 1, Barangay',
                'cp_no' => '09987654321',
                'semester_id' => $semesterId,
                'user_id' => 2, // Assign to Teacher One
                'school_id' => $schoolId,
            ],
            [
                'id_no' => '0003',
                'name' => 'Kristine Anne M. Reye',
                'gender' => 'F',
                'age' => 16,
                'address' => 'Sitio Lintungan, Barangay',
                'cp_no' => '09562345678',
                'semester_id' => $semesterId,
                'user_id' => 3, // Assign to Teacher Two
                'school_id' => $schoolId,
            ],
            [
                'id_no' => '0004',
                'name' => 'Paolo Emmanuel G. Navar',
                'gender' => 'M',
                'age' => 17,
                'address' => 'Purok 5, Barangay',
                'cp_no' => '09613456789',
                'semester_id' => $semesterId,
                'user_id' => 3, // Assign to Teacher Two
                'school_id' => $schoolId,
            ],
            [
                'id_no' => '0005',
                'name' => 'Catherine Louise B. Cruz',
                'gender' => 'F',
                'age' => 17,
                'address' => 'Purok 1, Barangay',
                'cp_no' => '09491234567',
                'semester_id' => $semesterId,
                'user_id' => 4, // Assign to Teacher Three
                'school_id' => $schoolId,
            ],
        ];

        // Add 50 more students with fake data
        $teacherIds = [2, 3, 4]; // Only assign to teachers, not admin
        for ($i = 6; $i <= 55; $i++) {
            $id_no = str_pad($i, 4, '0', STR_PAD_LEFT);
            $gender = $i % 2 === 0 ? 'M' : 'F';
            $name = $gender === 'F'
                ? fake()->name('female')
                : fake()->name('male');

            $students[] = [
                'id_no' => $id_no,
                'name' => $name,
                'gender' => $gender,
                'age' => rand(16, 18),
                'address' => fake()->address(),
                'cp_no' => '09' . rand(100000000, 999999999),
                'semester_id' => $semesterId,
                'user_id' => $teacherIds[array_rand($teacherIds)], // Random teacher from available teachers
                'school_id' => $schoolId,
            ];
        }

        unset($student);

        Student::insert($students);

        // Seed attendance records
        $this->call([
            AttendanceSeeder::class,
        ]);
    }
}
