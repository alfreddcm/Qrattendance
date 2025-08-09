<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Semester;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class AdminController extends Controller
{
    /**
     * Display admin dashboard with overview statistics
     */
    public function dashboard()
    {
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalStudents = Student::count();
        $totalSchools = School::count();

         $schools = School::withCount(['users as teachers_count' => function($query) {
            $query->where('role', 'teacher');
        }])->withCount('students')->get();

        return view('admin.dashboard', compact(
            'totalTeachers',
            'totalStudents', 
            'totalSchools',
            'schools'
        ));
    }

    /**
     * Show manage schools page
     */
    public function manageSchools()
    {
        $schools = School::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.manage-schools', compact('schools'));
    }

    public function addSchoolForm()
    {
        return view('admin.add-school');
    }

    public function storeSchool(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'required|string|unique:schools,school_id|max:20'
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('school_logos', 'public');
        }

        School::create([
            'school_id' => $request->school_id,
            'name' => $request->name,
            'address' => $request->address,
            'logo' => $logoPath
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'School added successfully!');
    }

    /**
     * Show edit school form
     */
    public function editSchoolForm($id)
    {
        $school = School::findOrFail($id);
        return view('admin.edit-school', compact('school'));
    }

    /**
     * Update school
     */
    public function updateSchool(Request $request, $id)
    {
        $school = School::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_id' => 'required|string|max:20|unique:schools,school_id,' . $school->id
        ]);

        $logoPath = $school->logo;
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $logoPath = $request->file('logo')->store('school_logos', 'public');
        }

        $school->update([
            'school_id' => $request->school_id,
            'name' => $request->name,
            'address' => $request->address,
            'logo' => $logoPath
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'School updated successfully!');
    }

    /**
     * Delete school and cascade delete all related data
     */
    public function deleteSchool($id)
    {
        $school = School::findOrFail($id);
        
        // Get all teachers in this school
        $teachers = User::where('role', 'teacher')
                        ->where('school_id', $school->school_id)
                        ->get();
        
        foreach ($teachers as $teacher) {
            // Get students associated with this teacher
            $students = Student::where('user_id', $teacher->id)->get();
            
            // Delete students and their files
            foreach ($students as $student) {
                // Clear QR code files
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    Storage::disk('public')->delete($student->qr_code);
                }
                
                // Clear picture
                if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                    Storage::disk('public')->delete('student_pictures/' . $student->picture);
                }
                
                // Delete student record
                $student->delete();
            }
            
            // Delete teacher
            $teacher->delete();
        }
        
        // Delete all semesters for this school
        $semesters = Semester::where('school_id', $school->school_id)->get();
        foreach ($semesters as $semester) {
            $semester->delete();
        }
        
        // Delete school logo if exists
        if ($school->logo) {
            Storage::disk('public')->delete($school->logo);
        }

        // Delete school
        $school->delete();

        return redirect()->route('admin.dashboard')->with('success', 'School and all related data deleted successfully!');
    }

    /**
     * Show manage teachers page
     */
    public function manageTeachers()
    {
        $teachers = User::where('role', 'teacher')
                       ->with('school')
                       ->paginate(10);
        
        $schools = School::all();

        return view('admin.manage-teachers', compact('teachers', 'schools'));
    }

    /**
     * Store new teacher
     */
    public function storeTeacher(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'school_id' => 'required|exists:schools,id',
            'section_name' => 'nullable|string|max:100'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
            'phone_number' => $request->phone_number,
            'position' => $request->position,
            'school_id' => $request->school_id, // Now directly uses schools.id
            'section_name' => $request->section_name
        ]);

        return redirect()->route('admin.manage-teachers')->with('success', 'Teacher added successfully!');
    }

    /**
     * Update teacher
     */
    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'username' => 'required|string|unique:users,username,' . $teacher->id . '|max:255',
            'password' => 'nullable|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'school_id' => 'required|exists:schools,id',
            'section_name' => 'nullable|string|max:100'
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'position' => $request->position,
            'school_id' => $request->school_id, 
            'section_name' => $request->section_name
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $teacher->update($updateData);

        return redirect()->route('admin.manage-teachers')->with('success', 'Teacher updated successfully!');
    }

    /**
     * Delete teacher and cascade delete related students
     */
    public function deleteTeacher($id)
    {
        $teacher = User::findOrFail($id);
        
        // Get students associated with this teacher
        $students = Student::where('user_id', $teacher->id)->get();
        
        // Delete students and their files
        foreach ($students as $student) {
            // Clear QR code files
            if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                Storage::disk('public')->delete($student->qr_code);
            }
            
            // Clear picture
            if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                Storage::disk('public')->delete('student_pictures/' . $student->picture);
            }
            
            // Delete student record
            $student->delete();
        }
        
        // Delete teacher
        $teacher->delete();

        return redirect()->route('admin.manage-teachers')->with('success', 'Teacher and related students deleted successfully!');
    }

    /**
     * Show manage semesters page
     */
    public function manageSemesters()
    {
        $semesters = Semester::with('school')->orderBy('start_date', 'desc')->paginate(10);
        $schools = School::all();

        return view('admin.manage-semesters', compact('semesters', 'schools'));
    }

    /**
     * Store new semester
     */
    public function storeSemester(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_id' => 'required|exists:schools,id',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ]);

            // Build create data array - only include existing columns
            $createData = [
                'name' => $request->name,
                'status' => 'active', // Default status
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'school_id' => $request->school_id,
            ];

            // Check if time columns exist in the database before adding them
            $tableColumns = \Schema::getColumnListing('semesters');
            
            if (in_array('am_time_in_start', $tableColumns)) {
                $createData['am_time_in_start'] = $request->am_time_in_start;
            }
            if (in_array('am_time_in_end', $tableColumns)) {
                $createData['am_time_in_end'] = $request->am_time_in_end;
            }
            if (in_array('pm_time_out_start', $tableColumns)) {
                $createData['pm_time_out_start'] = $request->pm_time_out_start;
            }
            if (in_array('pm_time_out_end', $tableColumns)) {
                $createData['pm_time_out_end'] = $request->pm_time_out_end;
            }

            Semester::create($createData);

            return redirect()->route('admin.manage-semesters')->with('success', 'Semester created successfully!');
            
        } catch (\Exception $e) {
            return redirect()->route('admin.manage-semesters')->with('error', 'Failed to create semester. Please check your data and try again.');
        }
    }

    /**
     * Update semester
     */
    public function updateSemester(Request $request, $id)
    {
        try {
            $semester = Semester::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_id' => 'required|exists:schools,id',
                'am_time_in_start' => 'required|date_format:H:i',
                'am_time_in_end' => 'required|date_format:H:i',
                'pm_time_out_start' => 'required|date_format:H:i',
                'pm_time_out_end' => 'required|date_format:H:i',
            ]);

            // Build update data array - only include existing columns
            $updateData = [
                'name' => $request->name,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'school_id' => $request->school_id,
            ];

            // Check if time columns exist in the database before adding them
            $tableColumns = \Schema::getColumnListing('semesters');
            
            if (in_array('am_time_in_start', $tableColumns)) {
                $updateData['am_time_in_start'] = $request->am_time_in_start;
            }
            if (in_array('am_time_in_end', $tableColumns)) {
                $updateData['am_time_in_end'] = $request->am_time_in_end;
            }
            if (in_array('pm_time_out_start', $tableColumns)) {
                $updateData['pm_time_out_start'] = $request->pm_time_out_start;
            }
            if (in_array('pm_time_out_end', $tableColumns)) {
                $updateData['pm_time_out_end'] = $request->pm_time_out_end;
            }

            $semester->update($updateData);

            return redirect()->route('admin.manage-semesters')->with('success', 'Semester updated successfully!');
            
        } catch (\Exception $e) {
            return redirect()->route('admin.manage-semesters')->with('error', 'Failed to update semester. Please check your data and try again.');
        }
    }

    /**
     * Delete semester and cascade delete related teachers and students
     */
    public function deleteSemester($id)
    {
        $semester = Semester::findOrFail($id);
        
        // Get teachers associated with this semester (by school)
        $teachers = User::where('role', 'teacher')
                        ->where('school_id', $semester->school_id)
                        ->get();
        
        foreach ($teachers as $teacher) {
            // Get students associated with this teacher
            $students = Student::where('user_id', $teacher->id)->get();
            
            // Delete students and their files
            foreach ($students as $student) {
                // Clear QR code files
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    Storage::disk('public')->delete($student->qr_code);
                }
                
                // Clear picture
                if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                    Storage::disk('public')->delete('student_pictures/' . $student->picture);
                }
                
                // Delete student record
                $student->delete();
            }
            
            // Delete teacher
            $teacher->delete();
        }
        
        // Delete semester
        $semester->delete();

        return redirect()->route('admin.manage-semesters')->with('success', 'Semester and related teachers/students deleted successfully!');
    }

    /**
     * Show manage students page
     */
    public function manageStudents(Request $request)
    {
        $query = Student::with(['user', 'school']);
        
        // Handle search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id_no', 'like', "%{$search}%");
            });
        }
        
        // Handle school filter
        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        
        // Handle teacher filter
        if ($request->filled('teacher_id')) {
            $query->where('user_id', $request->teacher_id);
        }
        
        // Handle QR status filter
        if ($request->filled('qr_status')) {
            if ($request->qr_status == 'with_qr') {
                $query->whereNotNull('qr_code');
            } elseif ($request->qr_status == 'without_qr') {
                $query->whereNull('qr_code');
            }
        }
        
        $students = $query->paginate(15);
        
        $schools = School::all();
        $teachers = User::where('role', 'teacher')->get();

        return view('admin.manage-students', compact('students', 'schools', 'teachers'));
    }

    /**
     * Show attendance reports page
     */
    public function attendanceReports(Request $request)
    {
        $schools = School::all();
        $teachers = User::where('role', 'teacher')->get();
        
        $attendanceData = collect();
        
        if ($request->filled(['start_date', 'end_date'])) {
            $query = Attendance::with(['student', 'student.user', 'student.school'])
                              ->whereBetween('date', [$request->start_date, $request->end_date]);
            
            if ($request->filled('school_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('school_id', $request->school_id);
                });
            }
            
            if ($request->filled('teacher_id')) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('user_id', $request->teacher_id);
                });
            }
            
            $attendanceData = $query->orderBy('date', 'desc')->paginate(20);
        }

        return view('admin.attendance-reports', compact('schools', 'teachers', 'attendanceData'));
    }

    /**
     * Store a new student
     */
    public function storeStudent(Request $request)
    {
        $request->validate([
            'id_no' => 'required|string|max:255|unique:students,id_no',
            'name' => 'required|string|max:255',
            'gender' => 'required|string|max:1',
            'age' => 'required|integer',
            'address' => 'nullable|string|max:255',
            'cp_no' => 'nullable|string|max:15',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:15',
            'school_id' => 'nullable|exists:schools,school_id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        Student::create($request->all());

        return redirect()->route('admin.manage-students')->with('success', 'Student added successfully.');
    }

    /**
     * Update a student
     */
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'id_no' => 'required|string|max:255|unique:students,id_no,' . $id,
            'name' => 'required|string|max:255',
            'gender' => 'required|string|max:1',
            'age' => 'required|integer',
            'address' => 'nullable|string|max:255',
            'cp_no' => 'nullable|string|max:15',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_relationship' => 'nullable|string|max:255',
            'contact_person_contact' => 'nullable|string|max:15',
            'school_id' => 'nullable|exists:schools,school_id',
        ]);

        $student->update($request->all());

        return redirect()->route('admin.manage-students')->with('success', 'Student updated successfully.');
    }

    /**
     * Delete a student
     */
    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        
        // Clear QR code files
        if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
            Storage::disk('public')->delete($student->qr_code);
        }
        
        // Clear picture
        if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
            Storage::disk('public')->delete('student_pictures/' . $student->picture);
        }

        $student->delete();

        return redirect()->route('admin.manage-students')->with('success', 'Student deleted successfully.');
    }

    /**
     * Bulk delete students
     */
    public function bulkDeleteStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        $students = Student::whereIn('id', $request->student_ids)->get();

        $deletedCount = 0;
        foreach ($students as $student) {
            // Clear QR code files
            if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                Storage::disk('public')->delete($student->qr_code);
            }
            
            // Clear picture
            if ($student->picture && Storage::disk('public')->exists('student_pictures/' . $student->picture)) {
                Storage::disk('public')->delete('student_pictures/' . $student->picture);
            }

            $student->delete();
            $deletedCount++;
        }

        return redirect()->route('admin.manage-students')->with('success', "{$deletedCount} student(s) deleted successfully.");
    }

    /**
     * Generate QR codes for students
     */
    public function generateQrs(Request $request)
    {
        // If specific student IDs are provided, generate QR codes for those students only
        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $students = Student::whereIn('id', $request->student_ids)->get();
        } else {
            // Generate for all students
            $students = Student::all();
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

    /**
     * Generate QR code for individual student
     */
    public function generateQr($id)
    {
        $student = Student::findOrFail($id);

        if ($this->generateQrForStudent($student)) {
            return back()->with('success', 'QR code generated for student.');
        } else {
            return back()->with('info', 'Student already has a QR code.');
        }
    }

    /**
     * Generate QR code for a student
     */
    private function generateQrForStudent(Student $student)
    {
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $qrPath = 'qr_codes/' . $student->id_no . '_' . $sanitizedName . '.svg';

        if (!Storage::disk('public')->exists($qrPath) || !$student->qr_code) {
            $data = [
                'student_id' => $student->id,
                'name' => $student->name,
                'school_id' => $student->school_id,
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

    /**
     * Print QR codes
     */
    public function printQrs()
    {
        $students = Student::whereNotNull('qr_code')->get();
        return view('admin.print-qrs', compact('students'));
    }

    /**
     * Download QR codes as ZIP
     */
    public function downloadQrs()
    {
        $students = Student::whereNotNull('qr_code')->get();
        
        if ($students->isEmpty()) {
            return back()->with('error', 'No QR codes found to download.');
        }

        $zipFileName = 'student_qr_codes_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive();
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($students as $student) {
                if ($student->qr_code && Storage::disk('public')->exists($student->qr_code)) {
                    $qrFilePath = Storage::disk('public')->path($student->qr_code);
                    $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
                    $fileName = $student->id_no . '_' . $sanitizedName . '.svg';
                    $zip->addFile($qrFilePath, $fileName);
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Failed to create QR codes archive.');
    }

    /**
     * Export students data
     */
    public function exportStudents()
    {
        $students = Student::with(['user', 'school'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_export_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID No',
                'Name',
                'Gender',
                'Age',
                'School',
                'Teacher',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Relationship',
                'Contact Person Contact',
                'QR Code Status'
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->id_no ?? '',
                    $student->name,
                    $student->gender == 'M' ? 'Male' : ($student->gender == 'F' ? 'Female' : $student->gender),
                    $student->age,
                    $student->school->name ?? 'N/A',
                    $student->user->name ?? 'N/A',
                    $student->address,
                    $student->cp_no ?? '',
                    $student->contact_person_name ?? '',
                    $student->contact_person_relationship ?? '',
                    $student->contact_person_contact ?? '',
                    $student->qr_code ? 'Available' : 'Missing'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download Excel template for student import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            
            // Add template headers with instructions
            fputcsv($handle, [
                'id_no',
                'name',
                'gender',
                'age',
                'school_id',
                'teacher_id',
                'address',
                'cp_no',
                'contact_person_name',
                'contact_person_relationship',
                'contact_person_contact'
            ]);
            
            // Add example data row
            fputcsv($handle, [
                '2024001',
                'John Doe',
                'M',
                '20',
                '1',
                '1',
                '123 Main St, City',
                '09123456789',
                'Jane Doe',
                'Mother',
                '09987654321'
            ]);
            
            // Add instruction rows (they'll be ignored during import)
            fputcsv($handle, []);
            fputcsv($handle, ['INSTRUCTIONS:']);
            fputcsv($handle, ['- Fill in all required fields']);
            fputcsv($handle, ['- Gender: Use M for Male, F for Female']);
            fputcsv($handle, ['- Age: Enter numeric value']);
            fputcsv($handle, ['- School ID: Get from admin panel']);
            fputcsv($handle, ['- Teacher ID: Get from admin panel (optional)']);
            fputcsv($handle, ['- Delete this instruction section before importing']);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download sample data for reference
     */
    public function downloadSampleData()
    {
        $schools = School::with('users')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_sample_data.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () use ($schools) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'id_no',
                'name',
                'gender',
                'age',
                'school_id',
                'teacher_id',
                'address',
                'cp_no',
                'contact_person_name',
                'contact_person_relationship',
                'contact_person_contact'
            ]);
            
            // Sample data
            $sampleData = [
                ['2024001', 'Alice Johnson', 'F', '19', '1', '1', '456 Oak Ave, Springfield', '09111111111', 'Robert Johnson', 'Father', '09222222222'],
                ['2024002', 'Bob Smith', 'M', '20', '1', '1', '789 Pine St, Springfield', '09333333333', 'Mary Smith', 'Mother', '09444444444'],
                ['2024003', 'Carol Davis', 'F', '18', '1', '', '321 Elm St, Springfield', '09555555555', 'David Davis', 'Father', '09666666666'],
            ];

            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            
            fputcsv($handle, []);
            fputcsv($handle, ['AVAILABLE SCHOOLS:']);
            foreach ($schools as $school) {
                fputcsv($handle, ["School ID: {$school->id}", "Name: {$school->name}"]);
            }
            
            fputcsv($handle, []);
            fputcsv($handle, ['AVAILABLE TEACHERS:']);
            foreach ($schools as $school) {
                foreach ($school->users->where('role', 'teacher') as $teacher) {
                    fputcsv($handle, ["Teacher ID: {$teacher->id}", "Name: {$teacher->name}", "School: {$school->name}"]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import students from uploaded file
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120', // 5MB max
            'assign_teacher_id' => 'nullable|exists:users,id',
            'preview' => 'nullable|boolean'
        ]);

        try {
            $file = $request->file('file');
            $path = $file->storeAs('imports', 'students_' . time() . '.' . $file->getClientOriginalExtension());
            
            // Use Laravel Excel or similar package for processing
            // For now, let's handle CSV files
            if ($file->getClientOriginalExtension() === 'csv') {
                return $this->processCsvImport($path, $request);
            }
            
            return redirect()->back()->with('error', 'Excel files require additional processing. Please use CSV format for now.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Process CSV import
     */
    private function processCsvImport($path, Request $request)
    {
        $preview = $request->boolean('preview', true);
        $assignTeacherId = $request->input('assign_teacher_id');
        
        $fullPath = storage_path('app/' . $path);
        $data = [];
        $errors = [];
        $successCount = 0;
        
        if (($handle = fopen($fullPath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            $rowNumber = 1;
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                $rowNumber++;
                
                // Skip empty rows or instruction rows
                if (empty(array_filter($row)) || strpos($row[0], 'INSTRUCTIONS') !== false) {
                    continue;
                }
                
                try {
                    $studentData = [
                        'id_no' => $row[0] ?? '',
                        'name' => $row[1] ?? '',
                        'gender' => $row[2] ?? '',
                        'age' => $row[3] ?? '',
                        'school_id' => $row[4] ?? '',
                        'user_id' => $assignTeacherId ?: ($row[5] ?? null),
                        'address' => $row[6] ?? '',
                        'cp_no' => $row[7] ?? '',
                        'contact_person_name' => $row[8] ?? '',
                        'contact_person_relationship' => $row[9] ?? '',
                        'contact_person_contact' => $row[10] ?? '',
                    ];
                    
                    // Validate required fields
                    if (empty($studentData['name']) || empty($studentData['school_id'])) {
                        $errors[] = "Row {$rowNumber}: Name and School ID are required";
                        continue;
                    }
                    
                    // Validate school exists
                    if (!School::find($studentData['school_id'])) {
                        $errors[] = "Row {$rowNumber}: Invalid School ID";
                        continue;
                    }
                    
                    // Validate teacher if provided
                    if ($studentData['user_id'] && !User::where('role', 'teacher')->find($studentData['user_id'])) {
                        $errors[] = "Row {$rowNumber}: Invalid Teacher ID";
                        continue;
                    }
                    
                    if ($preview) {
                        $data[] = $studentData;
                    } else {
                        // Create student
                        Student::create($studentData);
                        $successCount++;
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
            
            fclose($handle);
        }
        
        // Clean up uploaded file
        Storage::delete($path);
        
        if ($preview) {
            return view('admin.import-preview', [
                'data' => $data,
                'errors' => $errors,
                'totalRows' => count($data)
            ]);
        } else {
            $message = "Import completed. {$successCount} students imported successfully.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors encountered.";
            }
            
            return redirect()->route('admin.manage-students')->with('success', $message);
        }
    }

    /**
     * Show import guide
     */
    public function importGuide()
    {
        $schools = School::with('users')->get();
        return view('admin.import-guide', compact('schools'));
    }

    /**
     * Bulk export selected students
     */
    public function bulkExportStudents(Request $request)
    {
        $studentIds = $request->input('student_ids', []);
        
        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'No students selected for export.');
        }
        
        $students = Student::with(['school', 'user'])
            ->whereIn('id', $studentIds)
            ->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="selected_students_' . date('Y-m-d') . '.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID No',
                'Name',
                'Gender',
                'Age',
                'School',
                'Teacher',
                'Address',
                'CP No',
                'Contact Person Name',
                'Contact Person Relationship',
                'Contact Person Contact',
                'QR Code Status'
            ]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->id_no ?? '',
                    $student->name,
                    $student->gender == 'M' ? 'Male' : ($student->gender == 'F' ? 'Female' : $student->gender),
                    $student->age,
                    $student->school->name ?? 'N/A',
                    $student->user->name ?? 'N/A',
                    $student->address,
                    $student->cp_no ?? '',
                    $student->contact_person_name ?? '',
                    $student->contact_person_relationship ?? '',
                    $student->contact_person_contact ?? '',
                    $student->qr_code ? 'Available' : 'Missing'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
