<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';

try {
    $chart = new App\Charts\AttendanceTrendsChart(['Test'], [10], [5]);
    echo "AttendanceTrendsChart created successfully!\n";
} catch (Exception $e) {
    echo "Error creating AttendanceTrendsChart: " . $e->getMessage() . "\n";
}

try {
    $chart = new App\Charts\AbsenteeismRatesChart(['Student 1'], [85]);
    echo "AbsenteeismRatesChart created successfully!\n";
} catch (Exception $e) {
    echo "Error creating AbsenteeismRatesChart: " . $e->getMessage() . "\n";
}

echo "Chart testing completed.\n";
