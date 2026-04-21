<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'students',
            'attendances',
            'sections',
            'school_years',
            'schools',
            'attendance_codes',
            'attendance_sessions',
            'outbound_messages',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                if (!Schema::hasColumn($blueprint->getTable(), 'uuid')) {
                    $blueprint->uuid('uuid')->nullable()->after('id');
                }
            });
        }

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('uuid')
                ->orderBy('id')
                ->chunkById(500, function ($records) use ($table): void {
                    foreach ($records as $record) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });
        }

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unique('uuid');
                $blueprint->index('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'students',
            'attendances',
            'sections',
            'school_years',
            'schools',
            'attendance_codes',
            'attendance_sessions',
            'outbound_messages',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropIndex($table . '_uuid_index');
                $blueprint->dropUnique($table . '_uuid_unique');
                $blueprint->dropColumn('uuid');
            });
        }
    }
};
