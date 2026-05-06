<?php

// Simulate SectionController::update with empty teacher_id to reproduce JSON response
// Run: php tools/simulate_section_update.php

require __DIR__ . '/..\vendor\autoload.php';

$app = require_once __DIR__ . '/..\bootstrap\app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\SectionController;
use App\Models\Section;

$section = Section::first();
if (!$section) {
    echo "No section found in database. Create a section first.\n";
    exit(1);
}

$controller = new SectionController();

// Build request data using existing section values but clear teacher_id
$data = [
    'name' => $section->name,
    'gradelevel' => $section->gradelevel,
    'school_year_id' => $section->school_year_id,
    'teacher_id' => '', // simulate empty selection
    'am_time_in_start' => $section->am_time_in_start ?? '07:00',
    'am_time_in_end' => $section->am_time_in_end ?? '08:00',
    'am_time_out_start' => $section->am_time_out_start ?? '11:00',
    'am_time_out_end' => $section->am_time_out_end ?? '12:00',
    'pm_time_in_start' => $section->pm_time_in_start ?? '13:00',
    'pm_time_in_end' => $section->pm_time_in_end ?? '14:00',
    'pm_time_out_start' => $section->pm_time_out_start ?? '16:00',
    'pm_time_out_end' => $section->pm_time_out_end ?? '17:00',
];

$server = [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
];

$request = Request::create('/dummy', 'POST', $data, [], [], $server, json_encode($data));

$response = $controller->update($request, $section);

// $response may be a RedirectResponse or JsonResponse
if (method_exists($response, 'getContent')) {
    echo $response->getContent() . "\n";
} else {
    // Try to stringify
    echo var_export($response, true) . "\n";
}
