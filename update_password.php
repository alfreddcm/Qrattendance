<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Directly update the student with LRN 500012200001
$lrn = '500012200001';
$hashedPassword = Hash::make($lrn);

$result = DB::table('students')
    ->where('id_no', $lrn)
    ->update([
        'password' => $hashedPassword,
        'password_changed' => false,
    ]);

// Write result to a file so we can see what happened
$output = "Update result: " . ($result ? "Success" : "No rows affected") . "\n";
$output .= "LRN: $lrn\n";
$output .= "Password hash: " . substr($hashedPassword, 0, 20) . "...\n";

// Also check what's in the database now
$student = DB::table('students')->where('id_no', $lrn)->first();
if ($student) {
    $output .= "Student found in DB: " . $student->name . "\n";
    $output .= "Password in DB: " . (is_null($student->password) ? "NULL" : substr($student->password, 0, 20) . "...") . "\n";
} else {
    $output .= "Student NOT found in database!\n";
}

file_put_contents('password_update_log.txt', $output);
echo $output;
