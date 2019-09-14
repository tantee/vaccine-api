<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEncounters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->string('encounterId',50)->primary();
            $table->string('encounterType',10)->default('AMB');
            $table->string('hn');
            $table->string('clinicCode');
            $table->string('doctorCode');
            $table->string('locationCode');
            $table->string('locationSubunitCode')->nullable();
            $table->json('locationLog')->nullable();
            $table->string('currentLocation')->nullable();
            $table->string('nextLocation')->nullable();
            $table->datetime('admitDateTime');
            $table->datetime('dischargeDateTime')->nullable();
            $table->json('screening')->nullable();
            $table->json('diagnosis')->nullable();
            $table->json('summary')->nullable();
            $table->boolean('isTransactionLock')->nullable()->default(false);
            $table->string('status')->nullable()->default('checkedIn');
            $table->json('statusLog')->nullable();
            $table->integer('fromAppointmentId')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn','encounterType']);
            $table->index(['encounterType','doctorCode']);
            $table->index(['encounterType','locationCode','clinicCode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('encounters');
    }
}
