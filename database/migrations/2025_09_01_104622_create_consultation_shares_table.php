<?php

// database/migrations/2025_08_26_120000_create_consultation_report_shares_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('consultation_report_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('guru_bk_id');
            $table->enum('to_role', ['waka', 'kepsek']);
            $table->string('email');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->cascadeOnDelete();
            $table->foreign('guru_bk_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['consultation_id', 'to_role']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('consultation_report_shares');
    }
};
