<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsReferrers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('patients_referrers', function (Blueprint $table) {
          $table->increments('id');
          $table->string('hn');
          $table->string('hospital');
          $table->string('doctor')->nullable();
          $table->string('referenceId')->nullable();
          $table->text('contact')->nullable();
          $table->softDeletes();
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
        Schema::dropIfExists('patients_referrers');
    }
}
