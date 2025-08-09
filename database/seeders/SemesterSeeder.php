<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semester;

class SemesterSeeder extends Seeder
{
    public function run()
    {
        Semester::create([
            'name' => '1st Semester 2025',
            'start_date' => '2025-06-01',
            'end_date' => '2025-10-31',
            'status' => 'active',
            'school_id' => 1,
            'am_time_in_start' => '07:30:00',
            'am_time_in_end' => '08:30:00',
            'pm_time_out_start' => '16:30:00',
            'pm_time_out_end' => '17:30:00',
        ]);
    }
}