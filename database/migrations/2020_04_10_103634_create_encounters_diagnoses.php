<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEncountersDiagnoses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('encounters_diagnoses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->string('encounterId',50);
            $table->enum('diagnosisType',['primary','comorbid','complication','others','external']);
            $table->string('icd10');
            $table->string('diagnosisText')->nullable();
            $table->string('create_by')->nullable();
            $table->string('update_by')->nullable();
            $table->string('delete_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['encounterId','diagnosisType']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('encounters_diagnoses');
    }
}
