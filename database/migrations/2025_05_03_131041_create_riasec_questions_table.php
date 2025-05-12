<?php

// database/migrations/xxxx_xx_xx_create_riasec_questions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('riasec_questions', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->enum('riasec_type', ['R','I','A','S','E','C']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riasec_questions');
    }
}
