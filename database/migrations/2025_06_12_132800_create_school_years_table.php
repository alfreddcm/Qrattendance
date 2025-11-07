<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->unsignedSmallInteger('school_year_start');
            $table->unsignedSmallInteger('school_year_end');
            $table->string('name', 255);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 255)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
