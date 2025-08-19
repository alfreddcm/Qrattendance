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
        Schema::table('users', function (Blueprint $table) {
             $table->unsignedBigInteger('section_id')->nullable()->after('school_id');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
            
             $table->dropColumn('section_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove section_id and its foreign key
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
            
            // Add back section_name column
            $table->string('section_name')->nullable();
        });
    }
};
