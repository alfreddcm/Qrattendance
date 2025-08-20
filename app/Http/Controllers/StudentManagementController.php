<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Maatwebsite\Excel\Facades\Excel;

class StudentManagementController extends Controller
{
     
    private function getCurrentSemesterId()
    {
        $semesters = Semester::orderBy('start_date')->get();
        return $semesters->last()?->id;
    }

    public function index(Request $request)
    {
        $query = Student::with('section')->where('user_id', Auth::id());
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_no', 'like', "%{$search}%")
                  ->orWhereHas('section', function($sectionQuery) use ($search) {
                      $sectionQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('gradelevel', 'like', "%{$search}%");
                  });
             
            });
        }
        
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        } else {
            $selectedSemester = $this->getCurrentSemesterId();
            if ($selectedSemester) {
                $query->where('semester_id', $selectedSemester);
            }
        }
        
        // Handle grade_section filter (format: "Grade 11|STEM")
        if ($request->filled('grade_section')) {
            $parts = explode('|', $request->grade_section);
            if (count($parts) == 2) {
                $gradeLevel = $parts[0];
                $sectionName = $parts[1];
                $query->whereHas('section', function($sectionQuery) use ($gradeLevel, $sectionName) {
                    $sectionQuery->where('gradelevel', $gradeLevel)
                                ->where('name', $sectionName);
                });
            }
        }
        
        if ($request->filled('qr_status')) {
            if ($request->qr_status == 'with_qr') {
                $query->whereNotNull('qr_code');
            } elseif ($request->qr_status == 'without_qr') {
                $query->whereNull('qr_code');
            }
        }
        
        // Handle sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'name') {
            $query->orderBy('name', $sortOrder);
        } elseif ($sortBy === 'gender') {
            $query->orderBy('gender', $sortOrder)->orderBy('name', 'asc');
        } elseif ($sortBy === 'age') {
            $query->orderBy('age', $sortOrder)->orderBy('name', 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $students = $query->with('section')->get();
        
        // Get grade-section options for the filter dropdown
        $gradeSectionOptions = Student::where('user_id', Auth::id())
            ->with('section')
            ->whereHas('section')
            ->get()
            ->map(function($student) {
                return [
                    'value' => $student->section->gradelevel . '|' . $student->section->name,
                    'label' => 'Grade ' . $student->section->gradelevel . ' - ' . $student->section->name
                ];
            })
            ->unique('value')
            ->values();
        
        // Get semesters for the dropdown
        $semesters = Semester::all();
        
        return view('teacher.students', compact('students', 'gradeSectionOptions', 'semesters'));
    }

    public function addStudent(Request $request)
    {
        Log::info('Student add request received', [
            'teacher_id' => Auth::id(),
            'student_id_no' => $request->id_no,
            'student_name' => $request->name,
            'semester_id' => $request->semester_id,
            'has_picture_file' => $request->hasFile('picture'),
            'has_captured_image' => !empty($request->captured_image),
        ]);

        try {
            $request->validate([
                'id_no' => 'required|string|max:255|unique:students,id_no',
                'name' => 'required|string|max:255',
                'section' => 'required|string|max:255',
                'grade_level' => 'required|string|max:255',
                'gender' => 'required|string|max:1',
                'age' => 'required|integer',
                'address' => 'required|string|max:255',
                'cp_no' => 'required|string|max:15',
                'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'captured_image' => 'nullable|string',
                'contact_person_name' => 'nullable|string|max:255',
                'contact_person_relationship' => 'nullable|string|max:255',
                'contact_person_contact' => 'nullable|string|max:15',
                'semester_id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            Log::warning('Student add validation failed', [
                'teacher_id' => Auth::id(),
                'student_id_no' => $request->id_no,
                'validation_errors' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Find or create the section
        $section = Section::firstOrCreate([
            'name' => $request->section,
            'gradelevel' => (int) filter_var($request->grade_level, FILTER_EXTRACT_NUMBER_INT),
            'teacher_id' => Auth::id(),
            'semester_id' => $request->semester_id
        ]);

        $studentData = $request->except(['grade_level', 'section']);
        $studentData['user_id'] = Auth::id();
        $studentData['section_id'] = $section->id; 

        try {
            if ($request->hasFile('picture')) {
                $picture = $request->file('picture');
                $pictureName = time() . '_' . $request->id_no . '.' . $picture->getClientOriginalExtension();
                $picture->storeAs('student_pictures', $pictureName, 'public');
                $studentData['picture'] = $pictureName;
                
                Log::info('Student picture uploaded', [
                    'teacher_id' => Auth::id(),
                    'student_id_no' => $request->id_no,
                    'picture_name' => $pictureName,
                ]);
            } elseif ($request->captured_image) {
                 $imageData = $request->captured_image;
                $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $imageData = base64_decode($imageData);
                
                $pictureName = time() . '_' . $request->id_no . '.jpg';
                Storage::disk('public')->put('student_pictures/' . $pictureName, $imageData);
                $studentData['picture'] = $pictureName;
                
                Log::info('Student captured image saved', [
                    'teacher_id' => Auth::id(),
                    'student_id_no' => $request->id_no,
                    'picture_name' => $pictureName,
                ]);
            }

            $student = Student::create($studentData);
            
            Log::info('Student created successfully', [
                'teacher_id' => Auth::id(),
                'student_id' => $student->id,
                'student_id_no' => $student->id_no,
                'student_name' => $student->name,
                'semester_id' => $student->semester_id,
            ]);

            return redirect()->route('teacher.students')->with('success', 'Student added successfully.');
            
        } catch (\Exception $e) {
            Log::error('Failed to create student', [
                'teacher_id' => Auth::id(),
                'student_id_no' => $request->id_no,
                'student_name' => $request->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to add student. Please try again.');
        }
    }

    public function edit($id)
    {
        $student = Student::with('section')->where('user_id', Auth::id())->findOrFail($id);
        
        // Get available sections from all students
        $availableSections = $this->getAvailableSections();
        
        // Get semesters for dropdown
        $semesters = Semester::all();
        
        return view('teacher.edit_student', compact('student', 'availableSections', 'semesters'));
    }

    /**
     * Get available sections from student data
     */
    public function getAvailableSections()
    {
        return Student::where('user_id', Auth::id())
            ->with('section')
            ->whereHas('section')
            ->get()
            ->groupBy(function($student) {
                return $student->section->gradelevel;
            })
            ->map(function($students, $gradeLevel) {
                return [
                    'grade_level' => $gradeLevel,
                    'sections' => $students->pluck('section.name')->unique()->sort()->values()
                ];
            })
            ->values();
    }

    /**
     * API endpoint to get sections for AJAX calls
     */
    public function getSections()
    {
        return response()->json($this->getAvailableSections());
    }

    /**
     * Update student with enhanced section management
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_no' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'section_id' => 'required|integer',
            'gender' => 'required|string|max:1',
            'age' => 'required|integer',
            'address' => 'required|string|max:255',
            'cp_no' => 'required|string|max:15',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'captured_image' => 'nullable|string',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:15',
            'semester_id' => 'required|integer',
        ]);

        $student = Student::where('user_id', Auth::id())->findOrFail($id);
        
        // Verify that the selected section exists and belongs to this teacher
        $section = Section::where('id', $request->section_id)
                         ->where('teacher_id', Auth::id())
                         ->first();
                         
        if (!$section) {
            return redirect()->back()->withErrors(['section_id' => 'The selected section is not valid or does not belong to you.'])->withInput();
        }

        $studentData = $request->except(['section', 'grade_level']);
        $studentData['user_id'] = Auth::id();
        $studentData['section_id'] = $request->section_id; 

         $this->clearStudentQrCode($student);
        $studentData['qr_code'] = null;
        if ($request->hasFile('picture')) {
             if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                Storage::disk('public')->delete('student_pictures/' . $student->picture);
            }
            
            $picture = $request->file('picture');
            $pictureName = time() . '_' . $request->id_no . '.' . $picture->getClientOriginalExtension();
            $picture->storeAs('student_pictures', $pictureName, 'public');
            $studentData['picture'] = $pictureName;
        } elseif ($request->captured_image) {
             if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                Storage::disk('public')->delete('student_pictures/' . $student->picture);
            }
            
             $imageData = $request->captured_image;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);
            
            $pictureName = time() . '_' . $request->id_no . '.jpg';
            Storage::disk('public')->put('student_pictures/' . $pictureName, $imageData);
            $studentData['picture'] = $pictureName;
        }

        $student->update($studentData);

        return redirect()->route('teacher.students')->with('success', 'Student updated successfully.');
    }

    /**
     * Quick update for inline editing
     */
    public function quickUpdate(Request $request, $id)
    {
        try {
            $student = Student::where('user_id', Auth::id())->findOrFail($id);
            
            // Validate the specific field being updated
            $rules = [];
            if ($request->has('name')) {
                $rules['name'] = 'required|string|max:255';
            }
            if ($request->has('section')) {
                $rules['section'] = 'required|string|max:255';
            }
            if ($request->has('grade_level')) {
                $rules['grade_level'] = 'required|string|max:255';
            }
            
            $request->validate($rules);
            
            // Handle section and grade_level changes
            if ($request->has('section') || $request->has('grade_level')) {
                $currentSection = $student->section;
                $newSectionName = $request->get('section', $currentSection->name);
                $newGradeLevel = $request->get('grade_level', $currentSection->gradelevel);
                
                // Find or create the section
                $section = Section::firstOrCreate([
                    'name' => $newSectionName,
                    'gradelevel' => (int) filter_var($newGradeLevel, FILTER_EXTRACT_NUMBER_INT),
                    'teacher_id' => Auth::id(),
                    'semester_id' => $student->semester_id
                ]);
                
                $student->section_id = $section->id;
                $this->clearStudentQrCode($student);
                $student->qr_code = null;
            }
            
            // Update other fields
            if ($request->has('name')) {
                $student->name = $request->name;
            }
            
            $student->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Student information updated successfully',
                'student' => $student->load('section')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Quick update failed', [
                'student_id' => $id,
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student information'
            ], 500);
        }
}

public function destroy($id)
{
    Log::info('Student delete request', [
        'student_id' => $id,
        'teacher_id' => Auth::id(),
    ]);

    try {
        $student = Student::where('user_id', Auth::id())->findOrFail($id);
        
        Log::info('Student found for deletion', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_id_no' => $student->id_no,
            'has_picture' => !empty($student->picture),
            'has_qr_code' => !empty($student->qr_code),
            'teacher_id' => Auth::id(),
        ]);
        
         if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
            Storage::disk('public')->delete('student_pictures/' . $student->picture);
            Log::info('Student picture deleted', [
                'student_id' => $student->id,
                'picture_name' => $student->picture,
            ]);
        }
        
         $this->clearStudentQrCode($student);
        
        $student->delete();
        
        Log::info('Student deleted successfully', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_id_no' => $student->id_no,
            'teacher_id' => Auth::id(),
        ]);

        return redirect()->route('teacher.students')->with('success', 'Student deleted successfully.');
        
    } catch (\Exception $e) {
        Log::error('Failed to delete student', [
            'student_id' => $id,
            'teacher_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->back()->with('error', 'Failed to delete student. Please try again.');
    }
}

public function bulkDelete(Request $request)
{
    $request->validate([
        'student_ids' => 'required|array',
        'student_ids.*' => 'exists:students,id'
    ]);

    $students = Student::where('user_id', Auth::id())
                      ->whereIn('id', $request->student_ids)
                      ->get();

    $deletedCount = 0;
    foreach ($students as $student) {
        if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
            Storage::disk('public')->delete('student_pictures/' . $student->picture);
        }
        
        $this->clearStudentQrCode($student);
        
        $student->delete();
        $deletedCount++;
    }

    return redirect()->route('teacher.students')->with('success', "{$deletedCount} student(s) deleted successfully.");
}

     public function generateQrs(Request $request)
    {
        Log::info('Bulk QR generation started', [
            'teacher_id' => Auth::id(),
            'has_student_ids' => $request->has('student_ids'),
            'student_ids_count' => $request->has('student_ids') ? count($request->student_ids) : 0,
            'timestamp' => now(),
        ]);

         if ($request->has('student_ids') && is_array($request->student_ids)) {
            $students = Student::where('user_id', Auth::id())
                              ->whereIn('id', $request->student_ids)
                              ->get();
        } else {
             $students = Student::where('user_id', Auth::id())->get();
        }
        
        $generated = 0;
        $failed = 0;

        foreach ($students as $student) {
            try {
                if ($this->generateQrForStudent($student)) {
                    $generated++;
                    Log::info('QR generated for student', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'student_id_no' => $student->id_no,
                        'teacher_id' => Auth::id(),
                    ]);
                } else {
                    Log::info('QR already exists for student', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'student_id_no' => $student->id_no,
                        'teacher_id' => Auth::id(),
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to generate QR for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_id_no' => $student->id_no,
                    'teacher_id' => Auth::id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Bulk QR generation completed', [
            'teacher_id' => Auth::id(),
            'total_students' => count($students),
            'generated' => $generated,
            'failed' => $failed,
            'timestamp' => now(),
        ]);

        if ($generated > 0) {
            $message = "$generated QR code(s) generated for students missing them.";
            if ($failed > 0) {
                $message .= " $failed QR code(s) failed to generate.";
            }
            return back()->with('success', $message);
        } else {
            if ($failed > 0) {
                return back()->with('error', "$failed QR code(s) failed to generate.");
            } else {
                return back()->with('info', 'All selected students already have QR codes.');
            }
        }
    }

     public function generateQr($id)
    {
        Log::info('Single QR generation started', [
            'student_id' => $id,
            'teacher_id' => Auth::id(),
            'timestamp' => now(),
        ]);

        try {
            $student = Student::where('user_id', Auth::id())->findOrFail($id);

            Log::info('Student found for QR generation', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_id_no' => $student->id_no,
                'has_existing_qr' => !empty($student->qr_code),
                'has_existing_stud_code' => !empty($student->stud_code),
                'teacher_id' => Auth::id(),
            ]);

            if ($this->generateQrForStudent($student)) {
                Log::info('QR generation successful', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'teacher_id' => Auth::id(),
                ]);
                return back()->with('success', 'QR code generated for student.');
            } else {
                Log::info('QR already exists', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'teacher_id' => Auth::id(),
                ]);
                return back()->with('info', 'Student already has a QR code.');
            }
        } catch (\Exception $e) {
            Log::error('Single QR generation failed', [
                'student_id' => $id,
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to generate QR code. Please try again.');
        }
    }

     private function generateQrForStudent(Student $student)
    {
        Log::info('Generating QR for student', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_id_no' => $student->id_no,
            'existing_qr_code' => $student->qr_code,
            'existing_stud_code' => $student->stud_code,
            'teacher_id' => Auth::id(),
        ]);

        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $qrCodeData = $student->id_no . '_' . $randomString;
        
        Log::info('Generated QR data', [
            'student_id' => $student->id,
            'qr_code_data' => $qrCodeData,
            'random_string' => $randomString,
            'id_no' => $student->id_no,
        ]);
        
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $qrPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';

        Log::info('QR file path prepared', [
            'student_id' => $student->id,
            'qr_path' => $qrPath,
            'sanitized_name' => $sanitizedName,
            'file_exists' => Storage::disk('public')->exists($qrPath),
        ]);

        if (!Storage::disk('public')->exists($qrPath) || !$student->qr_code) {
            try {
                $data = [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'semester_id' => $student->semester_id,
                    'qr_data' => $qrCodeData, // Add the new QR data format
                ];
                
                Log::info('Creating QR image', [
                    'student_id' => $student->id,
                    'qr_data' => $qrCodeData,
                ]);
                
                 $qrImage = QrCode::format('svg')
                    ->size(200)
                    ->errorCorrection('M')
                    ->generate($qrCodeData); // Use the new format instead of JSON
                
                Log::info('QR image generated, saving to storage', [
                    'student_id' => $student->id,
                    'qr_path' => $qrPath,
                    'qr_image_size' => strlen($qrImage),
                ]);
                
                Storage::disk('public')->put($qrPath, $qrImage);
                
                 // Save both qr_code (file path) and stud_code (the data)
                $student->update([
                    'qr_code' => $qrPath,
                    'stud_code' => $qrCodeData
                ]);
                
                Log::info('QR generation completed successfully', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'qr_path' => $qrPath,
                    'stud_code' => $qrCodeData,
                    'file_saved' => Storage::disk('public')->exists($qrPath),
                ]);
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to generate QR image or save to database', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'qr_path' => $qrPath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return false;
            }
        }
        
        Log::info('QR already exists for student', [
            'student_id' => $student->id,
            'existing_qr_path' => $student->qr_code,
            'file_exists' => Storage::disk('public')->exists($qrPath),
        ]);
        
        return false;
    }

    public function printQrs()
    {
        $students = Student::where('user_id', Auth::id())->get();
        return view('teacher.print_qrs', compact('students'));
    }

    public function downloadQrs()
    {
        $zip = new ZipArchive;
        $fileName = 'qr_codes.zip';
        $zipPath = storage_path('app/public/' . $fileName);

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $students = Student::where('user_id', Auth::id())->get();
            foreach ($students as $student) {
                 if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    $qrPath = storage_path('app/public/' . $student->qr_code);
                    $zip->addFile($qrPath, $student->id_no . '.svg');
                } else {
                     $qrPath = storage_path('app/public/qr_codes/' . $student->id_no . '.svg');
                    if (file_exists($qrPath)) {
                        $zip->addFile($qrPath, $student->id_no . '.svg');
                    }
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function export()
    {
        $students = Student::where('user_id', Auth::id())
            ->with('semester')
            ->orderBy('id_no')
            ->get();

        $filename = 'students_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            
             fputcsv($handle, [
                'ID No',
                'Name',
                'Section',
                'Grade Level',
                'Gender',
                'Age',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Relationship',
                'Contact Person Contact',
            ]);

             foreach ($students as $student) {
                 $cpNo = $student->cp_no ?? '';
                if ($cpNo) {
                     if (!str_starts_with($cpNo, '0')) {
                        $cpNo = '0' . $cpNo;
                    }
                     if (strlen($cpNo) < 11) {
                        $cpNo = str_pad($cpNo, 11, '0', STR_PAD_LEFT);
                    }
                }
                
                $contactPersonContact = $student->contact_person_contact ?? '';
                if ($contactPersonContact) {
                     if (!str_starts_with($contactPersonContact, '0')) {
                        $contactPersonContact = '0' . $contactPersonContact;
                    }
                     if (strlen($contactPersonContact) < 11) {
                        $contactPersonContact = str_pad($contactPersonContact, 11, '0', STR_PAD_LEFT);
                    }
                }
                
                fputcsv($handle, [
                    str_pad($student->id_no, 4, '0', STR_PAD_LEFT),
                    $student->name,
                    $student->section_name ?? '',
                    $student->grade_level ?? '',
                    $student->gender == 'M' ? 'Male' : ($student->gender == 'F' ? 'Female' : $student->gender),
                    $student->age,
                    $student->address,
                    "\t" . $cpNo,  
                    $student->contact_person_name ?? '',
                    $student->contact_person_relationship ?? '',
                    "\t" . $contactPersonContact, 
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ];

        return response()->stream(function() {
            $handle = fopen('php://output', 'w');
            
            $student = new Student();
            $fillableFields = $student->getFillable();
            
            $excludedFields = ['picture', 'user_id', 'school_id','semester_id'];
            $requiredFields = array_diff($fillableFields, $excludedFields);
            
            $headerMapping = [
                'id_no' => 'ID No',
                'name' => 'Student Name(LN, FN MI.)', 
                'section' => 'Section',
                'grade_level' => 'Grade Level',
                'gender' => 'Gender (M/F)',
                'age' => 'Age',
                'address' => 'Address',
                'cp_no' => 'Contact Phone',
                'contact_person_name' => 'Emergency Contact Name',
                'contact_person_relationship' => 'Relationship (Parent/Guardian/etc)',
                'contact_person_contact' => 'Emergency Contact Phone',
            ];
            
            $csvHeaders = [];
            foreach ($requiredFields as $field) {
                if (isset($headerMapping[$field])) {
                    $csvHeaders[] = $headerMapping[$field];
                }
            }
            
             fputcsv($handle, $csvHeaders);

            $exampleData = [
                '0001', 
                'Juan Dela Cruz', 
                'Section A',
                'Grade 11',
                'M',   
                '17', 
                'Barangay Example, San Guillermo, Isabela',  
                "\t09171234567",  
                'Maria Dela Cruz',  
                'Mother',  
                "\t09987654321",  
            ];
            
            $filteredExampleData = array_slice($exampleData, 0, count($csvHeaders));
            fputcsv($handle, $filteredExampleData);

             $exampleData2 = [
                '0002', 
                'Maria Santos', 
                'Section B',
                'Grade 12',
                'F', 
                '16', 
                'Zone 2, San Guillermo, Isabela',  
                "\t09281234567",  
                'Jose Santos',  
                'Father',  
                "\t09123456789",  
            ];
            
            $filteredExampleData2 = array_slice($exampleData2, 0, count($csvHeaders));
            fputcsv($handle, $filteredExampleData2);

             $exampleData3 = [
                '0003',  
                'Ana Rodriguez',  
                'Section A',
                'Grade 10',
                'F',  
                '18',  
                'Poblacion, San Guillermo, Isabela',  
                "\t09123456789", 
                'Carlos Rodriguez',  
                'Father',  
                "\t09876543210", 
            ];
            
            $filteredExampleData3 = array_slice($exampleData3, 0, count($csvHeaders));
            fputcsv($handle, $filteredExampleData3);

            fclose($handle);
        }, 200, $headers);
    }


    private function clearStudentQrCode(Student $student)
    {
        if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
            Storage::disk('public')->delete($student->qr_code);
        }

        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $legacyQrPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';
        if (Storage::disk('public')->exists($legacyQrPath)) {
            Storage::disk('public')->delete($legacyQrPath);
        }

        $simpleQrPath = 'qr_codes/' . $student->id_no . '.svg';
        if (Storage::disk('public')->exists($simpleQrPath)) {
            Storage::disk('public')->delete($simpleQrPath);
        }
    }

    /**
     * Get students as JSON for API calls
     */
    public function getStudentsForApi(Request $request)
    {
        $query = Student::where('user_id', Auth::id());
        
         $selectedSemester = $this->getCurrentSemesterId();
        if ($selectedSemester) {
            $query->where('semester_id', $selectedSemester);
        }
        
         $students = $query->with('section')
                         ->select('id', 'name', 'cp_no', 'contact_person_contact', 'contact_person_name', 'id_no', 'section_id')
                         ->orderBy('name')
                         ->get();
        
        return response()->json($students);
    }
}
