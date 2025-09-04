<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('riasec_test_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riasec_test_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('onet_code')->nullable();   // O*NET-SOC code
            $table->unsignedTinyInteger('rank')->default(0); // urutan rekomendasi
            $table->timestamps();

            $table->index(['riasec_test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riasec_test_careers');
    }
};
