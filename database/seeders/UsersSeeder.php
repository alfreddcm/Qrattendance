<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\School;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('id', '1')->first();
        $schoolId = $school ? $school->id : '1';

        DB::table('users')->insert([

            [
                'name' => 'Admin User',
                'username' => 'adminuser',
                'email' => 'admin@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone_number' => '09123456789',
                'position' => 'Administrator',
                'school_id' => $schoolId,
                'created_at' => now(),
                'updated_at' => now(),
            ],[
                'name' => 'Pagalan, Mark Anthony',
                'username' => 'mark',
                'email' => 'teacher1@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'phone_number' => '09987654321',
                'position' => 'Teacher',
                'school_id' => $schoolId,
                'created_at' => now(),
                'updated_at' => now(),
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
                'created_at' => now(),
                'updated_at' => now(),
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
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}