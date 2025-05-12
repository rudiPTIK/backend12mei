<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecCareerResultTable extends Migration
{
    public function up()
    {
        Schema::create('riasec_career_result', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')
                  ->constrained('riasec_results')
                  ->cascadeOnDelete();
            $table->foreignId('career_id')
                  ->constrained('riasec_careers')
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riasec_career_result');
    }
}
