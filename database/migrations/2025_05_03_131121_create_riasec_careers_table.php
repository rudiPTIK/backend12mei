<?php

// database/migrations/xxxx_xx_xx_create_riasec_careers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiasecCareersTable extends Migration
{
    public function up()
    {
        Schema::create('riasec_careers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('riasec_type', ['R','I','A','S','E','C']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riasec_careers');
    }
}
