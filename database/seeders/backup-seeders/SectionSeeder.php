<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\User;
use App\Models\SchoolYear;

class SectionSeeder extends Seeder
{
    public function run()
    {
         $teacher = User::where('role', 'teacher')->first();
        $schoolYear = SchoolYear::first();
        
        if (!$teacher || !$schoolYear) {
            $this->command->info('Please create a teacher user and semester first');
            return;
        }

         Section::create([
            'name' => 'STEM',
            'gradelevel' => 11,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'am_time_in_start' => '07:30:00',
            'am_time_in_end' => '08:00:00',
            'am_time_out_start' => '11:30:00',
            'am_time_out_end' => '12:00:00',
            'pm_time_in_start' => '13:00:00',
            'pm_time_in_end' => '13:30:00',
            'pm_time_out_start' => '16:30:00',
            'pm_time_out_end' => '17:00:00',
        ]);

        Section::create([
            'name' => 'HUMMS',
            'gradelevel' => 12,
            'teacher_id' => $teacher->id,
            'school_year_id' => $schoolYear->id,
            'am_time_in_start' => '07:30:00',
            'am_time_in_end' => '08:00:00',
            'am_time_out_start' => '11:30:00',
            'am_time_out_end' => '12:00:00',
            'pm_time_in_start' => '13:00:00',
            'pm_time_in_end' => '13:30:00',
            'pm_time_out_start' => '16:30:00',
            'pm_time_out_end' => '17:00:00',
        ]);

        $this->command->info('Sections created successfully!');
    }
}
