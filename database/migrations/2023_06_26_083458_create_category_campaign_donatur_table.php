<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryCampaignDonaturTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_campaign_donatur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donatur_id')->nullable();
            $table->unsignedBigInteger('category_campaign_id')->nullable();
            $table->foreign('donatur_id')->constrained()->references('id')->on('donaturs')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('category_campaign_id')->constrained()->references('id')->on('category_campaigns')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('category_campaign_donatur');
    }
}
