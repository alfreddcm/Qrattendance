#!/bin/bash

# Smart Student Password System - Pre-Migration Validation Script
# This script verifies all code changes are in place before running migrations

echo "================================================"
echo "Smart Student Password System - Validation"
echo "================================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

passed=0
failed=0

# Helper function to check if text exists in file
check_in_file() {
    local file=$1
    local pattern=$2
    local description=$3
    
    if grep -q "$pattern" "$file" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} $description"
        ((passed++))
    else
        echo -e "${RED}✗${NC} $description"
        echo "  File: $file"
        echo "  Pattern: $pattern"
        ((failed++))
    fi
}

# Helper function to check if file exists
check_file_exists() {
    local file=$1
    local description=$2
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $description"
        ((passed++))
    else
        echo -e "${RED}✗${NC} $description"
        echo "  File not found: $file"
        ((failed++))
    fi
}

echo "Checking Core Model Implementation..."
echo "======================================"
check_in_file "app/Models/Student.php" "use App\\\\Observers\\\\StudentObserver" "StudentObserver import statement"
check_in_file "app/Models/Student.php" "protected static function booted" "booted() method exists"
check_in_file "app/Models/Student.php" "static::observe(StudentObserver::class)" "Observer registration"
check_in_file "app/Models/Student.php" "'password_changed'" "password_changed in fillable array"
check_in_file "app/Models/Student.php" "public function usesDefaultPassword" "usesDefaultPassword() method"
check_in_file "app/Models/Student.php" "public function setDefaultPassword" "setDefaultPassword() method"
check_in_file "app/Models/Student.php" "public function updatePassword" "updatePassword() method"
echo ""

echo "Checking Observer Implementation..."
echo "===================================="
check_file_exists "app/Observers/StudentObserver.php" "StudentObserver class exists"
check_in_file "app/Observers/StudentObserver.php" "public function updating" "Observer updating() method"
check_in_file "app/Observers/StudentObserver.php" "public function created" "Observer created() method"
check_in_file "app/Observers/StudentObserver.php" "Hash::check" "Hash::check() usage for password detection"
echo ""

echo "Checking Import Implementation..."
echo "=================================="
check_in_file "app/Imports/StudentsImport.php" "'password' => Hash::make" "Password hashing on import"
check_in_file "app/Imports/StudentsImport.php" "'password_changed' => false" "password_changed flag on import"
echo ""

echo "Checking Password Change Integration..."
echo "========================================"
check_in_file "app/Http/Controllers/StudentDashboardController.php" "updatePassword(\\\$request->password)" "updatePassword() call in controller"
echo ""

echo "Checking Database Migrations..."
echo "==============================="
check_file_exists "database/migrations/2026_04_07_000000_add_password_changed_to_students_table.php" "password_changed migration exists"
check_file_exists "database/migrations/2026_05_18_000001_set_default_passwords_for_students.php" "set_default_passwords migration exists"
check_in_file "database/migrations/2026_04_07_000000_add_password_changed_to_students_table.php" "boolean('password_changed')" "password_changed column definition"
check_in_file "database/migrations/2026_05_18_000001_set_default_passwords_for_students.php" "Hash::make" "Password hashing in migration"
echo ""

echo "Checking Documentation..."
echo "========================="
check_file_exists "STUDENT_PASSWORD_SYSTEM.md" "Comprehensive documentation exists"
check_file_exists "SETUP_INSTRUCTIONS.md" "Setup instructions exist"
check_file_exists "CHANGES_SUMMARY.md" "Changes summary exists"
echo ""

echo "================================================"
echo "Validation Results"
echo "================================================"
echo -e "${GREEN}Passed: $passed${NC}"
echo -e "${RED}Failed: $failed${NC}"
echo ""

if [ $failed -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! System is ready for migration.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Backup your database:"
    echo "   mysqldump -u root -p scan > backup_\$(date +%Y%m%d_%H%M%S).sql"
    echo ""
    echo "2. Run migrations:"
    echo "   php artisan migrate"
    echo ""
    echo "3. Clear cache:"
    echo "   php artisan config:clear && php artisan cache:clear"
    echo ""
    echo "4. Test the system (see SETUP_INSTRUCTIONS.md for test cases)"
    exit 0
else
    echo -e "${RED}✗ Some checks failed. Please review the errors above.${NC}"
    echo ""
    echo "Common issues:"
    echo "- File paths are relative to project root"
    echo "- Check for typos in class/method names"
    echo "- Ensure you haven't accidentally deleted code"
    echo ""
    echo "For help, see STUDENT_PASSWORD_SYSTEM.md or CHANGES_SUMMARY.md"
    exit 1
fi
