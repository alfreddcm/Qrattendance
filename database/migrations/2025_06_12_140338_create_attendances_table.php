<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_id')->nullable();
            $table->date('date');
            $table->time('time_in_am')->nullable();
            $table->time('time_out_am')->nullable();
            $table->time('time_in_pm')->nullable();
            $table->time('time_out_pm')->nullable();
            $table->enum('am_status', ['Early', 'On Time', 'Tardy', 'Late'])->nullable();
            $table->enum('pm_status', ['Early', 'On Time', 'Tardy', 'Late'])->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
            
            $table->unique(['student_id', 'date'], 'unique_student_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};