<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Semester;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ValidatesForResponse;
 

class ImportController extends Controller
{
    use ValidatesForResponse;
    public function showUploadForm()
    {
        return view('import.upload');
    }

    public function preview(Request $request)
    {
        try {
            $validated = $this->validateForResponse($request, [
                'file' => 'required|mimes:xlsx,xls,csv'
            ]);

            if (is_object($validated)) {
                return $validated;
            }

            if (!$request->hasFile('file')) {
                return redirect()->back()->with('error', 'No file was uploaded. Please select a file to import.');
            }

            $file = $request->file('file');
            if (!$file->isValid()) {
                return redirect()->back()->with('error', 'The uploaded file is invalid or corrupted.');
            }

            $path = $file->store('imports', 'public');
            $fullPath = Storage::disk('public')->path($path);
            
            if (!file_exists($fullPath)) {
                return redirect()->back()->with('error', 'Failed to save the uploaded file. Please try again.');
            }
            
            $data = Excel::toArray([], $fullPath);
            
             if (empty($data) || empty($data[0])) {
                 Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'The uploaded file is empty or contains no valid data.');
            }
            
             if (count($data[0]) < 2) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'The file must contain at least a header row and one data row.');
            }
            
             $headerRow = $data[0][0];
            if (count($headerRow) < 4) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'Invalid file format. The file must contain at least 4 columns (ID, Name, Gender, Age).');
            }
            
            $semesters = Semester::all();
            if ($semesters->isEmpty()) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'No semesters found in the system. Please create a semester first.');
            }

            $user = Auth::user();
            $teachers = [];
            $schools = [];
            $userSections = collect();
            $currentSchoolId = null;
            
            if ($user->role === 'admin') {
                $teachers = \App\Models\User::where('role', 'teacher')->get();
                $schools = \App\Models\School::all();
            } elseif ($user->role === 'teacher') {
                $currentSchoolId = $user->school_id;
                // Get sections for the teacher
                $userSections = \App\Models\Section::whereHas('teachers', function($query) use ($user) {
                    $query->where('users.id', $user->id);
                })->orWhere('teacher_id', $user->id)->get();
            }

            return view('import.preview', [
                'data' => $data[0],
                'file' => $path,
                'semesters' => $semesters,
                'teachers' => $teachers,
                'schools' => $schools,
                'userSections' => $userSections,
                'currentSchoolId' => $currentSchoolId
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Invalid file format. Please upload an Excel (.xlsx, .xls) or CSV file.');
        } catch (\Exception $e) {
            \Log::error('Import preview error: ' . $e->getMessage(), [
                'file' => $request->file('file')?->getClientOriginalName(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error processing the file: ' . $e->getMessage() . '. Please check the file format and try again.');
        }
    }

     public function import(Request $request)
    {
        try {
            \Log::info('Import process started', [
                'user_id' => $request->input('user_id'),
                'semester_id' => $request->input('semester_id'),
                'section_id' => $request->input('section_id'),
                'selectedUserId' => $request->input('selectedUserId'),
                'selectedSectionId' => $request->input('selectedSectionId'),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            $students = $request->input('students');  
            $semester_id = $request->input('semester_id'); 
            $user_id = $request->input('user_id');
            
             $section_id = null;
            if (Auth::user()->role === 'admin') {
                 $section_id = $request->input('selectedSectionId');
                $user_id = $request->input('selectedUserId') ?? $user_id;
            } else {
                 $section_id = $request->input('section_id');
            }

            $school_id = User::where('id', $user_id)->value('school_id');

            if (!$students || !$semester_id || !$user_id) {
                \Log::warning('Import failed: Missing students data or semester selection', [
                    'user_id' => $user_id,
                    'semester_id' => $semester_id,
                    'section_id' => $section_id
                ]);
                return redirect()->back()->with('error', 'Missing students data or semester selection. Please go back and try again.');
            }

            if (!$section_id) {
                \Log::warning('Import failed: No section selected', [
                    'user_id' => $user_id,
                    'semester_id' => $semester_id
                ]);
                return redirect()->back()->with('error', 'Please select a section for the students.');
            }

             $semester = Semester::find($semester_id);
            if (!$semester) {
                \Log::warning('Import failed: Selected semester does not exist', [
                    'semester_id' => $semester_id
                ]);
                return redirect()->back()->with('error', 'Selected semester does not exist. Please select a valid semester.');
            }

             if (!Auth::check()) {
                \Log::warning('Import failed: User not authenticated', [
                    'user_id' => $user_id
                ]);
                return redirect()->route('login')->with('error', 'You must be logged in to import students.');
            }

            $added = 0;
            $skipped = 0;
            $errors = [];
            $warnings = [];

            foreach ($students as $index => $row) {
                try {
                     if (empty(array_filter($row))) {
                        continue;
                    }

                     if (empty(trim($row[0] ?? '')) || empty(trim($row[1] ?? '')) || empty(trim($row[2] ?? '')) || empty(trim($row[3] ?? ''))) {
                        $errors[] = "Row " . ($index + 1) . ": Missing required fields (ID, Name, Gender, or Age)";
                        \Log::warning('Import row skipped: Missing required fields', [
                            'row' => $index + 1,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                     $idNo = trim($row[0]);
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $idNo)) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid ID format. Only letters and numbers are allowed.";
                        \Log::warning('Import row skipped: Invalid ID format', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                     $age = trim($row[3]);
                    if (!is_numeric($age) || $age < 1 || $age > 100) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid age. Age must be between 1 and 100.";
                        \Log::warning('Import row skipped: Invalid age', [
                            'row' => $index + 1,
                            'age' => $age,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                    $existingStudent = Student::where('id_no', $idNo)
                        ->where('semester_id', $semester_id)
                        ->where('user_id', $user_id)
                        ->first();

                    $cpNo = isset($row[5]) ? $this->formatPhoneNumber($this->cleanTabPrefix($row[5])) : null;
                    $contactPersonContact = isset($row[7]) ? $this->formatPhoneNumber($this->cleanTabPrefix($row[7])) : null;

                    $gender = $this->normalizeGender($row[2]);
                    if (!in_array($gender, ['M', 'F'])) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid gender format. Use M/F or Male/Female.";
                        \Log::warning('Import row skipped: Invalid gender format', [
                            'row' => $index + 1,
                            'gender' => $row[2],
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                    $name = trim($row[1]);
                    if (strlen($name) > 255) {
                        $errors[] = "Row " . ($index + 1) . ": Name is too long (maximum 255 characters).";
                        \Log::warning('Import row skipped: Name too long', [
                            'row' => $index + 1,
                            'name' => $name,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                    $studentData = [
                        'id_no'                         => $idNo,
                        'name'                          => $name,
                        'gender'                        => $gender,
                        'age'                           => (int)$age,
                        'address'                       => isset($row[4]) ? trim(substr($row[4], 0, 255)) : '',
                        'cp_no'                         => $cpNo,
                        'contact_person_name'           => isset($row[6]) ? trim(substr($row[6], 0, 255)) : null,
                        'contact_person_relationship'   => isset($row[8]) ? trim(substr($row[8], 0, 255)) : null,
                        'contact_person_contact'        => $contactPersonContact,
                        'semester_id'                   => $semester_id,
                        'section_id'                    => $section_id,
                        'user_id'                       => $user_id,
                        'school_id'                     => $school_id
                    ];

                    if ($existingStudent) {
                        $hasChanges = $this->hasStudentDataChanged($existingStudent, $studentData);
                        
                        if ($hasChanges) {
                             $existingStudent->update($studentData);
                            $added++;
                            $warnings[] = "Student with ID {$idNo} was updated with new information";
                            \Log::info('Student updated during import', [
                                'id_no' => $idNo,
                                'user_id' => $user_id,
                                'semester_id' => $semester_id
                            ]);
                        } else {
                            $skipped++;
                            \Log::info('Student skipped (no changes)', [
                                'id_no' => $idNo,
                                'user_id' => $user_id,
                                'semester_id' => $semester_id
                            ]);
                            continue;
                        }
                    } else {
                        Student::create($studentData);
                        $added++;
                        \Log::info('Student created during import', [
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'semester_id' => $semester_id
                        ]);
                    }
                    
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000) { 
                        $errors[] = "Row " . ($index + 1) . ": Duplicate entry or database constraint violation.";
                        \Log::error('Import row error: Duplicate entry or constraint violation', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'semester_id' => $semester_id,
                            'error' => $e->getMessage()
                        ]);
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Database error - " . $e->getMessage();
                        \Log::error('Import row error: Database error', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'semester_id' => $semester_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                    \Log::error('Import row error: General exception', [
                        'row' => $index + 1,
                        'id_no' => $idNo ?? null,
                        'user_id' => $user_id,
                        'semester_id' => $semester_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

             $message = "";
            if ($added > 0) {
                $message = "{$added} student(s) successfully imported/updated.";
            }
            
            if ($skipped > 0) {
                $skipMessage = " {$skipped} record(s) had no changes and were skipped.";
                $message .= $skipMessage;
            }

            if (!empty($errors)) {
                $errorMessage = " Errors encountered: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $errorMessage .= " and " . (count($errors) - 5) . " more errors.";
                }
                
                \Log::warning('Import completed with errors', [
                    'user_id' => $user_id,
                    'semester_id' => $semester_id,
                    'added' => $added,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]);

                if ($added == 0) {
                    return redirect()->route('teacher.students')->with('error', 'Import failed. ' . $errorMessage);
                } else {
                    return redirect()->route('teacher.students')->with('warning', $message . $errorMessage);
                }
            }

            if ($added == 0 && $skipped == 0) {
                \Log::info('Import completed: No students imported', [
                    'user_id' => $user_id,
                    'semester_id' => $semester_id
                ]);
                return redirect()->route('teacher.students')->with('error', 'No students were imported. Please check your file format and data.');
            }

            \Log::info('Import completed successfully', [
                'user_id' => $user_id,
                'semester_id' => $semester_id,
                'added' => $added,
                'skipped' => $skipped
            ]);

            $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
            return redirect()->route($route)->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Import process error: ' . $e->getMessage(), [
                'user' => Auth::id(),
                'file' => $request->file('file')?->getClientOriginalName(),
                'user_id' => $request->input('user_id'),
                'semester_id' => $request->input('semester_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
            return redirect()->route($route)->with('error', 'An unexpected error occurred during import: ' . $e->getMessage() . '. Please try again.');

        }
    }

  
    private function cleanTabPrefix($value)
    {
        if (empty($value)) {
            return $value;
        }
        
        return ltrim($value, "\t");
    }

 
    private function hasStudentDataChanged($existingStudent, $newData)
    {
        $fieldsToCompare = [
            'name', 'gender', 'age', 'address', 'cp_no',
            'contact_person_name', 'contact_person_relationship', 'contact_person_contact'
        ];
        
        foreach ($fieldsToCompare as $field) {
            $existingValue = $existingStudent->{$field} ?? '';
            $newValue = $newData[$field] ?? '';
            
            $existingValue = empty($existingValue) ? '' : (string)$existingValue;
            $newValue = empty($newValue) ? '' : (string)$newValue;
            
            if ($existingValue !== $newValue) {
                return true;
            }
        }
        
        return false;
    }
 
    private function formatPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (strlen($phoneNumber) == 10 && !str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '0' . $phoneNumber;
        }
        
        if (strlen($phoneNumber) < 11) {
            $phoneNumber = str_pad($phoneNumber, 11, '0', STR_PAD_LEFT);
        }
        
        return $phoneNumber;
    }

   
    private function normalizeGender($gender)
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['male', 'm'])) {
            return 'M';
        } elseif (in_array($gender, ['female', 'f'])) {
            return 'F';
        }
        
        return strtoupper(substr($gender, 0, 1));
    }
}