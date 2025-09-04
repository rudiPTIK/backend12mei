<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('riasec_tests', function (Blueprint $table) {
            $table->id();
            // optional jika pakai auth:
            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }

            // string 60 jawaban (setelah dinormalisasi), mis: "345...5"
            $table->string('answers', 60);
            // JSON skor per kode: {"R":25,"I":33,...}
            $table->json('scores');

            // kolom denormalisasi untuk query cepat & sort
            $table->unsignedTinyInteger('r')->default(0);
            $table->unsignedTinyInteger('i')->default(0);
            $table->unsignedTinyInteger('a')->default(0);
            $table->unsignedTinyInteger('s')->default(0);
            $table->unsignedTinyInteger('e')->default(0);
            $table->unsignedTinyInteger('c')->default(0);

            // tiga kode teratas, mis "IES"
            $table->string('code3', 3)->nullable();

            // metadata opsional
            $table->string('client')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['code3']);
            $table->index(['answers']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riasec_tests');
    }
};
