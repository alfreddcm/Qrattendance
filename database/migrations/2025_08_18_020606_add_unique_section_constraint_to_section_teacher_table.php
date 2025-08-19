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
            // Add unique constraint on section_id to ensure one teacher per section
            $table->unique('section_id', 'unique_section_per_teacher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('section_teacher', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('unique_section_per_teacher');
        });
    }
};
