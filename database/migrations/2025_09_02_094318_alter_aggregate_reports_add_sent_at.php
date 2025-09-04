<?php

// database/migrations/2025_09_02_000001_alter_aggregate_reports_add_sent_at.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('aggregate_reports', function (Blueprint $t) {
            if (!Schema::hasColumn('aggregate_reports', 'sent_at')) {
                $t->timestamp('sent_at')->nullable()->after('note');
            }
        });
    }
    public function down(): void {
        Schema::table('aggregate_reports', function (Blueprint $t) {
            if (Schema::hasColumn('aggregate_reports', 'sent_at')) {
                $t->dropColumn('sent_at');
            }
        });
    }
};
