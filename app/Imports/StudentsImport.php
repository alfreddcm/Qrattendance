<?php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Student;

class StudentsImport implements ToModel
{
    protected $userId;
    
    public function __construct($userId)
    {
        $this->userId = $userId;
    }
    
    public function model(array $row)
    {
        return new Student([
            'id_no'   => $row[0],
            'name'    => $row[1],
            'section' => $row[2] ?? 'Default Section',
            'grade_level' => $row[3] ?? 'Grade 10',
            'gender'  => $row[4],
            'age'     => $row[5],
            'address' => $row[6],
            'cp_no'   => $row[7],
            'contact_person_name' => $row[8] ?? null,
            'contact_person_relationship' => $row[9] ?? null,
            'contact_person_contact' => $row[10] ?? null,
            'semester_id' => $row[11] ?? 1, // Default to semester 1 if not provided
            'user_id' => $this->userId,
        ]);
    }
}