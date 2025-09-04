<?php
// database/migrations/2025_08_26_120002_create_consultation_reports_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('consultation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete()->unique();
            $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->text('follow_up')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->index('consultation_id');
            $table->index(['guru_bk_id','siswa_id']);
            $table->index('started_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('consultation_reports');
    }
};
