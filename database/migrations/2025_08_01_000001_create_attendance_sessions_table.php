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
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 100)->unique();  
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
            $table->string('session_name')->nullable();  
            $table->enum('status', ['active', 'expired', 'closed'])->default('active');
            // Removed expires_at
            $table->timestamp('started_at')->nullable();  
            $table->timestamp('closed_at')->nullable();  
            $table->integer('access_count')->default(0); 
            $table->json('access_log')->nullable();  
            $table->integer('attendance_count')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

        
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
