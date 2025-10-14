<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('school_id')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->text('description')->nullable();
            
            $table->timestamps();
        
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};