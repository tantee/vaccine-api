<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('patients_names', function (Blueprint $table) {
          $table->increments('id');
          $table->string('hn',20);
          $table->string('nameType',10)->default('TH');
          $table->string('namePrefix')->nullable();
          $table->string('firstName')->nullable();
          $table->string('middleName')->nullable();
          $table->string('lastName')->nullable();
          $table->string('nameSuffix')->nullable();
          $table->string('created_by')->nullable();
          $table->string('updated_by')->nullable();
          $table->string('deleted_by')->nullable();
          $table->softDeletes();
          $table->timestamps();
          $table->index(['hn','nameType']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients_names');
    }
}
