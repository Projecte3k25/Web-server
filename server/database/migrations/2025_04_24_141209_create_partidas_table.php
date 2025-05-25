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
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date')->useCurrent();
            $table->string('nom');
            $table->string('token');
            $table->integer('max_players');
            $table->foreignId('admin_id')->nullable()->constrained('usuaris');
            $table->foreignId('torn_player')->nullable();
            $table->foreignId('estat_torn')->nullable()->constrained('estats');
            $table->string('tipus')->default('Custom');
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
        Schema::dropIfExists('partidas');
    }
};
