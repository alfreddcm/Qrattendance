<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change recipient_type from enum to string to support new values
        // The column already stores values outside the original enum (teacher, teachers_broadcast, etc.)
        Schema::table('outbound_messages', function (Blueprint $table) {
            // Make teacher_id nullable (admin can send without teacher context)
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
        });

        // Change enum to string - need raw SQL for MySQL enum->string conversion
        DB::statement("ALTER TABLE outbound_messages MODIFY COLUMN recipient_type VARCHAR(50) DEFAULT 'individual'");

        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->string('send_type', 20)->nullable()->after('recipient_type')
                  ->comment('all, selected, specific');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropColumn('send_type');
        });

        DB::statement("ALTER TABLE outbound_messages MODIFY COLUMN recipient_type ENUM('individual', 'broadcast') DEFAULT 'individual'");
    }
};
