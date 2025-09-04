<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aggregate_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $t->enum('period_type', ['daily','weekly']);
            $t->date('period_start');
            $t->date('period_end');
            $t->string('title')->nullable();
            $t->text('summary')->nullable();
            $t->unsignedInteger('total_sessions')->default(0);
            $t->unsignedInteger('total_students')->default(0);
            $t->json('items')->nullable(); // daftar ringkasan tiap sesi
            $t->text('note')->nullable();  // catatan dari guru BK untuk pimpinan
            $t->timestamp('published_at')->nullable();
            $t->timestamps();

            $t->unique(
                ['counselor_id','period_type','period_start','period_end'],
                'uniq_counselor_period'
            );
        });
    }

    public function down(): void {
        Schema::dropIfExists('aggregate_reports');
    }
};
