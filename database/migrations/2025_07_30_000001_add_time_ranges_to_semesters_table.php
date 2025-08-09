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
        Schema::table('semesters', function (Blueprint $table) {
            // Add only AM time in range and PM time out range
            $table->time('am_time_in_start')->nullable()->after('end_date');
            $table->time('am_time_in_end')->nullable()->after('am_time_in_start');
            $table->time('pm_time_out_start')->nullable()->after('am_time_in_end');
            $table->time('pm_time_out_end')->nullable()->after('pm_time_out_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            // Drop the time range columns
            $table->dropColumn([
                'am_time_in_start',
                'am_time_in_end',
                'pm_time_out_start',
                'pm_time_out_end'
            ]);
        });
    }
};
