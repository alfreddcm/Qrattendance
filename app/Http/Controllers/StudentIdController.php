<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\School;
use Mpdf\Mpdf;

class StudentIdController extends Controller
{
    
    private function getCurrentSemesterId()
    {
        $semesters = \App\Models\Semester::orderBy('start_date')->get();
        return $semesters->last()?->id;
    }

    public function downloadSingle($id)
    {
        $student = Student::with(['school', 'user'])->findOrFail($id);
        $currentUser = auth()->user();
        
        
        
        if ($currentUser->role === 'teacher' && $currentUser->id != $student->user_id) {
            abort(403, 'Unauthorized access. Teachers can only download IDs for their own students.');
        }
        
        $html = view('student-id.single', compact('student'))->render();
        
        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);
        
        $mpdf->WriteHTML($html);
        
        
        $sanitizedName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $student->name);
        $filename = $student->id_no . '_' . $sanitizedName . '.pdf';
        
        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // public function downloadAll(Request $request)
    // {
    //     if (auth()->user()->role !== 'admin') {
    //         abort(403, 'Unauthorized access. Only administrators can download all student IDs.');
    //     }

    //     $schoolId = $request->input('school_id');

    //     if (!$schoolId && auth()->check()) {
    //         $schoolId = auth()->user()->school_id;
    //     }

    //     $students = Student::with(['school', 'user']);
        
    //     if ($schoolId) {
    //         $students = $students->where('school_id', $schoolId);
    //     }
        
    //     $students = $students->orderBy('user_id')
    //         ->orderBy('id')
    //         ->get();

    //     $html = view('student-id.grid', compact('students'))->render();

    //     $mpdf = new Mpdf([
    //         'format' => 'A4',
    //         'orientation' => 'P',
    //         'margin_left' => 10,
    //         'margin_right' => 10,
    //         'margin_top' => 10,
    //         'margin_bottom' => 10,
    //     ]);

    //     $mpdf->WriteHTML($html);

    //     $schoolName = $students->first()->school->name ?? 'School';
    //     $sanitizedSchoolName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $schoolName);
    //     $filename = 'all_student_ids_' . $sanitizedSchoolName . '.pdf';

    //     return response($mpdf->Output($filename, 'S'))
    //         ->header('Content-Type', 'application/pdf')
    //         ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    // }

    // public function downloadByTeacher($teacherId = null)
    // {
    //     $currentUser = auth()->user();
        
    //     if (!$teacherId) {
    //         $teacherId = $currentUser->id;
    //     }
        
    //     if ($currentUser->role === 'teacher' && $currentUser->id != $teacherId) {
    //         abort(403, 'Unauthorized access. Teachers can only download their own students\' IDs.');
    //     }

    //     $selectedSemester = $this->getCurrentSemesterId();

    //     $students = Student::with(['school', 'user'])
    //         ->where('user_id', $teacherId)
    //         ->where('semester_id', $selectedSemester)
    //         ->get();
         
    //     \Log::info("Download by teacher {$teacherId}, semester {$selectedSemester}: Found {$students->count()} students");
        
    //     if ($students->count() === 0) {
    //         \Log::warning("Teacher {$teacherId} has no students assigned for semester {$selectedSemester}");
            
            
    //         $teacher = User::find($teacherId);
    //         $teacherName = $teacher ? $teacher->name : 'Teacher';
            
    //         return response()->view('errors.no-students', [
    //             'message' => "No students found for {$teacherName} in the current semester.",
    //             'suggestion' => 'Please add students first using the toolbar options such as "Add Student" or import students, then generate QR codes before downloading student IDs.',
    //             'teacherId' => $teacherId
    //         ], 404);
    //     }
        
    //     $html = view('student-id.grid', compact('students'))->render();
        
    //     $mpdf = new Mpdf([
    //         'format' => 'A4',
    //         'orientation' => 'P',
    //         'margin_left' => 10,
    //         'margin_right' => 10,
    //         'margin_top' => 10,
    //         'margin_bottom' => 10,
    //     ]);
        
    //     $mpdf->WriteHTML($html);
        
        
    //     $teacher = User::find($teacherId);
    //     $teacherName = $teacher ? $teacher->name : 'Teacher';
    //     $sanitizedTeacherName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $teacherName);
    //     $filename = 'teacher_' . $teacherId . '_' . $sanitizedTeacherName . '_student_ids.pdf';
        
    //     return response($mpdf->Output($filename, 'S'))
    //         ->header('Content-Type', 'application/pdf')
    //         ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    // }

    public function downloadMyStudents()
    {
        $currentUser = auth()->user();
        if ($currentUser->role !== 'teacher') {
            abort(403, 'This endpoint is only available for teachers.');
        }

        $selectedSemester = $this->getCurrentSemesterId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $currentUser->id)
            ->where('semester_id', $selectedSemester)
            ->get();
         
        \Log::info("Download my students for teacher {$currentUser->id}, semester {$selectedSemester}: Found {$students->count()} students");
         
        
        if ($students->count() === 0) {
            \Log::warning("Teacher {$currentUser->id} has no students assigned for semester {$selectedSemester}");
            
            return response()->view('errors.no-students', [
                'message' => "No students found for your account in the current semester.",
                'suggestion' => 'Please add students first using the toolbar options such as "Add Student" or import students, then generate QR codes before downloading student IDs.',
                'teacherId' => $currentUser->id,
                'backUrl' => route('teacher.students')
            ], 404);
        }
        
        
        $html = view('student-id.grid', compact('students'))->render();
        
        \Log::info("HTML length: " . strlen($html));
        \Log::info("HTML preview: " . substr($html, 0, 500));
         
        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        
        $mpdf->WriteHTML($html);
        
        
        $sanitizedTeacherName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $currentUser->name);
        $filename = 'my_students_' . $sanitizedTeacherName . '_ids.pdf';
        
        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // Print methods for web pages (Ctrl+P to print)
    
    public function printSingle($id)
    {
        $student = Student::with(['school', 'user'])->findOrFail($id);
        $currentUser = auth()->user();
        
        // Check authorization
        if ($currentUser->role === 'teacher' && $currentUser->id != $student->user_id) {
            abort(403, 'You can only print student IDs for your own students.');
        }
        
        return view('student-id.single', compact('student'));
    }

    public function printAll(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized access. Only administrators can print all student IDs.');
        }

        $schoolId = $request->input('school_id');

        if (!$schoolId && auth()->check()) {
            $schoolId = auth()->user()->school_id;
        }

        $students = Student::with(['school', 'user']);
        
        if ($schoolId) {
            $students = $students->where('school_id', $schoolId);
        }
        
        $students = $students->orderBy('user_id')
            ->orderBy('id')
            ->get();

        return view('student-id.grid', compact('students'));
    }

    public function printByTeacher($teacherId = null)
    {
        $currentUser = auth()->user();
        
        if (!$teacherId) {
            $teacherId = $currentUser->id;
        }
        
        if ($currentUser->role === 'teacher' && $currentUser->id != $teacherId) {
            abort(403, 'You can only print student IDs for your own students.');
        }

        $selectedSemester = $this->getCurrentSemesterId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $teacherId)
            ->where('semester_id', $selectedSemester)
            ->get();
         
        if ($students->count() === 0) {
            $teacher = User::find($teacherId);
            $teacherName = $teacher ? $teacher->name : 'Teacher';
            
            return response()->view('errors.no-students', [
                'message' => "No students found for {$teacherName} in the current semester.",
                'suggestion' => 'Please add students first using the toolbar options such as "Add Student" or import students, then generate QR codes before printing student IDs.',
                'teacherId' => $teacherId
            ], 404);
        }
        
        return view('student-id.grid', compact('students'));
    }

    public function printMyStudents()
    {
        $currentUser = auth()->user();
        if ($currentUser->role !== 'teacher') {
            abort(403, 'This endpoint is only available for teachers.');
        }

        $selectedSemester = $this->getCurrentSemesterId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $currentUser->id)
            ->where('semester_id', $selectedSemester)
            ->get();
         
        if ($students->count() === 0) {
            return response()->view('errors.no-students', [
                'message' => "No students found for your account in the current semester.",
                'suggestion' => 'Please add students first using the toolbar options such as "Add Student" or import students, then generate QR codes before printing student IDs.',
                'teacherId' => $currentUser->id,
                'backUrl' => route('teacher.students')
            ], 404);
        }
        
        return view('student-id.grid', compact('students'));
    }
}
