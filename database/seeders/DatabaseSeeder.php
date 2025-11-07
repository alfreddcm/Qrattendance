<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolYear;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SchoolSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(SchoolYearsSeeder::class);
        $this->call(SectionSeeder::class);
        $this->call(SectionTeacherSeeder::class);
        $this->call(AttendanceCodesSeeder::class);
        $this->call(StudentsWithAttendanceSeeder::class);
        $this->call(OutboundMessagesSeeder::class);
    }
}
