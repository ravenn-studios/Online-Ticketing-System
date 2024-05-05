<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('for_user');
            $table->smallInteger('ticket_id');
            $table->text('title');
            $table->text('description');
            $table->smallInteger('day');
            $table->smallInteger('hour');
            $table->smallInteger('minute');
            $table->smallInteger('status_id');
            $table->boolean('is_notified');
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
        Schema::dropIfExists('email_notifications');
    }
}
