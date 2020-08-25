<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsRelatives extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('patients_relatives', function (Blueprint $table) {
          $table->increments('id');
          $table->string('hn',20);
          $table->string('name');
          $table->string('relation')->nullable();
          $table->string('mobileNo')->nullable();
          $table->string('telephoneNo')->nullable();
          $table->string('email')->nullable();
          $table->string('note')->nullable();
          $table->string('created_by')->nullable();
          $table->string('updated_by')->nullable();
          $table->string('deleted_by')->nullable();
          $table->softDeletes();
          $table->timestamps();
          $table->index(['hn']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients_relatives');
    }
}
