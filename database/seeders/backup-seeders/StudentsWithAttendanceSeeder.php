<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\User;
use App\Models\Attendance;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentsWithAttendanceSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            $schoolYear = SchoolYear::first();
            if (!$schoolYear) {
                throw new \Exception('No semester found.');
            }

            if (empty($schoolYear->start_date) || empty($schoolYear->end_date)) {
                throw new \Exception('School year does not have start_date or end_date set.');
            }

            $stemSection = Section::where('name', 'STEM')->where('gradelevel', 11)->first();
            $hummsSection = Section::where('name', 'HUMMS')->where('gradelevel', 12)->first();
            
            if (!$stemSection || !$hummsSection) {
                throw new \Exception('Sections not found. Please run SectionSeeder first.');
            }

            // Get teachers for each section
            $stemTeacher = User::where('role', 'teacher')->where('id', $stemSection->teacher_id)->first();
            $hummsTeacher = User::where('role', 'teacher')->where('id', $hummsSection->teacher_id)->first();
            
            if (!$stemTeacher || !$hummsTeacher) {
                // Fallback: get available teachers
                $teachers = User::where('role', 'teacher')->get();
                if ($teachers->count() < 2) {
                    throw new \Exception('Not enough teachers found. Please run UsersSeeder first.');
                }
                $stemTeacher = $stemTeacher ?? $teachers->first();
                $hummsTeacher = $hummsTeacher ?? $teachers->skip(1)->first();
            }

            $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Gonzales', 'Ramos', 'Lopez', 'Aquino', 'Morales', 'Castro', 'Flores', 'Villanueva', 'Navarro', 'Domingo', 'Gutierrez', 'Silva'];
            $firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Luisa', 'Carlos', 'Angelica', 'Miguel', 'Kristine', 'Paolo', 'Emmanuel', 'Andrea', 'Marco', 'Catherine', 'Francis', 'Isabel', 'Alfred', 'Jasmine', 'Rafael'];
            $middleInitials = range('A', 'T');
            $genders = ['M', 'F'];

            $students = [];

            for ($i = 1; $i <= 50; $i++) {
                $students[] = Student::create($this->generateStudentData($i, $schoolYear->id, $stemSection->id, $stemTeacher->id, $lastNames, $firstNames, $middleInitials, $genders));
            }

            for ($i = 51; $i <= 100; $i++) {
                $students[] = Student::create($this->generateStudentData($i, $schoolYear->id, $hummsSection->id, $hummsTeacher->id, $lastNames, $firstNames, $middleInitials, $genders));
            }

            // Use school year start and end dates to generate attendance dates (exclude weekends)
            $startDate = Carbon::parse($schoolYear->start_date)->startOfDay();
            $endDate = Carbon::parse($schoolYear->end_date)->endOfDay();

            if ($startDate->gt($endDate)) {
                throw new \Exception('School year start_date is after end_date.');
            }

            $dates = [];
            $period = $startDate->copy();
            while ($period->lte($endDate)) {
                if (!$period->isWeekend()) {
                    $dates[] = $period->copy();
                }
                $period->addDay();
            }

            foreach ($students as $student) {
                foreach ($dates as $date) {
                    if (rand(1, 100) <= 20) {
                        Attendance::create([
                            'school_year_id' => $student->school_year_id,
                            'student_id' => $student->id,
                            'school_id' => $student->school_id,
                            'teacher_id' => $student->user_id,
                            'date' => $date->toDateString(),
                            'time_in_am' => null,
                            'time_out_am' => null,
                            'time_in_pm' => null,
                            'time_out_pm' => null,
                        ]);
                        continue;
                    }

                    $late = rand(1, 100) <= 30;
                    $am_in_hour = $late ? rand(8, 9) : 7;
                    $am_in_min = rand(0, 59);
                    $am_in = sprintf('%02d:%02d:00', $am_in_hour, $am_in_min);
                    $am_out = sprintf('%02d:%02d:00', rand(11, 12), rand(0, 59));
                    $pm_in = sprintf('%02d:%02d:00', rand(13, 14), rand(0, 59));
                    $pm_out = sprintf('%02d:%02d:00', rand(15, 16), rand(0, 59));

                    Attendance::create([
                        'school_year_id' => $student->school_year_id,
                        'student_id' => $student->id,
                        'school_id' => $student->school_id,
                        'teacher_id' => $student->user_id,
                        'date' => $date->toDateString(),
                        'time_in_am' => $am_in,
                        'time_out_am' => $am_out,
                        'time_in_pm' => $pm_in,
                        'time_out_pm' => $pm_out,
                    ]);
                }
            }
        });
    }

    private function generateStudentData($id, $schoolYear_id, $section_id, $teacher_id, $lastNames, $firstNames, $middleInitials, $genders)
    {
        $ln = $lastNames[array_rand($lastNames)];
        $fn = $firstNames[array_rand($firstNames)];
        $mi = $middleInitials[array_rand($middleInitials)];
        $gender = $genders[array_rand($genders)];
        $address = 'Barangay ' . chr(65 + ($id % 26)) . ', City';
        $cp_no = '09' . rand(10, 99) . rand(1000000, 9999999);
        $contact_person_name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        $contact_person_contact = '09' . rand(10, 99) . rand(1000000, 9999999);
        $id_no = str_pad($id, 4, '0', STR_PAD_LEFT);

        return [
            'id_no' => $id_no,
            'name' => "$ln, $fn $mi.",
            'gender' => $gender,
            'age' => rand(16, 19),
            'address' => $address,
            'cp_no' => $cp_no,
            'picture' => null,
            'contact_person_name' => $contact_person_name,
            'contact_person_relationship' => 'Parent',
            'contact_person_contact' => $contact_person_contact,
            'school_year_id' => $schoolYear_id,
            'section_id' => $section_id,
            'user_id' => $teacher_id,
            'school_id' => 1,
            'qr_code' => 'QR' . $id_no,
            'stud_code' => 'STUD' . $id_no,
        ];
    }
}
