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

            $table->time('am_time_in_start')->nullable()->after('end_date');
            $table->time('am_time_in_end')->nullable()->after('am_time_in_start');
            $table->time('pm_time_out_start')->nullable()->after('am_time_in_end');
            $table->time('pm_time_out_end')->nullable()->after('pm_time_out_start');
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