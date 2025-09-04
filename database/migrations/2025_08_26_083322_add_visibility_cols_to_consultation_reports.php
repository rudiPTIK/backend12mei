<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('consultation_reports', function (Blueprint $t) {
            if (!Schema::hasColumn('consultation_reports', 'student_visible')) {
                $t->boolean('student_visible')->default(true)->after('follow_up');
            }
            if (!Schema::hasColumn('consultation_reports', 'acknowledged_at')) {
                $t->timestamp('acknowledged_at')->nullable()->after('ended_at');
            }
        });
    }

    public function down(): void {
        Schema::table('consultation_reports', function (Blueprint $t) {
            if (Schema::hasColumn('consultation_reports', 'student_visible')) {
                $t->dropColumn('student_visible');
            }
            if (Schema::hasColumn('consultation_reports', 'acknowledged_at')) {
                $t->dropColumn('acknowledged_at');
            }
        });
    }
};
