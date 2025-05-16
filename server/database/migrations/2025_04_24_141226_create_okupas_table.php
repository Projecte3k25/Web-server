<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('okupas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pais_id')->constrained('pais');
            $table->foreignId('player_id')->constrained('jugadors');
            $table->integer('tropes');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('okupas');
    }
};
