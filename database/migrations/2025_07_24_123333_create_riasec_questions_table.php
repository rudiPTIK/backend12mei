<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecQuestionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('riasec_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('question_id')->unique()->comment('ID soal dari O*NET');
            $table->text('question_text')->comment('Teks soal');
            $table->string('category', 50)->comment('Kategori RIASEC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riasec_questions');
    }
}
