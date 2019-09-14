<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClinics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->string('clinicCode',50)->primary();
            $table->string('clinicName');
            $table->string('clinicNameEN');
            $table->string('encounterType')->nullable()->default('AMB');
            $table->string('locationCode')->nullable();
            $table->string('specialty')->nullable();
            $table->integer('defaultTimeSlot')->nullable();
            $table->json('defaultDocument')->nullable();
            $table->json('autoCharge')->nullable();
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
        Schema::dropIfExists('clinics');
    }
}
