<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableReminderIntervalRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_interval_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('reminder_id');
            $table->smallInteger('user_id');
            $table->timestamps(); // will use updated_at to check if user has been notified
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
        Schema::dropIfExists('table_reminder_interval_records');
    }
}
