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
        Schema::create('outbound_messages_complete', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->enum('recipient_type', ['individual', 'broadcast'])->default('individual');
            $table->integer('recipient_count')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('message')->nullable();
            $table->string('message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            
            // Indexes
            $table->index(['teacher_id', 'created_at']);
            $table->index(['student_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('recipient_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_messages_complete');
    }
};
