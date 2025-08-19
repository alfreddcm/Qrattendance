<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('id', '1')->first();
        $schoolId = $school ? $school->id : '1';

        // Clear existing pivot relationships first (optional)
        // DB::table('section_teacher')->truncate();

         $users = [
            [
                'name' => 'Admin User',
                'username' => 'adminuser',
                'email' => 'admin@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone_number' => '09123456789',
                'position' => 'Administrator',
                'school_id' => $schoolId,
            ],
            [
                'name' => 'Pagalan, Mark Anthony',
                'username' => 'mark',
                'email' => 'teacher1@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone_number' => '09987654321',
                'position' => 'Teacher',
                'school_id' => $schoolId,
            ],
            [
                'name' => 'Teacher Two',
                'username' => '3_Teacher_Two',
                'email' => 'teacher2@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone_number' => '09111222333',
                'position' => 'Teacher',
                'school_id' => $schoolId,
            ],
            [
                'name' => 'Teacher Three',
                'username' => 'teacher3',
                'email' => 'teacher3@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone_number' => '09444555666',
                'position' => 'Teacher',
                'school_id' => $schoolId,
            ],
        ];

         foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],  
                $userData  
            );
        }

         $teacher = User::where('username', 'mark')->first();
        if ($teacher && $teacher->role === 'teacher') {
             $teacher->sections()->sync([1, 2]);
        }
    }
}