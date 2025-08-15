<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Semester;
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
            $startDate = Carbon::parse($semester->start_date);

            // Filipino name pools
            $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Gonzales', 'Ramos', 'Lopez', 'Aquino', 'Morales', 'Castro', 'Flores', 'Villanueva', 'Navarro', 'Domingo', 'Gutierrez', 'Silva'];
            $firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Luisa', 'Carlos', 'Angelica', 'Miguel', 'Kristine', 'Paolo', 'Emmanuel', 'Andrea', 'Marco', 'Catherine', 'Francis', 'Isabel', 'Alfred', 'Jasmine', 'Rafael'];
            $middleInitials = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
            $sections = ['A', 'B', 'C', 'D'];
            $genders = ['M', 'F'];

            $students = [];
            for ($i = 1; $i <= 30; $i++) {
                $ln = $lastNames[array_rand($lastNames)];
                $fn = $firstNames[array_rand($firstNames)];
                $mi = $middleInitials[array_rand($middleInitials)];
                $name = "$ln, $fn $mi.";
                $gender = $genders[array_rand($genders)];
                $age = rand(16, 19);
                $section = $sections[array_rand($sections)];
                $id_no = str_pad($i, 4, '0', STR_PAD_LEFT);
                $address = 'Barangay ' . chr(65 + ($i % 26)) . ', City';
                $cp_no = '09' . rand(10, 99) . rand(1000000, 9999999);
                $contact_person_name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
                $contact_person_relationship = 'Parent';
                $contact_person_contact = '09' . rand(10, 99) . rand(1000000, 9999999);
                $picture = null;
                $qr_code = 'QR' . $id_no;
                $stud_code = 'STUD' . $id_no;
                $user_id = 2;
                $school_id = 1; 
                $semester_id = $semester->id;

                $students[] = Student::create([
                    'id_no' => $id_no,
                    'name' => $name,
                    'gender' => $gender,
                    'age' => $age,
                    'address' => $address,
                    'cp_no' => $cp_no,
                    'picture' => $picture,
                    'contact_person_name' => $contact_person_name,
                    'contact_person_relationship' => $contact_person_relationship,
                    'contact_person_contact' => $contact_person_contact,
                    'semester_id' => $semester_id,
                    'user_id' => $user_id,
                    'school_id' => $school_id,
                    'qr_code' => $qr_code,
                    'stud_code' => $stud_code,
                ]);
            }

            $endDate = Carbon::parse($semester->end_date);
            $period = $startDate->copy();
            while ($period->lte($endDate)) {
                $dates[] = $period->copy();
                $period->addDay();
            }

            foreach ($students as $student) {
                foreach ($dates as $date) {
                    // Randomly decide if student is absent (20% chance)
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
                    // Randomize lateness (30% chance late in AM)
                    $late = rand(1, 100) <= 30;
                    $am_in_hour = $late ? rand(8, 9) : rand(7, 7);
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
}
