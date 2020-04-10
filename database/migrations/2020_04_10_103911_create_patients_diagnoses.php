<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsDiagnoses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_diagnoses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->enum('diagnosisType',['primary','comorbid','complication','others','external']);
            $table->string('icd10');
            $table->string('diagnosisText')->nullable();
            $table->integer('occurrence')->default(0);
            $table->string('create_by')->nullable();
            $table->string('update_by')->nullable();
            $table->string('delete_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn','diagnosisType']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients_diagnoses');
    }
}
