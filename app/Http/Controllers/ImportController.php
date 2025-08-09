<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\Semester;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    public function showUploadForm()
    {
        return view('import.upload');
    }

    public function preview(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv'
            ]);

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

            return view('import.preview', [
                'data' => $data[0], 
                'file' => $path,
                'semesters' => $semesters
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
            $students = $request->input('students');  
            $semester_id = $request->input('semester_id'); 

            if (!$students || !$semester_id) {
                return redirect()->back()->with('error', 'Missing students data or semester selection. Please go back and try again.');
            }

             $semester = Semester::find($semester_id);
            if (!$semester) {
                return redirect()->back()->with('error', 'Selected semester does not exist. Please select a valid semester.');
            }

             if (!Auth::check()) {
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
                        continue;
                    }

                     $idNo = trim($row[0]);
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $idNo)) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid ID format. Only letters and numbers are allowed.";
                        continue;
                    }

                     $age = trim($row[3]);
                    if (!is_numeric($age) || $age < 1 || $age > 100) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid age. Age must be between 1 and 100.";
                        continue;
                    }

                    // Check if student exists and compare data for updates
                    $existingStudent = Student::where('id_no', $idNo)
                        ->where('semester_id', $semester_id)
                        ->where('user_id', Auth::id())
                        ->first();

                    // Format phone numbers (remove tabs if present from export)
                    $cpNo = isset($row[5]) ? $this->formatPhoneNumber($this->cleanTabPrefix($row[5])) : null;
                    $contactPersonContact = isset($row[8]) ? $this->formatPhoneNumber($this->cleanTabPrefix($row[8])) : null;

                    // Convert gender format
                    $gender = $this->normalizeGender($row[2]);
                    if (!in_array($gender, ['M', 'F'])) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid gender format. Use M/F or Male/Female.";
                        continue;
                    }

                    // Validate name length
                    $name = trim($row[1]);
                    if (strlen($name) > 255) {
                        $errors[] = "Row " . ($index + 1) . ": Name is too long (maximum 255 characters).";
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
                        'contact_person_relationship'   => isset($row[7]) ? trim(substr($row[7], 0, 255)) : null,
                        'contact_person_contact'        => $contactPersonContact,
                        'semester_id'                   => $semester_id,
                        'user_id'                       => Auth::id(),
                    ];

                    if ($existingStudent) {
                        // Check if there are any changes
                        $hasChanges = $this->hasStudentDataChanged($existingStudent, $studentData);
                        
                        if ($hasChanges) {
                            // Update existing student
                            $existingStudent->update($studentData);
                            $added++; // Count as added (updated)
                            $warnings[] = "Student with ID {$idNo} was updated with new information";
                        } else {
                            // No changes, skip
                            $skipped++;
                            continue;
                        }
                    } else {
                        // Create new student
                        Student::create($studentData);
                        $added++;
                    }
                    
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000) { 
                        $errors[] = "Row " . ($index + 1) . ": Duplicate entry or database constraint violation.";
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Database error - " . $e->getMessage();
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // Build response message
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
                
                if ($added == 0) {
                    return redirect()->route('teacher.students')->with('error', 'Import failed. ' . $errorMessage);
                } else {
                    return redirect()->route('teacher.students')->with('warning', $message . $errorMessage);
                }
            }

            if ($added == 0 && $skipped == 0) {
                return redirect()->route('teacher.students')->with('error', 'No students were imported. Please check your file format and data.');
            }

            return redirect()->route('teacher.students')->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Import process error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'semester_id' => $request->input('semester_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('teacher.students')->with('error', 'An unexpected error occurred during import: ' . $e->getMessage() . '. Please try again.');
        }
    }

    /**
     * Remove tab prefix that might be added during export to preserve phone number formatting
     */
    private function cleanTabPrefix($value)
    {
        if (empty($value)) {
            return $value;
        }
        
        // Remove leading tab character if present
        return ltrim($value, "\t");
    }

    /**
     * Check if student data has changed compared to existing record
     */
    private function hasStudentDataChanged($existingStudent, $newData)
    {
        // Fields to compare (excluding timestamps and IDs)
        $fieldsToCompare = [
            'name', 'gender', 'age', 'address', 'cp_no',
            'contact_person_name', 'contact_person_relationship', 'contact_person_contact'
        ];
        
        foreach ($fieldsToCompare as $field) {
            $existingValue = $existingStudent->{$field} ?? '';
            $newValue = $newData[$field] ?? '';
            
            // Normalize empty values for comparison
            $existingValue = empty($existingValue) ? '' : (string)$existingValue;
            $newValue = empty($newValue) ? '' : (string)$newValue;
            
            if ($existingValue !== $newValue) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Format phone number to ensure proper format
     */
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

    /**
     * Normalize gender input to M/F format
     */
    private function normalizeGender($gender)
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['male', 'm'])) {
            return 'M';
        } elseif (in_array($gender, ['female', 'f'])) {
            return 'F';
        }
        
        return strtoupper(substr($gender, 0, 1)); // Default to first letter uppercase
    }
}