<?php
// database/migrations/2025_08_26_120000_add_indexes_for_consultations_and_reports.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('consultations', function (Blueprint $t) {
      $t->index(['counselor_id','student_id']);
      $t->index('status');
      $t->index('started_at');
    });
    Schema::table('consultation_reports', function (Blueprint $t) {
      $t->index('consultation_id');
      $t->index(['guru_bk_id','siswa_id']);
      $t->index('started_at');
    });
  }
  public function down(): void {
    Schema::table('consultations', function (Blueprint $t) {
      $t->dropIndex(['consultations_counselor_id_student_id_index']);
      $t->dropIndex(['consultations_status_index']);
      $t->dropIndex(['consultations_started_at_index']);
    });
    Schema::table('consultation_reports', function (Blueprint $t) {
      $t->dropIndex(['consultation_reports_consultation_id_index']);
      $t->dropIndex(['consultation_reports_guru_bk_id_siswa_id_index']);
      $t->dropIndex(['consultation_reports_started_at_index']);
    });
  }
};
