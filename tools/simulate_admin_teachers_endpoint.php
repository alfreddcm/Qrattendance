<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\AdminController;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$admin = User::where('role', 'admin')->first();
$school = School::first();

if (!$admin || !$school) {
    echo "Missing admin user or school record.\n";
    exit(1);
}

Auth::login($admin);

$controller = app(AdminController::class);
$response = $controller->getTeachersBySchool($school);

echo "HTTP status: " . $response->status() . "\n";
echo $response->getContent() . "\n";
