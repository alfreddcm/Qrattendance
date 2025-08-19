<?php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\Student;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudentsImport implements ToModel, WithHeadingRow, WithBatchInserts, WithValidation
{
    protected $userId;
    protected $semesterId;
    protected $createdSections = [];
    protected $errors = [];
    
    public function __construct($userId, $semesterId = null)
    {
        $this->userId = $userId;
        $this->semesterId = $semesterId ?? 1;
    }
    
    public function model(array $row)
    {
        try {
            DB::beginTransaction();
            
            // Get or create section
            $sectionId = $this->getOrCreateSection($row['section_name'] ?? null, $row['grade_level'] ?? null);
            
            $student = new Student([
                'id_no' => $row['id_no'] ?? $row['student_id'],
                'name' => $row['name'] ?? $row['full_name'],
                'section_id' => $sectionId,
                'gender' => strtoupper(substr($row['gender'] ?? 'M', 0, 1)),
                'age' => $row['age'] ?? 16,
                'address' => $row['address'] ?? 'N/A',
                'cp_no' => $row['contact_number'] ?? $row['cp_no'] ?? $row['phone'],
                'contact_person_name' => $row['emergency_contact_name'] ?? $row['contact_person_name'],
                'contact_person_relationship' => $row['emergency_contact_relationship'] ?? $row['contact_person_relationship'] ?? 'Parent',
                'contact_person_contact' => $row['emergency_contact_number'] ?? $row['contact_person_contact'],
                'semester_id' => $this->semesterId,
                'user_id' => $this->userId,
            ]);
            
            DB::commit();
            
            Log::info('Student imported successfully', [
                'student_id' => $student->id_no,
                'name' => $student->name,
                'section_id' => $sectionId
            ]);
            
            return $student;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error importing student', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
            
            $this->errors[] = [
                'row' => $row,
                'error' => $e->getMessage()
            ];
            
            return null;
        }
    }
    
    protected function getOrCreateSection($sectionName, $gradeLevel)
    {
        // Default values if not provided
        $sectionName = $sectionName ?? 'Imported Section';
        $gradeLevel = $gradeLevel ?? 10;
        
        // Create a unique key for this section
        $sectionKey = strtolower($sectionName . '_' . $gradeLevel);
        
        // Check if we already created this section in this import
        if (isset($this->createdSections[$sectionKey])) {
            return $this->createdSections[$sectionKey];
        }
        
        // Try to find existing section
        $section = Section::where('name', $sectionName)
                         ->where('gradelevel', $gradeLevel)
                         ->first();
        
        if (!$section) {
            // Create new section
            $section = Section::create([
                'name' => $sectionName,
                'gradelevel' => $gradeLevel,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Created new section during import', [
                'section_id' => $section->id,
                'name' => $sectionName,
                'grade_level' => $gradeLevel
            ]);
        }
        
        // Assign section to the importing teacher if not already assigned
        $teacher = User::find($this->userId);
        if ($teacher && !$teacher->sections()->where('section_id', $section->id)->exists()) {
            $teacher->sections()->attach($section->id);
            
            Log::info('Assigned section to teacher during import', [
                'teacher_id' => $this->userId,
                'section_id' => $section->id
            ]);
        }
        
        // Cache the section ID for this import session
        $this->createdSections[$sectionKey] = $section->id;
        
        return $section->id;
    }
    
    public function batchSize(): int
    {
        return 100; // Process 100 rows at a time
    }
    
    public function rules(): array
    {
        return [
            '*.id_no' => ['required', 'string', 'max:50'],
            '*.student_id' => ['required_without:*.id_no', 'string', 'max:50'],
            '*.name' => ['required', 'string', 'max:255'],
            '*.full_name' => ['required_without:*.name', 'string', 'max:255'],
            '*.gender' => ['nullable', 'string', 'in:M,F,Male,Female,m,f,male,female'],
            '*.age' => ['nullable', 'integer', 'min:1', 'max:100'],
            '*.grade_level' => ['nullable', 'integer', 'min:7', 'max:12'],
        ];
    }
    
    public function customValidationMessages()
    {
        return [
            '*.id_no.required' => 'Student ID is required',
            '*.name.required' => 'Student name is required',
            '*.gender.in' => 'Gender must be M, F, Male, or Female',
            '*.age.integer' => 'Age must be a number',
            '*.grade_level.integer' => 'Grade level must be a number between 7 and 12',
        ];
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function getCreatedSections()
    {
        return $this->createdSections;
    }
}