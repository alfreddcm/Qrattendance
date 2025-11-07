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
        // Ensure school_years table has proper structure
        if (!Schema::hasTable('school_years')) {
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
                
                // Add indexes
                $table->index(['school_id', 'status']);
                $table->index('status');
            });
        } else {
            // Update existing table structure if needed
            Schema::table('school_years', function (Blueprint $table) {
                // Make required fields non-nullable if they aren't already
                if (Schema::hasColumn('school_years', 'school_year_start')) {
                    DB::statement('ALTER TABLE school_years MODIFY school_year_start SMALLINT UNSIGNED NOT NULL');
                }
                if (Schema::hasColumn('school_years', 'school_year_end')) {
                    DB::statement('ALTER TABLE school_years MODIFY school_year_end SMALLINT UNSIGNED NOT NULL');
                }
                if (Schema::hasColumn('school_years', 'name')) {
                    DB::statement('ALTER TABLE school_years MODIFY name VARCHAR(255) NOT NULL');
                }
                if (Schema::hasColumn('school_years', 'start_date')) {
                    DB::statement('ALTER TABLE school_years MODIFY start_date DATE NOT NULL');
                }
                if (Schema::hasColumn('school_years', 'end_date')) {
                    DB::statement('ALTER TABLE school_years MODIFY end_date DATE NOT NULL');
                }
            });
        }
        
        // Update existing school year status based on current date
        $currentDate = now();
        
        // Set status to 'completed' for past school years
        DB::table('school_years')
            ->where('end_date', '<', $currentDate)
            ->update(['status' => 'completed']);
            
        // Set status to 'active' for current school years
        DB::table('school_years')
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->update(['status' => 'active']);
            
        // Set status to 'upcoming' for future school years
        DB::table('school_years')
            ->where('start_date', '>', $currentDate)
            ->update(['status' => 'upcoming']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table as it may contain important data
        // Just revert status changes if needed
    }
};
