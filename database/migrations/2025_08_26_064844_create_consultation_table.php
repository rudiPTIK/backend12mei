<?php
// database/migrations/2025_08_26_120001_create_consultations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('consultations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('jadwal_id')->nullable()->constrained('jadwal')->nullOnDelete();

            $t->enum('mode', ['video', 'chat', 'offline'])->default('video');
            $t->string('topic')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable();

            // Selaras dengan controller
            $t->enum('status', ['scheduled', 'ongoing', 'ended'])->default('scheduled');
            $t->string('recording_url')->nullable();
            $t->timestamps();

            // Indeks umum
            $t->unique('jadwal_id');
            $t->index(['counselor_id','student_id']);
            $t->index('status');
            $t->index('started_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('consultations');
    }
};
