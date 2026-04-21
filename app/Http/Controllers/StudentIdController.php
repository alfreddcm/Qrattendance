<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\School;
use Mpdf\Mpdf;

class StudentIdController extends Controller
{
    
    private function getCurrentSchoolYearId()
    {
        $semesters = \App\Models\SchoolYear::orderBy('start_date')->get();
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

    //     $selectedSchoolYear = $this->getCurrentSchoolYearId();

    //     $students = Student::with(['school', 'user'])
    //         ->where('user_id', $teacherId)
    //         ->where('school_year_id', $selectedSchoolYear)
    //         ->get();
         
    //     \Log::info("Download by teacher {$teacherId}, semester {$selectedSchoolYear}: Found {$students->count()} students");
        
    //     if ($students->count() === 0) {
    //         \Log::warning("Teacher {$teacherId} has no students assigned for semester {$selectedSchoolYear}");
            
            
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

        $selectedSchoolYear = $this->getCurrentSchoolYearId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $currentUser->id)
            ->where('school_year_id', $selectedSchoolYear)
            ->get();
         
        \Log::info("Download my students for teacher {$currentUser->id}, semester {$selectedSchoolYear}: Found {$students->count()} students");
         
        
        if ($students->count() === 0) {
            \Log::warning("Teacher {$currentUser->id} has no students assigned for semester {$selectedSchoolYear}");
            
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
    
    public function printSingle(Student $student)
    {
        $this->authorize('view', $student);

        $student->load(['school', 'user']);
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

        // Get filter parameters
        $schoolId = $request->input('school_id');
        $schoolYearId = $request->input('school_year_id');
        $teacherId = $request->input('teacher_id');
        $sectionId = $request->input('section_id');

        // Build the query
        $query = Student::with(['school', 'schoolYear', 'section.teacher', 'section.teachers']);
        
        // Apply filters
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($schoolYearId) {
            $query->where('school_year_id', $schoolYearId);
        }
        
        if ($teacherId) {
            $query->whereHas('section', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                  ->orWhereHas('teachers', function($subQ) use ($teacherId) {
                      $subQ->where('users.id', $teacherId);
                  });
            });
        }
        
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }
        
        // Get students and organize them
        $students = $query->orderBy('school_id')
            ->orderBy('school_year_id')
            ->orderBy('section_id')
            ->orderBy('name')
            ->get();

        // Organize students by School → School Year → Teacher
        $organized = [];
        
        foreach ($students as $student) {
            $schoolKey = $student->school_id ?? 'no_school';
            $schoolName = $student->school->name ?? 'No School';
            
            $schoolYearKey = $student->school_year_id ?? 'no_year';
            $schoolYearName = 'No School Year';
            if ($student->schoolYear) {
                if ($student->schoolYear->school_year_start && $student->schoolYear->school_year_end) {
                    $schoolYearName = $student->schoolYear->school_year_start . '–' . $student->schoolYear->school_year_end;
                } else {
                    $schoolYearName = $student->schoolYear->name ?? 'No School Year';
                }
            }
            
            $teacherKey = 'no_teacher';
            $teacherName = 'No Teacher';
            if ($student->section) {
                if ($student->section->teacher) {
                    $teacherKey = $student->section->teacher->id;
                    $teacherName = $student->section->teacher->name;
                } elseif ($student->section->teachers->count() > 0) {
                    $firstTeacher = $student->section->teachers->first();
                    $teacherKey = $firstTeacher->id;
                    $teacherName = $firstTeacher->name;
                }
            }
            
            if (!isset($organized[$schoolKey])) {
                $organized[$schoolKey] = [
                    'name' => $schoolName,
                    'school_years' => []
                ];
            }
            
            if (!isset($organized[$schoolKey]['school_years'][$schoolYearKey])) {
                $organized[$schoolKey]['school_years'][$schoolYearKey] = [
                    'name' => $schoolYearName,
                    'teachers' => []
                ];
            }
            
            if (!isset($organized[$schoolKey]['school_years'][$schoolYearKey]['teachers'][$teacherKey])) {
                $organized[$schoolKey]['school_years'][$schoolYearKey]['teachers'][$teacherKey] = [
                    'name' => $teacherName,
                    'students' => []
                ];
            }
            
            $organized[$schoolKey]['school_years'][$schoolYearKey]['teachers'][$teacherKey]['students'][] = $student;
        }

        return view('student-id.grid-organized', compact('organized', 'students'));
    }

    public function printByTeacher(User $teacher)
    {
        $this->authorize('view', $teacher);

        $currentUser = auth()->user();

        if ($currentUser->role === 'teacher' && $currentUser->id != $teacher->id) {
            abort(403, 'You can only print student IDs for your own students.');
        }

        $selectedSchoolYear = $this->getCurrentSchoolYearId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $teacher->id)
            ->where('school_year_id', $selectedSchoolYear)
            ->get();
         
        if ($students->count() === 0) {
            $teacherName = $teacher->name ?: 'Teacher';
            
            return response()->view('errors.no-students', [
                'message' => "No students found for {$teacherName} in the current semester.",
                'suggestion' => 'Please add students first using the toolbar options such as "Add Student" or import students, then generate QR codes before printing student IDs.',
                'teacherId' => $teacher->id
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

        $selectedSchoolYear = $this->getCurrentSchoolYearId();

        $students = Student::with(['school', 'user'])
            ->where('user_id', $currentUser->id)
            ->where('school_year_id', $selectedSchoolYear)
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
