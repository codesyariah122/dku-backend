<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankDonaturTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_donatur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donatur_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();

            $table->foreign('donatur_id')->constrained()->references('id')->on('donaturs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('bank_id')->constrained()->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('bank_donatur');
    }
}
