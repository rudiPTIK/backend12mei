<?php

// database/migrations/xxxx_xx_xx_create_riasec_results_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecResultsTable extends Migration
{
    public function up()
    {
        Schema::create('riasec_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('score_R')->default(0);
            $table->integer('score_I')->default(0);
            $table->integer('score_A')->default(0);
            $table->integer('score_S')->default(0);
            $table->integer('score_E')->default(0);
            $table->integer('score_C')->default(0);
            $table->string('top_domains'); // misal "R,A"
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riasec_results');
    }
}
