<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$teachers = App\Models\User::where('role', 'teacher')
    ->select('id', 'name', 'school_id', 'is_active')
    ->orderBy('id')
    ->get();

if ($teachers->isEmpty()) {
    echo "No teachers found.\n";
    exit(0);
}

foreach ($teachers as $teacher) {
    $active = is_null($teacher->is_active) ? 'null' : ($teacher->is_active ? '1' : '0');
    echo $teacher->id . ' | ' . $teacher->name . ' | school_id=' . ($teacher->school_id ?? 'null') . ' | is_active=' . $active . "\n";
}
