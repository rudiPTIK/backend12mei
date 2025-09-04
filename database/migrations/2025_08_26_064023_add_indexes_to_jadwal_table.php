<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            // beri nama index biar mudah di-drop saat rollback
            $table->index(['guru_bk_id', 'waktu_mulai'], 'jadwal_guru_mulai_idx');
            $table->index(['siswa_id', 'waktu_mulai'], 'jadwal_siswa_mulai_idx');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropIndex('jadwal_guru_mulai_idx');
            $table->dropIndex('jadwal_siswa_mulai_idx');
        });
    }
};
