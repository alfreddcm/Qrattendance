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
        // Check current table structure and add missing columns
        if (Schema::hasTable('school_years')) {
            Schema::table('school_years', function (Blueprint $table) {
                // Check if columns exist and add if missing
                if (!Schema::hasColumn('school_years', 'school_id')) {
                    $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                }
                if (!Schema::hasColumn('school_years', 'school_year_start')) {
                    $table->unsignedSmallInteger('school_year_start')->nullable();
                }
                if (!Schema::hasColumn('school_years', 'school_year_end')) {
                    $table->unsignedSmallInteger('school_year_end')->nullable();
                }
                if (!Schema::hasColumn('school_years', 'name')) {
                    $table->string('name')->nullable();
                }
                if (!Schema::hasColumn('school_years', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (!Schema::hasColumn('school_years', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (!Schema::hasColumn('school_years', 'status')) {
                    $table->string('status')->default('active');
                }
                if (!Schema::hasColumn('school_years', 'description')) {
                    $table->text('description')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only adds missing columns, no need to rollback
    }
};
