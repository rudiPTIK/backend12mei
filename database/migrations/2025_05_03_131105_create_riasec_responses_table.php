<?php

// database/migrations/xxxx_xx_xx_create_riasec_responses_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecResponsesTable extends Migration
{
    public function up()
    {
        Schema::create('riasec_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('riasec_questions')->cascadeOnDelete();
            $table->tinyInteger('score'); // 1–5
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riasec_responses');
    }
}
