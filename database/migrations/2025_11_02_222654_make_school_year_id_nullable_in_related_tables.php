<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make school_year_id nullable in students table
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'school_year_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable()->change();
            });
        }

        // Make school_year_id nullable in sections table
        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'school_year_id')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable()->change();
            });
        }

        // Make school_year_id nullable in attendances table
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'school_year_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable()->change();
            });
        }

        // Make school_year_id nullable in attendance_sessions table
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'school_year_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make school_year_id NOT NULL again (if safe to do so)
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'school_year_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'school_year_id')) {
            Schema::table('sections', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'school_year_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'school_year_id')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('school_year_id')->nullable(false)->change();
            });
        }
    }
};
