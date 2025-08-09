<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Semester;
use Carbon\Carbon;

echo "Current time: " . Carbon::now('Asia/Manila')->format('Y-m-d H:i:s') . "\n";

$semester = Semester::where('status', 'active')->first();
if ($semester) {
    echo "Active Semester: " . $semester->name . "\n";
    echo "AM Time In: " . $semester->am_time_in_start . " - " . $semester->am_time_in_end . "\n";
    echo "AM Time Out: " . $semester->am_time_out_start . " - " . $semester->am_time_out_end . "\n";
    echo "PM Time In: " . $semester->pm_time_in_start . " - " . $semester->pm_time_in_end . "\n";
    echo "PM Time Out: " . $semester->pm_time_out_start . " - " . $semester->pm_time_out_end . "\n";
} else {
    echo "No active semester found\n";
}
