<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use ZipArchive;
use Maatwebsite\Excel\Facades\Excel;

class StudentManagementController extends Controller
{
     
    private function getCurrentSemesterId()
    {
        $semesters = \App\Models\Semester::orderBy('start_date')->get();
        return $semesters->last()?->id;
    }

    public function index(Request $request)
    {
        $query = Student::where('user_id', Auth::id());
        
        // Handle search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_no', 'like', "%{$search}%");
            });
        }
        
        // Handle semester filter
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        } else {
            // Default to current semester if no filter
            $selectedSemester = $this->getCurrentSemesterId();
            if ($selectedSemester) {
                $query->where('semester_id', $selectedSemester);
            }
        }
        
        // Handle QR status filter
        if ($request->filled('qr_status')) {
            if ($request->qr_status == 'with_qr') {
                $query->whereNotNull('qr_code');
            } elseif ($request->qr_status == 'without_qr') {
                $query->whereNull('qr_code');
            }
        }
        
        $students = $query->orderBy('id_no')->get();
        
        return view('teacher.students', compact('students'));
    }

    public function addStudent(Request $request)
    {
        $request->validate([
            'id_no' => 'required|string|max:255|unique:students,id_no',
            'name' => 'required|string|max:255',
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

        $studentData = $request->all();
        $studentData['user_id'] = Auth::id(); 


        if ($request->hasFile('picture')) {
            $picture = $request->file('picture');
            $pictureName = time() . '_' . $request->id_no . '.' . $picture->getClientOriginalExtension();
            $picture->storeAs('student_pictures', $pictureName, 'public');
            $studentData['picture'] = $pictureName;
        } elseif ($request->captured_image) {
 
            $imageData = $request->captured_image;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);
            
            $pictureName = time() . '_' . $request->id_no . '.jpg';
            Storage::disk('public')->put('student_pictures/' . $pictureName, $imageData);
            $studentData['picture'] = $pictureName;
        }

        Student::create($studentData);

        return redirect()->route('teacher.students')->with('success', 'Student added successfully.');
    }

    public function edit($id)
    {
        $student = Student::where('user_id', Auth::id())->findOrFail($id);
        return view('teacher.edit_student', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_no' => 'required|string|max:255',
            'name' => 'required|string|max:255',
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
        $studentData = $request->all();
        $studentData['user_id'] = Auth::id(); 

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

public function destroy($id)
{
    $student = Student::where('user_id', Auth::id())->findOrFail($id);
    
     if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
        Storage::disk('public')->delete('student_pictures/' . $student->picture);
    }
    
     $this->clearStudentQrCode($student);
    
    $student->delete();

    return redirect()->route('teacher.students')->with('success', 'Student deleted successfully.');
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
        // If specific student IDs are provided, generate QR codes for those students only
        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $students = Student::where('user_id', Auth::id())
                              ->whereIn('id', $request->student_ids)
                              ->get();
        } else {
            // Generate for all students
            $students = Student::where('user_id', Auth::id())->get();
        }
        
        $generated = 0;

        foreach ($students as $student) {
            if ($this->generateQrForStudent($student)) {
                $generated++;
            }
        }

        if ($generated > 0) {
            return back()->with('success', "$generated QR code(s) generated for students missing them.");
        } else {
            return back()->with('info', 'All selected students already have QR codes.');
        }
    }

     public function generateQr($id)
    {
        $student = Student::where('user_id', Auth::id())->findOrFail($id);

        if ($this->generateQrForStudent($student)) {
            return back()->with('success', 'QR code generated for student.');
        } else {
            return back()->with('info', 'Student already has a QR code.');
        }
    }

     private function generateQrForStudent(Student $student)
    {
         $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $qrPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';

        if (!Storage::disk('public')->exists($qrPath) || !$student->qr_code) {
            $data = [
                'student_id' => $student->id,
                'name' => $student->name,
                'semester_id' => $student->semester_id,
            ];
            
             $qrImage = QrCode::format('svg')
                ->size(200)
                ->errorCorrection('M')
                ->generate(json_encode($data));
            
            Storage::disk('public')->put($qrPath, $qrImage);
            
             $student->update(['qr_code' => $qrPath]);
            
            return true;
        }
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
                'name' => 'Student Name', 
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
}
