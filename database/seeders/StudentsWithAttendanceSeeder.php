<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Semester;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentsWithAttendanceSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Get the first semester
            $semester = Semester::first();
            if (!$semester) {
                throw new \Exception('No semester found.');
            }

            // Get sections
            $stemSection = Section::where('name', 'STEM')->where('gradelevel', 11)->first();
            $hummsSection = Section::where('name', 'HUMMS')->where('gradelevel', 12)->first();
            
            if (!$stemSection || !$hummsSection) {
                throw new \Exception('Sections not found. Please run SectionSeeder first.');
            }

            // Filipino name pools
            $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Gonzales', 'Ramos', 'Lopez', 'Aquino', 'Morales', 'Castro', 'Flores', 'Villanueva', 'Navarro', 'Domingo', 'Gutierrez', 'Silva'];
            $firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Luisa', 'Carlos', 'Angelica', 'Miguel', 'Kristine', 'Paolo', 'Emmanuel', 'Andrea', 'Marco', 'Catherine', 'Francis', 'Isabel', 'Alfred', 'Jasmine', 'Rafael'];
            $middleInitials = range('A', 'T');
            $genders = ['M', 'F'];

            $students = [];

            // 50 students - Grade 11 STEM
            for ($i = 1; $i <= 50; $i++) {
                $students[] = Student::create($this->generateStudentData($i, $semester->id, $stemSection->id, $lastNames, $firstNames, $middleInitials, $genders));
            }

            // 50 students - Grade 12 HUMMS
            for ($i = 51; $i <= 100; $i++) {
                $students[] = Student::create($this->generateStudentData($i, $semester->id, $hummsSection->id, $lastNames, $firstNames, $middleInitials, $genders));
            }

            // Attendance date range: June 2 to Aug 8
            $startDate = Carbon::create(null, 6, 2);
            $endDate = Carbon::create(null, 8, 8);
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
                    // 20% chance absent
                    if (rand(1, 100) <= 20) {
                        Attendance::create([
                            'semester_id' => $student->semester_id,
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

                    // 30% chance late in AM
                    $late = rand(1, 100) <= 30;
                    $am_in_hour = $late ? rand(8, 9) : 7;
                    $am_in_min = rand(0, 59);
                    $am_in = sprintf('%02d:%02d:00', $am_in_hour, $am_in_min);
                    $am_out = sprintf('%02d:%02d:00', rand(11, 12), rand(0, 59));
                    $pm_in = sprintf('%02d:%02d:00', rand(13, 14), rand(0, 59));
                    $pm_out = sprintf('%02d:%02d:00', rand(15, 16), rand(0, 59));

                    Attendance::create([
                        'semester_id' => $student->semester_id,
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

    private function generateStudentData($id, $semester_id, $section_id, $lastNames, $firstNames, $middleInitials, $genders)
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
            'semester_id' => $semester_id,
            'section_id' => $section_id,
            'user_id' => 2,
            'school_id' => 1,
            'qr_code' => 'QR' . $id_no,
            'stud_code' => 'STUD' . $id_no,
        ];
    }
}
