<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncountersDiagnosesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('encounters_diagnoses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20);
			$table->string('encounterId', 50);
			$table->enum('diagnosisType', ['primary','comorbid','complication','others','external']);
			$table->string('icd10');
			$table->string('diagnosisText')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
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
		Schema::drop('encounters_diagnoses');
	}
}
