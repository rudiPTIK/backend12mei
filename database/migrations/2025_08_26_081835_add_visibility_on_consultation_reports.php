<?php

// database/migrations/xxxx_add_visibility_on_consultation_reports.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('consultation_reports', function (Blueprint $t) {
      $t->boolean('student_visible')->default(true);
      $t->text('private_notes')->nullable();   // catatan hanya untuk konselor
      $t->timestamp('acknowledged_at')->nullable(); // siswa menandai “Saya paham”
    });
  }
  public function down(): void {
    Schema::table('consultation_reports', function (Blueprint $t) {
      $t->dropColumn(['student_visible','private_notes','acknowledged_at']);
    });
  }
};
