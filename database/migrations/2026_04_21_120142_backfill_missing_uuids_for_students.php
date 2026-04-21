<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Backfill missing UUIDs for all tables that use HasUuidRouteKey.
     * Pre-existing records created before the UUID migration won't have UUIDs,
     * which causes UrlGenerationException when generating routes.
     */
    public function up(): void
    {
        $tables = [
            'students',
            'users',
            'sections',
            'schools',
            'school_years',
            'attendances',
            'attendance_sessions',
            'attendance_codes',
            'outbound_messages',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            $records = DB::table($table)
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->pluck('id');

            foreach ($records as $id) {
                DB::table($table)
                    ->where('id', $id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed — removing UUIDs would break routes
    }
};
