# Smart Student Password System - Pre-Migration Validation Script (Windows PowerShell)
# This script verifies all code changes are in place before running migrations

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Smart Student Password System - Validation" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

$passed = 0
$failed = 0

# Helper function to check if text exists in file
function Test-PatternInFile {
    param(
        [string]$FilePath,
        [string]$Pattern,
        [string]$Description
    )
    
    if (Test-Path $FilePath) {
        $content = Get-Content $FilePath -Raw
        if ($content -match [regex]::Escape($Pattern)) {
            Write-Host "✓ " -ForegroundColor Green -NoNewline
            Write-Host $Description
            return $true
        }
    }
    
    Write-Host "✗ " -ForegroundColor Red -NoNewline
    Write-Host $Description
    Write-Host "  File: $FilePath"
    Write-Host "  Pattern: $Pattern"
    return $false
}

# Helper function to check if file exists
function Test-FileExists {
    param(
        [string]$FilePath,
        [string]$Description
    )
    
    if (Test-Path $FilePath) {
        Write-Host "✓ " -ForegroundColor Green -NoNewline
        Write-Host $Description
        return $true
    }
    
    Write-Host "✗ " -ForegroundColor Red -NoNewline
    Write-Host $Description
    Write-Host "  File not found: $FilePath"
    return $false
}

Write-Host "Checking Core Model Implementation..." -ForegroundColor Yellow
Write-Host "======================================" -ForegroundColor Yellow
if (Test-PatternInFile "app/Models/Student.php" "use App\Observers\StudentObserver" "StudentObserver import statement") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "protected static function booted" "booted() method exists") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "static::observe(StudentObserver::class)" "Observer registration") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "'password_changed'" "password_changed in fillable array") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "public function usesDefaultPassword" "usesDefaultPassword() method") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "public function setDefaultPassword" "setDefaultPassword() method") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Models/Student.php" "public function updatePassword" "updatePassword() method") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "Checking Observer Implementation..." -ForegroundColor Yellow
Write-Host "====================================" -ForegroundColor Yellow
if (Test-FileExists "app/Observers/StudentObserver.php" "StudentObserver class exists") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Observers/StudentObserver.php" "public function updating" "Observer updating() method") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Observers/StudentObserver.php" "public function created" "Observer created() method") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Observers/StudentObserver.php" "Hash::check" "Hash::check() usage for password detection") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "Checking Import Implementation..." -ForegroundColor Yellow
Write-Host "==================================" -ForegroundColor Yellow
if (Test-PatternInFile "app/Imports/StudentsImport.php" "'password' => Hash::make" "Password hashing on import") { $passed++ } else { $failed++ }
if (Test-PatternInFile "app/Imports/StudentsImport.php" "'password_changed' => false" "password_changed flag on import") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "Checking Password Change Integration..." -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
if (Test-PatternInFile "app/Http/Controllers/StudentDashboardController.php" "updatePassword" "updatePassword() call in controller") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "Checking Database Migrations..." -ForegroundColor Yellow
Write-Host "===============================" -ForegroundColor Yellow
if (Test-FileExists "database/migrations/2026_04_07_000000_add_password_changed_to_students_table.php" "password_changed migration exists") { $passed++ } else { $failed++ }
if (Test-FileExists "database/migrations/2026_05_18_000001_set_default_passwords_for_students.php" "set_default_passwords migration exists") { $passed++ } else { $failed++ }
if (Test-PatternInFile "database/migrations/2026_04_07_000000_add_password_changed_to_students_table.php" "boolean('password_changed')" "password_changed column definition") { $passed++ } else { $failed++ }
if (Test-PatternInFile "database/migrations/2026_05_18_000001_set_default_passwords_for_students.php" "Hash::make" "Password hashing in migration") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "Checking Documentation..." -ForegroundColor Yellow
Write-Host "=========================" -ForegroundColor Yellow
if (Test-FileExists "STUDENT_PASSWORD_SYSTEM.md" "Comprehensive documentation exists") { $passed++ } else { $failed++ }
if (Test-FileExists "SETUP_INSTRUCTIONS.md" "Setup instructions exist") { $passed++ } else { $failed++ }
if (Test-FileExists "CHANGES_SUMMARY.md" "Changes summary exists") { $passed++ } else { $failed++ }
Write-Host ""

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Validation Results" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Passed: " -NoNewline
Write-Host $passed -ForegroundColor Green
Write-Host "Failed: " -NoNewline
Write-Host $failed -ForegroundColor Red
Write-Host ""

if ($failed -eq 0) {
    Write-Host "✓ All checks passed! System is ready for migration." -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Backup your database:"
    Write-Host "   mysqldump -u root -p scan > backup_`$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
    Write-Host ""
    Write-Host "2. Run migrations:"
    Write-Host "   php artisan migrate"
    Write-Host ""
    Write-Host "3. Clear cache:"
    Write-Host "   php artisan config:clear && php artisan cache:clear"
    Write-Host ""
    Write-Host "4. Test the system (see SETUP_INSTRUCTIONS.md for test cases)"
    exit 0
} else {
    Write-Host "✗ Some checks failed. Please review the errors above." -ForegroundColor Red
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Yellow
    Write-Host "- File paths are relative to project root"
    Write-Host "- Check for typos in class/method names"
    Write-Host "- Ensure you haven't accidentally deleted code"
    Write-Host ""
    Write-Host "For help, see STUDENT_PASSWORD_SYSTEM.md or CHANGES_SUMMARY.md"
    exit 1
}
