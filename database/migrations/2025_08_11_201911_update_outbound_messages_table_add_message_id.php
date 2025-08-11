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
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('message')->nullable();
            $table->string('message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->index(['student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'contact_number', 'message', 'message_id', 'status']);
        });
    }
};
