<?php

// database/migrations/2025_09_02_000010_alter_consultation_reports_visibility_ack.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('consultation_reports', function (Blueprint $t) {
            if (!Schema::hasColumn('consultation_reports', 'student_visible')) {
                $t->boolean('student_visible')->default(true)->after('duration_minutes');
            }
            if (!Schema::hasColumn('consultation_reports', 'acknowledged_at')) {
                $t->timestamp('acknowledged_at')->nullable()->after('student_visible');
            }
            if (!Schema::hasColumn('consultation_reports', 'private_notes')) {
                $t->text('private_notes')->nullable()->after('follow_up');
            }
        });
    }
    public function down(): void {
        Schema::table('consultation_reports', function (Blueprint $t) {
            if (Schema::hasColumn('consultation_reports', 'private_notes')) $t->dropColumn('private_notes');
            if (Schema::hasColumn('consultation_reports', 'acknowledged_at')) $t->dropColumn('acknowledged_at');
            if (Schema::hasColumn('consultation_reports', 'student_visible')) $t->dropColumn('student_visible');
        });
    }
};
