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
        // Step 1: Create the new complete table (already done by previous migration)
        // Step 2: Copy data from old table to new table
        if (Schema::hasTable('outbound_messages')) {
            // Get first teacher to assign to messages without teacher_id
            $firstTeacher = DB::table('users')->first();
            $defaultTeacherId = $firstTeacher ? $firstTeacher->id : 1;
            
            // Copy existing data
            $existingMessages = DB::table('outbound_messages')->get();
            
            foreach ($existingMessages as $message) {
                // Ensure teacher_id is valid
                $teacherId = isset($message->teacher_id) && $message->teacher_id > 0 ? 
                    $message->teacher_id : $defaultTeacherId;
                
                DB::table('outbound_messages_complete')->insert([
                    'id' => $message->id,
                    'teacher_id' => $teacherId,
                    'recipient_type' => $message->recipient_type ?? 'individual',
                    'recipient_count' => $message->recipient_count ?? 1,
                    'student_id' => $message->student_id,
                    'contact_number' => $message->contact_number,
                    'message' => $message->message,
                    'message_id' => $message->message_id,
                    'status' => $message->status ?? 'pending',
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ]);
            }
        }
        
        // Step 3: Drop old table and rename new table
        Schema::dropIfExists('outbound_messages');
        Schema::rename('outbound_messages_complete', 'outbound_messages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create old table structure
        Schema::create('outbound_messages_old', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('message')->nullable();
            $table->string('message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->index(['student_id', 'created_at']);
        });
        
        // Copy data back (losing teacher and recipient info)
        if (Schema::hasTable('outbound_messages')) {
            $messages = DB::table('outbound_messages')->get();
            
            foreach ($messages as $message) {
                DB::table('outbound_messages_old')->insert([
                    'id' => $message->id,
                    'student_id' => $message->student_id,
                    'contact_number' => $message->contact_number,
                    'message' => $message->message,
                    'message_id' => $message->message_id,
                    'status' => $message->status,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ]);
            }
        }
        
        Schema::dropIfExists('outbound_messages');
        Schema::rename('outbound_messages_old', 'outbound_messages');
    }
};
