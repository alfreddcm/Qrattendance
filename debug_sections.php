<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\Section;
use App\Models\Student;

echo "=== Checking Sections Table ===\n";
$sections = Section::select('id', 'gradelevel', 'name')->limit(10)->get();
foreach($sections as $section) {
    echo "ID: {$section->id}, Grade: " . ($section->gradelevel ?? 'NULL') . ", Name: " . ($section->name ?? 'NULL') . "\n";
}

echo "\n=== Checking Students with Sections ===\n";
$students = Student::with('section')->whereNotNull('section_id')->limit(5)->get();
foreach($students as $student) {
    echo "Student: {$student->name}, Section: " . ($student->section ? 
        "Grade {$student->section->gradelevel} - {$student->section->name}" : 'NO SECTION') . "\n";
}

echo "\n=== Testing Query ===\n";
$query = Student::where('user_id', 2)
    ->join('sections', 'students.section_id', '=', 'sections.id')
    ->whereNotNull('sections.gradelevel')
    ->whereNotNull('sections.name')
    ->where('sections.gradelevel', '!=', '')
    ->where('sections.name', '!=', '')
    ->select('sections.gradelevel', 'sections.name as section_name')
    ->distinct()
    ->orderBy('sections.gradelevel')
    ->orderBy('sections.name')
    ->get();

foreach($query as $item) {
    echo "Grade: {$item->gradelevel}, Section: {$item->section_name}\n";
}
