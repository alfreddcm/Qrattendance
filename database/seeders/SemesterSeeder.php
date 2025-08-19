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
                'start_date' => '2025-06-02',
                'end_date' => '2025-08-29',
                'status' => 'active',
                'school_id' => 1,
                'morning_period_start' => '07:30:00',
                'morning_period_end' => '12:00:00',
                'afternoon_period_start' => '13:00:00',
                'afternoon_period_end' => '17:00:00',
        ]);

    }
}