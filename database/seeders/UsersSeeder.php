<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'username' => 'adminuser',
                'email' => 'admin@sgvs.edu',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone_number' => '09123456789',
                'position' => 'Administrator',
                'school_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}