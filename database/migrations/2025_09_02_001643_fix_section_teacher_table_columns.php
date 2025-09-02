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
        Schema::table('section_teacher', function (Blueprint $table) {
            // Add foreign key columns if they don't exist
            if (!Schema::hasColumn('section_teacher', 'section_id')) {
                $table->unsignedBigInteger('section_id');
            }
            if (!Schema::hasColumn('section_teacher', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id');
            }
            
             $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            
             $table->unique(['section_id', 'teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('section_teacher', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropUnique(['section_id', 'teacher_id']);
            $table->dropColumn(['section_id', 'teacher_id']);
        });
    }
};
