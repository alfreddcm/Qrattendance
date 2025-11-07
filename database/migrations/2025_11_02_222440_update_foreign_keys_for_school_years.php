<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip foreign key constraints for now due to data type issues
        // Foreign keys can be added later when all data integrity issues are resolved
        // No changes needed for now
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Remove foreign key constraints
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                try {
                    $table->dropForeign(['school_year_id']);
                } catch (\Exception $e) {
                    // Continue if foreign key doesn't exist
                }
            });
        }

        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                try {
                    $table->dropForeign(['school_year_id']);
                } catch (\Exception $e) {
                    // Continue if foreign key doesn't exist
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                try {
                    $table->dropForeign(['school_year_id']);
                } catch (\Exception $e) {
                    // Continue if foreign key doesn't exist
                }
            });
        }

        if (Schema::hasTable('attendance_sessions')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['school_year_id']);
                } catch (\Exception $e) {
                    // Continue if foreign key doesn't exist
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
