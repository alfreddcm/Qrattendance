<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all students who have NULL password to use hashed LRN as password
        $students = DB::table('students')->whereNull('password')->get();
        
        foreach ($students as $student) {
            if (!empty($student->id_no)) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'password' => Hash::make($student->id_no),
                        'password_changed' => false,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is irreversible as it sets actual passwords
        // Reverting would delete all student access
    }
};
