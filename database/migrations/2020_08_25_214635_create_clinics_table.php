<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClinicsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clinics', function(Blueprint $table)
		{
			$table->string('clinicCode', 50)->primary();
			$table->string('clinicName');
			$table->string('clinicNameEN');
			$table->string('encounterType', 10)->nullable()->default('AMB');
			$table->string('locationCode', 50)->nullable();
			$table->string('specialty')->nullable();
			$table->integer('defaultTimeSlot')->nullable();
			$table->json('defaultDocument')->nullable();
			$table->json('autoCharge')->nullable();
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
		Schema::drop('clinics');
	}
}
