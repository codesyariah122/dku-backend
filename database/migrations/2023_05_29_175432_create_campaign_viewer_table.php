<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignViewerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_viewer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewer_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->foreign('viewer_id')->constrained()->references('id')->on('viewers')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('campaign_viewer');
    }
}
