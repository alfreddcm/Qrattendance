<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TestStudentLogin extends Command
{
    protected $signature = 'test:student-login {--id_no=103677140003 : The student ID to test}';
    protected $description = 'Test the complete student login flow';

    public function handle()
    {
        $id_no = $this->option('id_no');

        $this->info("=== Testing Student Login Flow ===");
        $this->line("Student ID: {$id_no}\n");

        // Step 1: Find student
        $this->info("Step 1: Finding student...");
        $student = Student::where('id_no', $id_no)->orWhere('stud_code', $id_no)->first();

        if (!$student) {
            $this->error("❌ Student not found!");
            return Command::FAILURE;
        }

        $this->info("✅ Student found: {$student->name}");
        $this->line("   ID: {$student->id}");
        $this->line("   ID_NO: {$student->id_no}");
        $this->line("   Role: {$student->role}");
        $this->line("   Has password: " . (!is_null($student->password) ? 'Yes' : 'No'));

        // Step 2: Check password
        $this->info("\nStep 2: Checking password...");
        $password = $id_no; // Default password is same as ID

        if (!$student->password) {
            $this->error("❌ Student has no password set!");
            return Command::FAILURE;
        }

        $passwordMatches = Hash::check($password, $student->password);
        if (!$passwordMatches) {
            $this->error("❌ Password does not match!");
            return Command::FAILURE;
        }

        $this->info("✅ Password verified!");

        // Step 3: Attempt login
        $this->info("\nStep 3: Attempting login with Auth::login()...");
        Auth::login($student);

        if (!Auth::check()) {
            $this->error("❌ Auth::check() failed after Auth::login()!");
            return Command::FAILURE;
        }

        $this->info("✅ Auth::login() successful!");

        // Step 4: Verify authenticated user
        $this->info("\nStep 4: Verifying authenticated user...");
        $authUser = Auth::user();

        $this->line("   Authenticated user ID: {$authUser->id}");
        $this->line("   Authenticated user class: " . class_basename(get_class($authUser)));
        $this->line("   Authenticated user role: {$authUser->role}");
        $this->line("   Is Student model: " . ($authUser instanceof \App\Models\Student ? 'Yes' : 'No'));

        // Step 5: Simulate redirect logic
        $this->info("\nStep 5: Testing redirect logic...");
        if ($authUser->role === 'student') {
            $this->info("✅ Role matches 'student'");
            $this->line("   Would redirect to: /student/dashboard");
            $this->line("   Route name: student.dashboard");
        } else {
            $this->error("❌ Role does not match 'student', got: {$authUser->role}");
            return Command::FAILURE;
        }

        // Step 6: Verify routes
        $this->info("\nStep 6: Verifying routes exist...");
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(function ($route) {
            return in_array('student.dashboard', (array) $route->getName());
        });

        if ($routes->empty()) {
            $this->error("❌ Route 'student.dashboard' not found!");
        } else {
            $this->info("✅ Route 'student.dashboard' exists");
            $routes->each(function ($route) {
                $this->line("   Path: {$route->uri}");
                $this->line("   Methods: " . implode(', ', $route->methods));
            });
        }

        // Step 7: Test middleware
        $this->info("\nStep 7: Checking middleware...");
        $student_routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(function ($route) {
            return strpos($route->uri, 'student') !== false && strpos($route->uri, 'dashboard') !== false;
        });

        if ($student_routes->empty()) {
            $this->error("❌ No student routes found!");
        } else {
            $student_routes->each(function ($route) {
                $middlewares = $route->middleware();
                $this->line("   Route: {$route->uri}");
                $this->line("   Middlewares: " . implode(', ', $middlewares));
            });
        }

        $this->info("\n=== ✅ All Tests Passed ===");
        return Command::SUCCESS;
    }
}
