<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->longText('description');
            // $table->unsignedBigInteger('views')->unsigned()->default(0)->nullable();
            $table->unsignedBigInteger('donation_target')->unsigned()->default(0)->nullable();
            $table->enum('is_headline', ['Y', 'N'])->default('N');
            $table->string('banner')->nullable();
            $table->unsignedBigInteger('total_trf')->unsigned()->default(0)->nullable();
            $table->enum('publish', ['Y', 'N'])->default('N');
            $table->longText('barcode')->nullable();
            $table->string('created_by')->nullable();
            $table->string('author')->nullable();
            $table->string('author_email')->nullable();
            $table->enum('without_limit', ['Y', 'N']);
            $table->string('short_link')->nullable();
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
        Schema::dropIfExists('campaigns');
    }
}
