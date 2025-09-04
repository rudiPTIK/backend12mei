<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aggregate_report_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('aggregate_reports')->cascadeOnDelete();
            $t->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $t->string('recipient_role', 32);
            $t->text('note')->nullable();   // catatan singkat yg dikirimkan guru BK utk penerima
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();

            $t->unique(['report_id','recipient_id'],'uniq_report_recipient');
        });
    }

    public function down(): void {
        Schema::dropIfExists('aggregate_report_recipients');
    }
};
