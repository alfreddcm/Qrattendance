<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCode;
use App\Models\Section;
use App\Models\User;

class AttendanceCodesSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $section = Section::first();
        if (!$teacher) {
            $this->command->info('No teacher found for AttendanceCodesSeeder');
            return;
        }

        AttendanceCode::create([
            'teacher_id' => $teacher->id,
            'section_id' => $section ? $section->id : null,
            'code' => 'ABC123',
            'qr_code_path' => null,
            'is_active' => true,
        ]);

        AttendanceCode::create([
            'teacher_id' => $teacher->id,
            'section_id' => $section ? $section->id : null,
            'code' => 'XYZ789',
            'qr_code_path' => null,
            'is_active' => true,
        ]);
    }
}
