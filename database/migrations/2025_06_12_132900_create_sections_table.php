<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('gradelevel');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->onDelete('set null');
            $table->time('am_time_in_start')->nullable();
            $table->time('am_time_in_end')->nullable();
            $table->time('am_time_out_start')->nullable();
            $table->time('am_time_out_end')->nullable();
            $table->time('pm_time_in_start')->nullable();
            $table->time('pm_time_in_end')->nullable();
            $table->time('pm_time_out_start')->nullable();
            $table->time('pm_time_out_end')->nullable();
            $table->timestamps();
            
            $table->unique(['teacher_id', 'school_year_id', 'name'], 'unique_teacher_school_year_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
