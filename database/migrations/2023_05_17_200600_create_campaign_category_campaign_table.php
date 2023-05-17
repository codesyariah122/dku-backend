<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignCategoryCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_category_campaign', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_campaign_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->foreign('category_campaign_id')->constrained()->references('id')->on('category_campaigns')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('campaign_id')->constrained()->references('id')->on('campaigns')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('campaign_category_campaign');
    }
}
