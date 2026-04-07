<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Models\SchoolYear;
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
        // Redirect to the manage students page where the import modal exists
        return redirect()->route('admin.manage-students');
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

            $normalizedData = $this->normalizeImportDataForPreview($data[0]);

             if (count($normalizedData) < 2) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'The file must contain at least a header row and one data row.');
            }
            
             $headerRow = $normalizedData[0];
            if (count($headerRow) < 7) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'Invalid file format. The file must contain LRN, name fields, gender, and age columns.');
            }
            
            $schoolYears = SchoolYear::all();
            if ($schoolYears->isEmpty()) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('error', 'No school years found in the system. Please create a school year first.');
            }

            $user = Auth::user();
            $teachers = [];
            $schools = [];
            $sections = collect();
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
                
                // Also get all sections for the teacher's school
                $sections = \App\Models\Section::where('teacher_id', $user->id)->get();
            }

            return view('import.preview', [
                'data' => $normalizedData,
                'file' => $path,
                'schoolYears' => $schoolYears,
                'semesters' => $schoolYears,
                'teachers' => $teachers,
                'schools' => $schools,
                'sections' => $sections,
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
                'school_year_id' => $request->input('school_year_id'),
                'section_id' => $request->input('section_id'),
                'selectedUserId' => $request->input('selectedUserId'),
                'selectedSectionId' => $request->input('selectedSectionId'),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            $students = $request->input('students');  
            $schoolYear_id = $request->input('school_year_id'); 
            $user_id = $request->input('user_id');
            
            \Log::info('Import data received', [
                'students_count' => is_array($students) ? count($students) : 0,
                'school_year_id' => $schoolYear_id,
                'user_id' => $user_id,
                'section_id_from_request' => $request->input('section_id'),
                'selectedSectionId' => $request->input('selectedSectionId'),
                'auth_user_role' => Auth::user()->role
            ]);
            
             $section_id = null;
            if (Auth::user()->role === 'admin') {
                 $section_id = $request->input('selectedSectionId');
                $user_id = $request->input('selectedUserId') ?? $user_id;
                \Log::info('Admin import - section assignment', [
                    'section_id' => $section_id,
                    'user_id' => $user_id,
                    'selectedUserId' => $request->input('selectedUserId')
                ]);
            } else {
                 $section_id = $request->input('section_id');
                \Log::info('Teacher import - section assignment', [
                    'section_id' => $section_id
                ]);
            }

            $school_id = User::where('id', $user_id)->value('school_id');

            if (!$students || !$schoolYear_id || !$user_id) {
                \Log::warning('Import failed: Missing students data or school year selection', [
                    'user_id' => $user_id,
                    'school_year_id' => $schoolYear_id,
                    'section_id' => $section_id
                ]);
                return redirect()->back()->with('error', 'Missing students data or school year selection. Please go back and try again.');
            }

            if (!$section_id) {
                \Log::warning('Import failed: No section selected', [
                    'user_id' => $user_id,
                    'school_year_id' => $schoolYear_id,
                    'request_section_id' => $request->input('section_id'),
                    'selectedSectionId' => $request->input('selectedSectionId'),
                    'all_request_data' => $request->except(['_token', 'students'])
                ]);
                $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
                return redirect()->route($route)->with('error', 'Please select a section for the students.');
            }

             $schoolYear = SchoolYear::find($schoolYear_id);
            if (!$schoolYear) {
                \Log::warning('Import failed: Selected school year does not exist', [
                    'school_year_id' => $schoolYear_id
                ]);
                return redirect()->back()->with('error', 'Selected school year does not exist. Please select a valid school year.');
            }

             if (!Auth::check()) {
                \Log::warning('Import failed: User not authenticated', [
                    'user_id' => $user_id
                ]);
                return redirect()->route('login')->with('error', 'You must be logged in to import students.');
            }

            // Check for conflicts if not confirmed
            if (!$request->has('confirm_conflicts')) {
                $conflicts = [];
                foreach ($students as $index => $row) {
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    $parsedRow = $this->parseImportStudentRow($row);
                    $idNo = $parsedRow['id_no'];
                    $name = $parsedRow['name'];
                    
                    if (empty($idNo) || empty($name)) {
                        continue;
                    }

                    $existingStudent = Student::where('id_no', $idNo)
                        ->where('school_year_id', $schoolYear_id)
                        ->where('user_id', $user_id)
                        ->first();

                    if ($existingStudent) {
                        $conflicts[] = [
                            'id_no' => $idNo,
                            'existing_name' => $existingStudent->name,
                            'new_name' => $name,
                            'section' => $existingStudent->section->name ?? 'N/A',
                            'grade_level' => $existingStudent->section->gradelevel ?? 'N/A'
                        ];
                    }
                }

                // If conflicts found, return to preview with conflict data
                if (!empty($conflicts)) {
                    \Log::warning('Import conflicts detected', [
                        'conflicts_count' => count($conflicts),
                        'user_id' => $user_id
                    ]);
                    return redirect()->back()
                        ->with('conflicts', $conflicts)
                        ->with('warning', count($conflicts) . ' student(s) already exist. Please review conflicts and confirm to update.')
                        ->withInput();
                }
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

                    $parsedRow = $this->parseImportStudentRow($row);

                     if (empty($parsedRow['id_no']) || empty($parsedRow['name']) || empty($parsedRow['gender']) || empty($parsedRow['age'])) {
                        $errors[] = "Row " . ($index + 1) . ": Missing required fields (LRN, Name, Gender, or Age)";
                        \Log::warning('Import row skipped: Missing required fields', [
                            'row' => $index + 1,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                     $idNo = $parsedRow['id_no'];
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $idNo)) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid ID format. Only letters and numbers are allowed.";
                        \Log::warning('Import row skipped: Invalid ID format', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                     $age = trim((string) $parsedRow['age']);
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
                        ->where('school_year_id', $schoolYear_id)
                        ->where('user_id', $user_id)
                        ->first();

                    $cpNo = $this->formatPhoneNumber($this->cleanTabPrefix($parsedRow['cp_no']));
                    $contactPersonContact = $this->formatPhoneNumber($this->cleanTabPrefix($parsedRow['contact_person_contact']));

                    $gender = $this->normalizeGender($parsedRow['gender']);
                    if (!in_array($gender, ['M', 'F'])) {
                        $errors[] = "Row " . ($index + 1) . ": Invalid gender format. Use M/F or Male/Female.";
                        \Log::warning('Import row skipped: Invalid gender format', [
                            'row' => $index + 1,
                            'gender' => $parsedRow['gender'],
                            'user_id' => $user_id
                        ]);
                        continue;
                    }

                    $name = trim($parsedRow['name']);
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
                        'address'                       => trim(substr($parsedRow['address'] ?? '', 0, 255)),
                        'cp_no'                         => $cpNo,
                        'contact_person_name'           => !empty($parsedRow['contact_person_name']) ? trim(substr($parsedRow['contact_person_name'], 0, 255)) : null,
                        'contact_person_relationship'   => !empty($parsedRow['contact_person_relationship']) ? trim(substr($parsedRow['contact_person_relationship'], 0, 255)) : null,
                        'contact_person_contact'        => $contactPersonContact,
                        'school_year_id'                   => $schoolYear_id,
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
                                'school_year_id' => $schoolYear_id
                            ]);
                        } else {
                            $skipped++;
                            \Log::info('Student skipped (no changes)', [
                                'id_no' => $idNo,
                                'user_id' => $user_id,
                                'school_year_id' => $schoolYear_id
                            ]);
                            continue;
                        }
                    } else {
                        Student::create($studentData);
                        $added++;
                        \Log::info('Student created during import', [
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'school_year_id' => $schoolYear_id
                        ]);
                    }
                    
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000) { 
                        $errors[] = "Row " . ($index + 1) . ": Duplicate entry or database constraint violation.";
                        \Log::error('Import row error: Duplicate entry or constraint violation', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'school_year_id' => $schoolYear_id,
                            'error' => $e->getMessage()
                        ]);
                    } else {
                        $errors[] = "Row " . ($index + 1) . ": Database error - " . $e->getMessage();
                        \Log::error('Import row error: Database error', [
                            'row' => $index + 1,
                            'id_no' => $idNo,
                            'user_id' => $user_id,
                            'school_year_id' => $schoolYear_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                    \Log::error('Import row error: General exception', [
                        'row' => $index + 1,
                        'id_no' => $idNo ?? null,
                        'user_id' => $user_id,
                        'school_year_id' => $schoolYear_id,
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
                    'school_year_id' => $schoolYear_id,
                    'added' => $added,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]);

                if ($added == 0) {
                    $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
                    return redirect()->route($route)->with('error', 'Import failed. ' . $errorMessage);
                } else {
                    $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
                    return redirect()->route($route)->with('warning', $message . $errorMessage);
                }
            }

            if ($added == 0 && $skipped == 0) {
                \Log::info('Import completed: No students imported', [
                    'user_id' => $user_id,
                    'school_year_id' => $schoolYear_id
                ]);
                $route = Auth::user()->role === 'admin' ? 'admin.manage-students' : 'teacher.students';
                return redirect()->route($route)->with('error', 'No students were imported. Please check your file format and data.');
            }

            \Log::info('Import completed successfully', [
                'user_id' => $user_id,
                'school_year_id' => $schoolYear_id,
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
                'school_year_id' => $request->input('school_year_id'),
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

    private function normalizeImportDataForPreview(array $rows): array
    {
        $normalized = [[
            'LRN',
            'Last Name',
            'First Name',
            'MI',
            'Name Extension',
            'Gender',
            'Age',
            'Address',
            'CP No',
            'Contact Person Name',
            'Contact Person Phone',
            'Relationship',
        ]];

        foreach (array_slice($rows, 1) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $parsed = $this->parseImportStudentRow($row);
            if (empty($parsed['id_no']) && empty($parsed['last_name']) && empty($parsed['first_name'])) {
                continue;
            }

            $normalized[] = [
                $parsed['id_no'],
                $parsed['last_name'],
                $parsed['first_name'],
                $parsed['middle_initial'],
                $parsed['name_extension'],
                $parsed['gender'],
                $parsed['age'],
                $parsed['address'],
                $parsed['cp_no'],
                $parsed['contact_person_name'],
                $parsed['contact_person_contact'],
                $parsed['contact_person_relationship'],
            ];
        }

        return $normalized;
    }

    private function parseImportStudentRow(array $row): array
    {
        $idNo = trim((string) ($row[0] ?? ''));

        $isNewTemplate = $this->isNewNameTemplateRow($row);
        if ($isNewTemplate) {
            $lastName = trim((string) ($row[1] ?? ''));
            $firstName = trim((string) ($row[2] ?? ''));
            $middleInitial = trim((string) ($row[3] ?? ''));
            $nameExtension = trim((string) ($row[4] ?? ''));

            return [
                'id_no' => $idNo,
                'last_name' => $this->formatNamePart($lastName),
                'first_name' => $this->formatNamePart($firstName),
                'middle_initial' => $this->normalizeMiddleInitial($middleInitial),
                'name_extension' => $this->normalizeNameExtension($nameExtension),
                'name' => $this->buildFormattedName($lastName, $firstName, $middleInitial, $nameExtension),
                'gender' => trim((string) ($row[5] ?? '')),
                'age' => trim((string) ($row[6] ?? '')),
                'address' => trim((string) ($row[7] ?? '')),
                'cp_no' => trim((string) ($row[8] ?? '')),
                'contact_person_name' => trim((string) ($row[9] ?? '')),
                'contact_person_contact' => trim((string) ($row[10] ?? '')),
                'contact_person_relationship' => trim((string) ($row[11] ?? '')),
            ];
        }

        $legacyName = trim((string) ($row[1] ?? ''));
        [$lastName, $firstName, $middleInitial, $nameExtension] = $this->splitLegacyName($legacyName);

        return [
            'id_no' => $idNo,
            'last_name' => $this->formatNamePart($lastName),
            'first_name' => $this->formatNamePart($firstName),
            'middle_initial' => $this->normalizeMiddleInitial($middleInitial),
            'name_extension' => $this->normalizeNameExtension($nameExtension),
            'name' => $this->buildFormattedName($lastName, $firstName, $middleInitial, $nameExtension),
            'gender' => trim((string) ($row[2] ?? '')),
            'age' => trim((string) ($row[3] ?? '')),
            'address' => trim((string) ($row[4] ?? '')),
            'cp_no' => trim((string) ($row[5] ?? '')),
            'contact_person_name' => trim((string) ($row[6] ?? '')),
            'contact_person_contact' => trim((string) ($row[7] ?? '')),
            'contact_person_relationship' => trim((string) ($row[8] ?? '')),
        ];
    }

    private function isNewNameTemplateRow(array $row): bool
    {
        $genderCandidate = strtolower(trim((string) ($row[5] ?? '')));
        $hasNameParts = !empty(trim((string) ($row[1] ?? ''))) || !empty(trim((string) ($row[2] ?? '')));

        return count($row) >= 12 && $hasNameParts && in_array($genderCandidate, ['m', 'f', 'male', 'female']);
    }

    private function splitLegacyName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['', '', '', ''];
        }

        if (str_contains($fullName, ',')) {
            $parts = array_map('trim', explode(',', $fullName, 2));
            $lastName = $parts[0] ?? '';
            $rest = $parts[1] ?? '';
            $tokens = preg_split('/\s+/', trim($rest)) ?: [];
            $firstName = array_shift($tokens) ?? '';

            $middleInitial = '';
            $nameExtension = '';
            if (!empty($tokens)) {
                $lastToken = strtoupper(rtrim(end($tokens), '.'));
                if (in_array($lastToken, ['JR', 'SR', 'II', 'III', 'IV', 'V'])) {
                    $nameExtension = array_pop($tokens);
                }
            }
            if (!empty($tokens)) {
                $middleInitial = array_pop($tokens);
            }

            return [$lastName, $firstName, $middleInitial, $nameExtension];
        }

        $tokens = preg_split('/\s+/', $fullName) ?: [];
        if (count($tokens) === 1) {
            return ['', $tokens[0], '', ''];
        }

        $firstName = array_shift($tokens);
        $lastName = implode(' ', $tokens);
        return [$lastName, $firstName, '', ''];
    }

    private function buildFormattedName(string $lastName, string $firstName, string $middleInitial = '', string $nameExtension = ''): string
    {
        $formattedLastName = $this->formatNamePart($lastName);
        $formattedFirstName = $this->formatNamePart($firstName);
        $formattedMiddleInitial = $this->normalizeMiddleInitial($middleInitial);
        $formattedNameExtension = $this->normalizeNameExtension($nameExtension);

        if ($formattedLastName !== '' && $formattedFirstName !== '') {
            $name = $formattedLastName . ', ' . $formattedFirstName;
        } elseif ($formattedLastName !== '') {
            $name = $formattedLastName;
        } else {
            $name = $formattedFirstName;
        }

        if ($formattedNameExtension !== '') {
            $name .= ' ' . $formattedNameExtension;
        }

        if ($formattedMiddleInitial !== '') {
            $name .= ' ' . $formattedMiddleInitial . '.';
        }

        return trim($name);
    }

    private function formatNamePart(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));
        if ($value === '') {
            return '';
        }

        return ucwords(strtolower($value));
    }

    private function normalizeMiddleInitial(string $value): string
    {
        $value = strtoupper(trim(str_replace('.', '', $value)));
        if ($value === '') {
            return '';
        }

        return substr($value, 0, 1);
    }

    private function normalizeNameExtension(string $value): string
    {
        $value = strtoupper(trim(str_replace('.', '', $value)));
        if ($value === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $value);
    }

    /**
     * Check for duplicate student IDs before importing (AJAX)
     */
    public function checkDuplicates(Request $request)
    {
        $idNumbers = $request->input('id_numbers', []);
        
        if (empty($idNumbers)) {
            return response()->json(['duplicates' => []]);
        }

        // Find existing students with these IDs
        $existing = Student::whereIn('id_no', $idNumbers)
            ->with('section')
            ->get()
            ->map(function ($student) {
                return [
                    'id_no' => $student->id_no,
                    'name' => $student->name,
                    'section' => $student->section->name ?? 'N/A',
                    'grade_level' => $student->section->gradelevel ?? 'N/A',
                ];
            });

        // Also check for duplicates within the uploaded list itself
        $counts = array_count_values(array_map('trim', $idNumbers));
        $inFileDuplicates = array_keys(array_filter($counts, fn($c) => $c > 1));

        return response()->json([
            'duplicates' => $existing,
            'in_file_duplicates' => $inFileDuplicates,
        ]);
    }
}
