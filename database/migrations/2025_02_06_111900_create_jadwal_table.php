<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_bk_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('siswa_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->enum('status', ['tersedia', 'dipesan', 'selesai'])->default('tersedia');
            $table->timestamps();

            
        });
    }

    public function down()
    {
        Schema::dropIfExists('jadwal');
    }
};
