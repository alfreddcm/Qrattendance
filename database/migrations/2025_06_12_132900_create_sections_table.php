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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('gradelevel');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            
            // Morning period times
            $table->time('am_time_in_start')->nullable();
            $table->time('am_time_in_end')->nullable();
            $table->time('am_time_out_start')->nullable();
            $table->time('am_time_out_end')->nullable();
            
            // Afternoon period times
            $table->time('pm_time_in_start')->nullable();
            $table->time('pm_time_in_end')->nullable();
            $table->time('pm_time_out_start')->nullable();
            $table->time('pm_time_out_end')->nullable();
            
            $table->timestamps();
            
            // Unique constraint: one section per teacher per semester
            $table->unique(['teacher_id', 'semester_id', 'name'], 'unique_teacher_semester_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
