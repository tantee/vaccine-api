<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsVitalsignsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_vitalsigns', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20);
			$table->string('encounterId', 50)->nullable();
			$table->timestamp('vitalSignDateTime')->useCurrent();
			$table->decimal('temperature', 5)->nullable();
			$table->integer('heartRate')->nullable();
			$table->integer('respiratoryRate')->nullable();
			$table->integer('bloodPressureSystolic')->nullable();
			$table->integer('bloodPressureDiastolic')->nullable();
			$table->decimal('oxygenSaturation', 5)->nullable();
			$table->decimal('height', 5)->nullable();
			$table->decimal('weight', 5)->nullable();
			$table->integer('painScore')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
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
		Schema::drop('patients_vitalsigns');
	}
}
