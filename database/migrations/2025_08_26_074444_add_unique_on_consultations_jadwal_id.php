<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('consultations')) {
            return;
        }

        // Cek apakah index dengan nama ini sudah ada
        $exists = collect(DB::select("
            SHOW INDEX FROM consultations WHERE Key_name = 'consultations_jadwal_id_unique'
        "))->isNotEmpty();

        if (!$exists) {
            Schema::table('consultations', function (Blueprint $t) {
                $t->unique('jadwal_id', 'consultations_jadwal_id_unique');
            });
        }
        // kalau sudah ada, tidak melakukan apa-apa
    }

    public function down(): void
    {
        if (!Schema::hasTable('consultations')) {
            return;
        }

        // Hanya drop kalau memang ada
        $exists = collect(DB::select("
            SHOW INDEX FROM consultations WHERE Key_name = 'consultations_jadwal_id_unique'
        "))->isNotEmpty();

        if ($exists) {
            Schema::table('consultations', function (Blueprint $t) {
                $t->dropUnique('consultations_jadwal_id_unique');
            });
        }
    }
};
