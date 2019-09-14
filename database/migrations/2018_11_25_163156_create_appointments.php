<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppointments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->datetime('appointmentDateTime');
            $table->integer('appointmentDuration')->nullable()->default(15);
            $table->string('appointmentType')->nullable();
            $table->string('appointmentActivity')->nullable();
            $table->string('clinicCode');
            $table->string('doctorCode')->nullable();
            $table->text('suggestion')->nullable();
            $table->json('additionalDetail')->nullable();
            $table->string('fromEncounterId')->nullable();
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn','appointmentDateTime','clinicCode','doctorCode']);
            $table->index(['appointmentDateTime','clinicCode']);
            $table->index(['appointmentDateTime','doctorCode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
