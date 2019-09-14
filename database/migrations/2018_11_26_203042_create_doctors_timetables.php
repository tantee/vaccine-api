<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctorsTimetables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors_timetables', function (Blueprint $table) {
            $table->increments('id');
            $table->string('doctorCode',20)->index();
            $table->string('clinicCode')->index();
            $table->string('dayOfweek');
            $table->time('beginTime');
            $table->time('endTime');
            $table->integer('limitTotalCase')->nullable();
            $table->integer('limitNewCase')->nullable();
            $table->decimal('dfHospitalPercent',5,2);
            $table->decimal('dfMinimum',8,2)->nullable();
            $table->decimal('dfMaximum',8,2)->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
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
        Schema::dropIfExists('doctors_timetables');
    }
}
