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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('id_no')->unique();
            $table->string('name');
            $table->char('gender', 1);
            $table->tinyInteger('age')->unsigned();
            $table->text('address');
            $table->string('cp_no', 20);
            $table->string('picture')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_relationship')->nullable();
            $table->string('contact_person_contact', 20)->nullable();
            $table->string('qr_code')->nullable()->unique();
             $table->string('stud_code')->nullable()->unique();
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
