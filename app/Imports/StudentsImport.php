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
            'gender'  => $row[2],
            'age'     => $row[3],
            'address' => $row[4],
            'cp_no'   => $row[5],
            'contact_person_name' => $row[6] ?? null,
            'contact_person_relationship' => $row[7] ?? null,
            'contact_person_contact' => $row[8] ?? null,
            'semester_id' => $row[9] ?? 1, // Default to semester 1 if not provided
            'user_id' => $this->userId,
        ]);
    }
}