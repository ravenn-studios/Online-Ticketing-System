<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEbayKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebay_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('status');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->text('appId');
            $table->text('certId');
            $table->text('devId');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ebay_keys');
    }
}