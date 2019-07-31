<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsVitalsign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_vitalsign', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn');
            $table->string('encounterId')->nullable();
            $table->datetime('vitalSignDateTime');
            $table->decimal('temperature',5,2)->nullable();
            $table->integer('heartRate')->nullable();
            $table->integer('respiratoryRate')->nullable();
            $table->integer('bloodPressureSystolic')->nullable();
            $table->integer('bloodPressureDiastolic')->nullable();
            $table->decimal('oxygenSaturation',5,2)->nullable();
            $table->decimal('height',5,2)->nullable();
            $table->decimal('weight',5,2)->nullable();
            $table->integer('painScore')->nullable();
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
        Schema::dropIfExists('patients_vitalsign');
    }
}
