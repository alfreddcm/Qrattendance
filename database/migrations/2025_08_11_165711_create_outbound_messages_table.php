<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->enum('recipient_type', ['individual', 'broadcast'])->default('individual');
            $table->integer('recipient_count')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('message')->nullable();
            $table->string('message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            
            $table->index(['teacher_id', 'created_at']);
            $table->index(['student_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('recipient_type');
            $table->index(['contact_number', 'last_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
