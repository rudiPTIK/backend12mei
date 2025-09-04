<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
     Schema::create('consultation_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('consultation_id')->constrained()->cascadeOnDelete()->unique();
    $table->foreignId('guru_bk_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
    $table->text('summary')->nullable();
    $table->text('follow_up')->nullable();      // <-- WAJIB: dipakai controller-mu
    $table->dateTime('started_at')->nullable();
    $table->dateTime('ended_at')->nullable();
    $table->unsignedInteger('duration_minutes')->nullable();
    $table->timestamps();
        });
    }
  
};
