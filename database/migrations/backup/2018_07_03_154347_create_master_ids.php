<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_ids', function (Blueprint $table) {
          $table->increments('id');
          $table->string('idType');
          $table->string('prefix');
          $table->integer('runningNumber');
          $table->timestamps();
          $table->index(['idType','prefix']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_ids');
    }
}
