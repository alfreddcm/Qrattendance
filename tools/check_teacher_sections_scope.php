<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Section;
use App\Models\User;

$teachers = User::where('role', 'teacher')->get();

foreach ($teachers as $teacher) {
    $directCount = Section::where('teacher_id', $teacher->id)->count();
    $combinedCount = Section::where('teacher_id', $teacher->id)
        ->orWhereHas('teachers', function ($query) use ($teacher) {
            $query->where('users.id', $teacher->id);
        })
        ->count();

    echo $teacher->id . ' | ' . $teacher->name . ' | direct=' . $directCount . ' | combined=' . $combinedCount . "\n";
}
