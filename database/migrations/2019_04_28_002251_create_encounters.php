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
            $table->string('encounterId')->primary();
            $table->string('encounterType')->default('AMB');
            $table->string('hn');
            $table->string('clinicCode');
            $table->string('doctorCode');
            $table->datetime('startDateTime');
            $table->datetime('endDateTime')->nullable();
            $table->json('screening')->nullable();
            $table->json('diagnosis')->nullable();
            $table->enum('status',['checkedIn','checkedOut','closed']);
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
        Schema::dropIfExists('encounters');
    }
}
