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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('school_id')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            // Period boundaries for the semester
            $table->time('morning_period_start')->nullable();
            $table->time('morning_period_end')->nullable();
            $table->time('afternoon_period_start')->nullable();
            $table->time('afternoon_period_end')->nullable();

            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->text('description')->nullable();
            
            $table->timestamps();
        
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};