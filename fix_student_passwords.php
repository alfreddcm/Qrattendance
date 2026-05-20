<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Hash;

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Get all students without passwords
$students = \App\Models\Student::whereNull('password')->get();

echo "Found " . count($students) . " students without passwords\n";

foreach ($students as $student) {
    if ($student->id_no) {
        $hashedPassword = Hash::make($student->id_no);
        $student->password = $hashedPassword;
        $student->password_changed = false;
        $student->save();
        echo "✓ Set password for: {$student->name} (LRN: {$student->id_no})\n";
    }
}

echo "\nDone! All students now have default passwords set.\n";
