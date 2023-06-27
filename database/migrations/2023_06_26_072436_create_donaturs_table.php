<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonatursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donaturs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->enum('anonim', ['Y', 'N'])->default('N');
            $table->enum('status', ['PAID', 'HOLD', 'PENDING'])->default('PENDING');
            $table->unsignedBigInteger('donation_amount')->unsigned()->default(0)->nullable();
            $table->integer('unique_code')->nullable();
            $table->string('image')->nullable();
            $table->string('methode');

            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('category_campaign_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->enum('fundraiser', ['Y', 'N'])->default('N');

            $table->foreign('campaign_id')->constrained()->references('id')->on('campaigns')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('category_campaign_id')->constrained()->references('id')->on('category_campaigns')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->constrained()->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('bank_id')->constrained()->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('expires_at')->nullable();

            $table->timestamp('deleted_at')->nullable();

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
        Schema::dropIfExists('donaturs');
    }
}
