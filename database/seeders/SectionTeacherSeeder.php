<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SectionTeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $section = Section::first();
        if (!$teacher || !$section) {
            $this->command->info('No teacher or section found for SectionTeacherSeeder');
            return;
        }

        DB::table('section_teacher')->updateOrInsert([
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
