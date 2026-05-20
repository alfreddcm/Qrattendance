<?php
// Ultra-simple password update - directly using PHP/Blade
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('tinker', [
    '--execute' => <<<'EOT'
$s = \App\Models\Student::where('id_no', '500012200001')->first();
if($s) {
  $s->password = \Hash::make('500012200001');
  $s->password_changed = false;
  $s->save();
  echo "Password updated for student: " . $s->name;
} else {
  echo "Student not found";
}
EOT
]);
