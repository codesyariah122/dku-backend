<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonaturNominalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donatur_nominal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donatur_id')->nullable();
            $table->unsignedBigInteger('nominal_id')->nullable();
            $table->foreign('donatur_id')->constrained()->references('id')->on('donaturs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('nominal_id')->constrained()->references('id')->on('nominals')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('donatur_nominal');
    }
}
