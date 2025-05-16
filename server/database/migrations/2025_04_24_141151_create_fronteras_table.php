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
        Schema::create('fronteras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pais1_id')->constrained('pais');
            $table->foreignId('pais2_id')->constrained('pais');
            $table->unique(['pais1_id', 'pais2_id']);
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
        Schema::dropIfExists('fronteras');
    }
};
