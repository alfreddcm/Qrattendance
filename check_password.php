<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$student = \App\Models\Student::where('id_no', '500012200001')->first();

if ($student) {
    echo "Student Found: " . $student->name . PHP_EOL;
    echo "LRN: " . $student->id_no . PHP_EOL;
    echo "Password Hash: " . ($student->password ? substr($student->password, 0, 50) . "..." : "NULL") . PHP_EOL;
    echo "Password Changed: " . $student->password_changed . PHP_EOL;
    
    if ($student->password) {
        $match = \Illuminate\Support\Facades\Hash::check('500012200001', $student->password);
        echo "Hash Check (LRN): " . ($match ? "MATCH" : "NO MATCH") . PHP_EOL;
    }
} else {
    echo "Student not found" . PHP_EOL;
}
