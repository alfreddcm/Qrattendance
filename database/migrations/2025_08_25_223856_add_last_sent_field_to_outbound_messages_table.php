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
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->timestamp('last_sent_at')->nullable()->after('status');
            $table->index(['contact_number', 'last_sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $table) {
            $table->dropIndex(['contact_number', 'last_sent_at']);
            $table->dropColumn('last_sent_at');
        });
    }
};
