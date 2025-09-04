<?php

// database/migrations/2025_09_02_000002_alter_recipients_add_sender_id.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('aggregate_report_recipients', function (Blueprint $t) {
            if (!Schema::hasColumn('aggregate_report_recipients', 'sender_id')) {
                $t->foreignId('sender_id')->after('recipient_id')->constrained('users')->cascadeOnDelete();
                $t->index('sender_id');
            }
        });
    }
    public function down(): void {
        Schema::table('aggregate_report_recipients', function (Blueprint $t) {
            if (Schema::hasColumn('aggregate_report_recipients', 'sender_id')) {
                $t->dropConstrainedForeignId('sender_id');
            }
        });
    }
};
