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
            $table->dropColumn([
                'morning_period_start',
                'morning_period_end',
                'afternoon_period_start',
                'afternoon_period_end'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->time('morning_period_start')->nullable();
            $table->time('morning_period_end')->nullable();
            $table->time('afternoon_period_start')->nullable();
            $table->time('afternoon_period_end')->nullable();
        });
    }
};
